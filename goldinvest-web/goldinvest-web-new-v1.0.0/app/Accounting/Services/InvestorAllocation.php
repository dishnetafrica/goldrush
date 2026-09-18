<?php

namespace App\Accounting\Services;

use App\Accounting\Exceptions\PostingRefused;
use App\Accounting\Models\AccountingPeriod;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\LotResult;
use App\GoldTrading\Services\LotResultCalculator;
use App\Investor\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * What share of a period's realized result is the investors', under the
 * approved allocation policy.
 *
 * This is a calculation and a report. It moves nothing: no journal, no
 * investor ledger entry, no wallet. What it produces is the figure a later,
 * separately approved distribution would act on, and every reason that figure
 * cannot yet be acted on.
 *
 * The policy, decision by decision:
 *
 *   basis        the sum of the period's FINALIZED, RECORDED deal results, each
 *                split under that deal's own recorded terms
 *   overheads    costs belonging to no deal do not reduce the pool; they are
 *                shown, excluded
 *   eligibility  a recorded realized result, in this period; nothing interim,
 *                nothing still holding gold, nothing historical
 *   terms        a deal without valid terms REFUSES the whole allocation, by name
 *   loss         a negative pool REFUSES; there is no loss policy
 *   reserve      zero, from config, so a retention has one place to be declared
 *   the 100%     a per-deal term, never a default
 *
 * And the separation the whole phase depends on: the company's result is read
 * from the ledger first, from 4000, 5000 and the 6000s, and this calculation
 * is applied to it afterwards. Account 7000 is never an input. Nothing here
 * reads investment_plans or any fixed-return figure.
 */
class InvestorAllocation
{
    private const EPSILON = 0.00000001;

    public const NEGATIVE_POOL = 'Negative investor allocation requires an approved loss policy.';

    public function __construct(
        private readonly RealizedTradingResult $results,
        private readonly LotResultCalculator $calculator,
    ) {
    }

    /**
     * The allocation for a period, or every reason it cannot be made.
     *
     * Deterministic: the same books produce the same answer, in the same order,
     * every time. Nothing in it depends on the clock.
     */
    public function forPeriod(AccountingPeriod $period): array
    {
        $company = $this->results->forCompany(['period_id' => $period->id]);

        $eligible = [];
        $ineligible = [];
        $blocked = [];

        // Every deal with any ledger activity in the period, so that nothing that
        // touched the month can be missed, and every recorded result that belongs
        // to it, so that nothing recorded can be either.
        $lotIds = array_unique(array_merge(
            array_column($company['lots'], 'gold_lot_id'),
            LotResult::where('accounting_period_id', $period->id)->pluck('gold_lot_id')->all(),
        ));
        sort($lotIds);

        foreach (GoldLot::with(['allocations', 'result'])->whereIn('id', $lotIds)->orderBy('lot_code')->get() as $lot) {
            $record = $lot->result;
            $state = $this->results->forLot($lot);

            // Historical attribution: figures from before the ledger existed.
            if ($record && $record->status === LotResult::STATUS_DISTRIBUTED && $record->realized_at === null) {
                $ineligible[] = $this->line($lot, 'historical attribution - not posted to company GL');
                continue;
            }

            // Still holding gold: nothing realized to allocate.
            if (! $state['trading_complete']) {
                $ineligible[] = $this->line($lot, 'gold still held; ' . number_format($state['remaining_grams'], 4)
                    . ' g unsold, nothing realized to allocate');
                continue;
            }

            // Sold out but not recorded: interim, and it belongs to this month, so
            // it holds the allocation up rather than being quietly left out.
            if (! $record || $record->realized_at === null) {
                $blocked[] = $this->line($lot, 'result is interim: ' . lcfirst((string) ($state['qualification'] ?? $state['stage'])));
                continue;
            }

            if ((int) $record->accounting_period_id !== (int) $period->id) {
                $ineligible[] = $this->line($lot, 'recorded in period ' . ($record->period?->code ?? '?') . ', not this one');
                continue;
            }

            // The deal's own terms, applied to the FIGURES AS RECORDED. The recorded
            // result is immutable; reading it rather than recomputing means the
            // allocation cannot drift from what was stood behind.
            if ($lot->allocations->isEmpty()) {
                $blocked[] = $this->line($lot, 'no investor capital is recorded against this deal, so there are '
                    . 'no terms to apply. Record the capital allocations, or the whole result is the company\'s '
                    . 'and that has to be said rather than assumed');
                continue;
            }

            $split = $this->calculator->splitResult($lot, [
                'gross_profit_usd' => (float) $record->gross_profit_usd,
                'net_profit_usd'   => (float) $record->net_profit_usd,
            ]);

            if ($split['missing_terms']) {
                $blocked[] = $this->line($lot, 'no investor share is recorded for this deal. Set it with '
                    . 'gold:set-terms ' . $lot->lot_code . ' --investor-share=NN; no default is applied');
                continue;
            }

            $eligible[] = [
                'lot_code'             => $lot->lot_code,
                'gold_lot_id'          => $lot->id,
                'realized_usd'         => round((float) $record->net_profit_usd, 8),
                'gross_usd'            => round((float) $record->gross_profit_usd, 8),
                'expense_policy'       => $split['applied_expense_policy'],
                'pool_basis_usd'       => $split['profit_pool_usd'],
                'investor_share_pct'   => (float) ($lot->investor_share_percent ?? 0),
                'investor_usd'         => $split['investor_profit_usd'],
                'company_usd'          => $split['company_profit_usd'],
                'investors'            => array_map(fn ($i) => [
                    'user_id'              => $i['user_id'],
                    'capital_usd'          => round($i['capital_usd'], 8),
                    'capital_share_pct'    => $i['capital_share_percent'],
                    'profit_share_pct'     => $i['profit_share_percent'],
                    'allocation_usd'       => round($i['profit_usd'], 8),
                ], $split['investors']),
                'recorded_at'          => $record->realized_at?->toDateTimeString(),
                'result_reference'     => 'LotResult#' . $record->id,
            ];
        }

        $grossPool = round(array_sum(array_column($eligible, 'investor_usd')), 8);
        $reservePercent = (float) config('accounting.allocation.reserve_percent', 0);
        $reserve = round($grossPool * ($reservePercent / 100), 8);
        $pool = round($grossPool - $reserve, 8);

        // Per investor, across every eligible deal.
        $byInvestor = [];

        foreach ($eligible as $deal) {
            foreach ($deal['investors'] as $i) {
                $byInvestor[$i['user_id']] = round(($byInvestor[$i['user_id']] ?? 0) + $i['allocation_usd'], 8);
            }
        }
        ksort($byInvestor);

        $lossStatus = $pool < -self::EPSILON
            ? self::NEGATIVE_POOL
            : ($eligible === [] ? 'no eligible result' : 'not a loss');

        $refusals = array_map(fn ($b) => $b['lot_code'] . ': ' . $b['reason'], $blocked);

        if ($pool < -self::EPSILON) {
            $refusals[] = self::NEGATIVE_POOL;
        }

        if (! $period->isClosed()) {
            $refusals[] = 'Period ' . $period->code . ' is ' . $period->status
                . '; a distribution follows a close, not the other way round.';
        }

        return [
            'period'              => $period->code,
            'period_status'       => $period->status,
            'policy'              => 'sum of finalized recorded deal results, each under its own recorded terms',

            'eligible'            => $eligible,
            'ineligible'          => $ineligible,
            'blocked'             => $blocked,

            'company_trading_result_usd'    => $company['trading_result_usd'],
            'company_overheads_excluded_usd' => $company['unattributed_expenses_usd'],
            'eligible_realized_usd'         => round(array_sum(array_column($eligible, 'realized_usd')), 8),

            'gross_investor_pool_usd' => $grossPool,
            'reserve_percent'         => $reservePercent,
            'reserve_usd'             => $reserve,
            'investor_pool_usd'       => $pool,
            'company_retains_usd'     => round(array_sum(array_column($eligible, 'company_usd')) + $reserve, 8),

            'by_investor'         => $byInvestor,
            'loss_policy_status'  => $lossStatus,

            'distributable'       => $refusals === [] && $pool > self::EPSILON,
            'refusals'            => $refusals,
            'nothing_to_distribute' => $refusals === [] && $pool <= self::EPSILON,
        ];
    }

    /**
     * Refuse loudly where a report would merely list.
     *
     * The report above is for reading; this is for the moment somebody tries to
     * act on it. Same facts, but as an exception naming every reason.
     */
    public function assertDistributable(AccountingPeriod $period): array
    {
        $allocation = $this->forPeriod($period);

        if ($allocation['refusals'] !== []) {
            throw new PostingRefused(
                'The investor allocation for ' . $period->code . ' cannot be made:' . PHP_EOL
                . '  - ' . implode(PHP_EOL . '  - ', $allocation['refusals'])
            );
        }

        if ($allocation['nothing_to_distribute']) {
            throw new PostingRefused(
                'The investor pool for ' . $period->code . ' is ' . Money::format($allocation['investor_pool_usd'])
                . '; there is nothing to distribute.'
            );
        }

        return $allocation;
    }

    private function line(GoldLot $lot, string $reason): array
    {
        return ['lot_code' => $lot->lot_code, 'gold_lot_id' => $lot->id, 'reason' => $reason];
    }
}
