<?php

namespace App\Accounting\Services;

use App\Accounting\Exceptions\PostingRefused;
use App\Accounting\Security\AccountingPermission;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\LotResult;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Standing behind a deal's figures.
 *
 * Recording a realized result is a deliberate act, not something that happens
 * because the last gram was sold. Until somebody records it, the figure is
 * current rather than final, and it is allowed to move — a late transport
 * invoice, a refining charge that arrives a week after the gold did. Afterwards
 * it is not, and a correction means reversing the cost in the ledger and
 * recording the result again, so both are visible.
 *
 * Nothing is computed here. The figures come from RealizedTradingResult, which
 * reads them out of the general ledger; this only writes down what the ledger
 * said at the moment somebody accepted it, and refuses to do so while anything
 * is still outstanding.
 *
 * Nothing here touches an investor. Deciding what share of a realized result
 * becomes an investor's is Phase 3F, and it cannot happen before there is a
 * final company figure for it to be a share of.
 */
class RealizedResultRecorder
{
    private const EPSILON = 0.00000001;

    public function __construct(private readonly RealizedTradingResult $results)
    {
    }

    /**
     * Record what a deal realized.
     *
     * Asking twice records once: the result already accepted is handed back
     * rather than a second one being written over it.
     */
    public function record(GoldLot $lot, ?Admin $actor = null, array $context = []): LotResult
    {
        AccountingPermission::assert($actor, AccountingPermission::RESULT_RECORD);

        $existing = LotResult::where('gold_lot_id', $lot->id)->first();

        if ($existing && $existing->realized_at !== null) {
            return $existing;
        }

        $this->assertRecordable($lot, $existing);

        $result = $this->results->forLot($lot);
        $period = $this->results->periodFor($lot);

        if ($period && $period->isClosed()) {
            throw new PostingRefused(
                'The result of ' . $lot->lot_code . ' belongs to period ' . $period->code
                . ', which is ' . $period->status . '. Recording it now would assert a figure for a period '
                . 'the company has already stood behind.'
            );
        }

        $closedAt = $lot->sales()->where('status', 'settled')->max('sale_date');

        return DB::transaction(function () use ($lot, $existing, $result, $period, $closedAt, $actor, $context) {
            $attributes = [
                'gold_lot_id'            => $lot->id,
                'closed_at'              => $closedAt ?: Carbon::now()->toDateString(),
                'refined_grams'          => $result['refined_grams'],
                'sold_grams'             => $result['sold_grams'],
                'cost_of_goods_sold_usd' => $result['cost_of_goods_sold_usd'],
                'capitalised_cost_usd'   => $result['capitalised_cost_usd'],
                'expenses_usd'           => $result['ordinary_expenses_usd'],
                'proceeds_usd'           => $result['revenue_usd'],
                'revenue_usd'            => $result['revenue_usd'],
                'gross_profit_usd'       => $result['gross_profit_usd'],
                'net_profit_usd'         => $result['net_realized_usd'],
                'status'                 => LotResult::STATUS_REALIZED,
                'realized_at'            => Carbon::now(),
                'realized_by'            => $actor?->id,
                'accounting_period_id'   => $period?->id,
                'notes'                  => $context['notes'] ?? null,
                'snapshot'               => [
                    'revenue_usd'            => $result['revenue_usd'],
                    'cost_of_goods_sold_usd' => $result['cost_of_goods_sold_usd'],
                    'gross_profit_usd'       => $result['gross_profit_usd'],
                    'ordinary_expenses_usd'  => $result['ordinary_expenses_usd'],
                    'expenses_by_account'    => $result['expenses_by_account'],
                    'capitalised_cost_usd'   => $result['capitalised_cost_usd'],
                    'cost_basis_usd'         => $result['cost_basis_usd'],
                    'net_realized_usd'       => $result['net_realized_usd'],
                    'recorded_from'          => 'general_ledger',
                ],
            ];

            // Investor share is left exactly as it was. What portion of this
            // becomes somebody's is Phase 3F's decision, and writing a zero here
            // would be as much of an answer as writing a number.
            if ($existing) {
                $existing->forceFill($attributes)->save();

                return $existing->refresh();
            }

            return LotResult::create($attributes);
        });
    }

    /** Everything that has to be true before a figure can be called final. */
    private function assertRecordable(GoldLot $lot, ?LotResult $existing): void
    {
        if ($existing && $existing->status === LotResult::STATUS_DISTRIBUTED) {
            throw new PostingRefused(
                'Deal ' . $lot->lot_code . ' was closed and its profit distributed before the general ledger '
                . 'existed. Its figures are attribution records, and reconstructing them as accounting belongs '
                . 'to the historical backfill, where the funding can be established from records rather than '
                . 'assumed.'
            );
        }

        if ($lot->purchase_journal_id === null) {
            throw new PostingRefused(
                'Deal ' . $lot->lot_code . ' has never been posted to the general ledger, so there are no '
                . 'accounting figures to record. Post its purchase, refining and sales first.'
            );
        }

        $result = $this->results->forLot($lot);

        if (! $result['trading_complete']) {
            throw new PostingRefused(
                'Deal ' . $lot->lot_code . ' still holds ' . number_format($result['remaining_grams'], 4)
                . ' g of gold. What it realized is not known until the gold has gone.'
            );
        }

        if ($result['unposted_costs_usd'] > self::EPSILON) {
            throw new PostingRefused(
                'Deal ' . $lot->lot_code . ' has ' . Money::format($result['unposted_costs_usd'])
                . ' of recorded costs that have not reached the ledger. Post or reject them first; '
                . 'recording the result now would call a figure final that is about to fall.'
            );
        }

        if (! $result['expenses_finalised']) {
            throw new PostingRefused(
                'The costs of ' . $lot->lot_code . ' have not been declared complete, so its result may still '
                . 'fall. Run trading:expenses-final ' . $lot->lot_code . ' once every cost is in.'
            );
        }
    }

    /**
     * Declare that every cost of a deal is in.
     *
     * A separate act from recording the result, and usually a different person's:
     * one says the costs are complete, the other says the figures are accepted.
     * Collapsing them would mean the only check on a final figure was the wish to
     * produce one.
     *
     * It is refused while any recorded cost is still outside the ledger, because
     * declaring costs complete while one of them is sitting in a drawer is how a
     * result comes to be wrong in a way nobody notices.
     */
    public function finaliseExpenses(GoldLot $lot, ?Admin $actor = null): GoldLot
    {
        AccountingPermission::assert($actor, AccountingPermission::EXPENSE_APPROVE);

        if ($lot->expensesFinalised()) {
            return $lot;
        }

        $result = $this->results->forLot($lot);

        if ($result['unposted_costs_usd'] > self::EPSILON) {
            throw new PostingRefused(
                'Deal ' . $lot->lot_code . ' has ' . Money::format($result['unposted_costs_usd'])
                . ' of recorded costs that have not reached the ledger. Post or reject them before declaring '
                . 'the costs of this deal complete.'
            );
        }

        $lot->forceFill([
            'expenses_finalised_at' => Carbon::now(),
            'expenses_finalised_by' => $actor?->id,
        ])->save();

        return $lot;
    }

    /**
     * Take that back, because a cost turned up after all.
     *
     * Only while the result is still interim. Once it has been recorded the
     * figure is one the company stands behind, and a late cost is answered in the
     * ledger rather than by quietly reopening the deal it belongs to.
     */
    public function reopenExpenses(GoldLot $lot, string $reason, ?Admin $actor = null): GoldLot
    {
        AccountingPermission::assert($actor, AccountingPermission::EXPENSE_APPROVE);

        if (trim($reason) === '') {
            throw new PostingRefused('Reopening a deal\'s costs needs a reason.');
        }

        $record = LotResult::where('gold_lot_id', $lot->id)->first();

        if ($record && $record->realized_at !== null) {
            throw new PostingRefused(
                'The result of ' . $lot->lot_code . ' has been recorded. A cost arriving now is posted to the '
                . 'ledger in the period it belongs to; the recorded figure is not reopened.'
            );
        }

        $lot->forceFill([
            'expenses_finalised_at' => null,
            'expenses_finalised_by' => null,
            'notes'                 => trim((string) $lot->notes . "\n" . 'Costs reopened: ' . trim($reason)),
        ])->save();

        return $lot;
    }
}
