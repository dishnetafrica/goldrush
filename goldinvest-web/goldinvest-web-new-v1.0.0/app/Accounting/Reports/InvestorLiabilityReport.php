<?php

namespace App\Accounting\Reports;

use App\Accounting\Models\InvestorDistributionLine;
use App\Investor\Ledger\Bucket;
use App\Investor\Ledger\LedgerEvent;
use App\Investor\Models\InvestorDocument;
use App\Investor\Models\LedgerEntry;
use App\Models\Admin\Currency;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Support\Facades\DB;

/**
 * What the company owes its investors, and where each figure comes from.
 *
 * The chain, in order: capital liability (2000, per investor from the ledger's
 * available and committed buckets), trading allocation (the distribution
 * snapshot), profit payable (2010, per investor from the ledger's profit
 * bucket), actual payment (ledger withdrawal events; the GL side is gap G1).
 *
 * The wallet appears once, in a control column, with its difference to the
 * ledger. It is never a source. Nothing here shows grams, lots or ownership
 * against an investor: their money is a liability of the company, split
 * between capital and profit, and that is all it is.
 *
 * Two views. The admin view is every investor with the company totals and the
 * GL controls. The investor's own view is their row alone: no other investor,
 * no company total, no GL, no gold, no supplier or expense figure.
 */
class InvestorLiabilityReport
{
    private const WITHDRAWAL_EVENTS = [
        LedgerEvent::WITHDRAWAL_REQUESTED, LedgerEvent::WITHDRAWAL_PAID, LedgerEvent::WITHDRAWAL_REVERSED,
    ];

    /** The keys an investor's own view is permitted to contain. Anything else is a leak. */
    public const INVESTOR_VIEW_KEYS = [
        'report', 'scope', 'investor', 'opening', 'closing', 'movements', 'distributions', 'withdrawals', 'statements', 'currency', 'interim',
    ];

    public function __construct(private readonly LedgerFigures $ledger)
    {
    }

    /** The admin view: every investor, the totals, the GL controls. */
    public function company(ReportScope $scope): array
    {
        $users = $scope->userId
            ? User::where('id', $scope->userId)->get()
            : User::whereIn('id', LedgerEntry::select('user_id')->distinct())->orderBy('id')->get();

        $rows = [];
        $totals = ['available' => 0.0, 'profit' => 0.0, 'committed' => 0.0, 'wallet_balance' => 0.0, 'wallet_profit' => 0.0,
            'profit_credited_in_scope' => 0.0];

        foreach ($users as $user) {
            $row = $this->row($user, $scope);
            $rows[] = $row;

            $totals['available'] += $row['closing'][Bucket::AVAILABLE];
            $totals['profit'] += $row['closing'][Bucket::PROFIT];
            $totals['committed'] += $row['closing'][Bucket::COMMITTED];
            $totals['wallet_balance'] += $row['wallet']['balance'];
            $totals['wallet_profit'] += $row['wallet']['profit_balance'];
            $totals['profit_credited_in_scope'] += $row['profit_credited_in_scope'];
        }

        foreach ($totals as $k => $v) {
            $totals[$k] = round($v, 8);
        }

        $asAt = $scope->end()->toDateString();
        $gl2000 = $this->ledger->balanceAt('2000', $asAt);
        $gl2010 = $this->ledger->balanceAt('2010', $asAt);
        $movement2010 = $this->ledger->movement('2010', $scope);

        // Every active distribution line in scope has a ledger entry of the same amount.
        $lineRows = [];
        $linesOk = true;

        foreach ($this->distributionLines($scope, null) as $line) {
            $ok = $line['ledger_amount_usd'] !== null && abs($line['amount_usd'] - $line['ledger_amount_usd']) <= Control::EPSILON;
            $linesOk = $linesOk && $ok;
            $lineRows[] = $line + ['matched' => $ok];
        }

        $walletRows = array_values(array_filter(array_map(fn ($r) => [
            'user' => $r['investor']['username'], 'ledger_available' => $r['closing'][Bucket::AVAILABLE],
            'wallet_balance' => $r['wallet']['balance'], 'ledger_profit' => $r['closing'][Bucket::PROFIT],
            'wallet_profit' => $r['wallet']['profit_balance'], 'difference' => $r['wallet']['difference'],
        ], $rows), fn ($w) => abs($w['difference']) > Control::EPSILON));

        $controls = [
            // The balance to date: on real data this is the D2/D3 gap and is labelled as such.
            Control::make('il_profit_balance', 'Investor Profit Payable (GL 2010) equals the investor ledger profit to date',
                'GL 2010', $gl2010, 'Investor ledger profit', $totals['profit'], true),
            Control::make('il_capital_balance', 'Investor Capital Payable (GL 2000) equals the investor ledger available plus committed to date',
                'GL 2000', $gl2000, 'Investor ledger capital', round($totals['available'] + $totals['committed'], 8), true),
            // The movement within the scope: what this period's own postings did.
            Control::make('il_profit_movement', '2010 credited in scope equals profit credited to investor ledgers in scope',
                'GL 2010 movement', $movement2010['net'], 'Ledger profit credits', $totals['profit_credited_in_scope'], true),
            Control::flag('il_distribution_lines', 'Every distribution line has a ledger entry of the same amount', $linesOk,
                count($lineRows) . ' line(s) checked'),
            Control::make('il_wallet', 'Investor ledger equals the wallet the application spends from (control figure only)',
                'Ledger total', round($totals['available'] + $totals['profit'], 8), 'Wallet total',
                round($totals['wallet_balance'] + $totals['wallet_profit'], 8), false,
                'A wallet that disagrees with the ledger. Run ledger:check; the ledger is the trail, the wallet is not.', $walletRows),
        ];

        return [
            'report'   => 'investor-liability',
            'scope'    => $scope->toArray(),
            'currency' => Currency::where('default', true)->value('code') ?? 'USD',
            'rows'     => $rows,
            'totals'   => $totals,
            'gl'       => ['capital_2000_usd' => $gl2000, 'profit_2010_usd' => $gl2010, 'movement_2010' => $movement2010, 'as_at' => $asAt],
            'chain'    => [
                'capital_liability'  => ['source' => 'GL 2000; per investor: investor ledger available + committed', 'total_usd' => round($totals['available'] + $totals['committed'], 8), 'gl_usd' => $gl2000],
                'trading_allocation' => ['source' => 'investor_distributions.snapshot (posted); InvestorAllocation preview (open)'],
                'profit_payable'     => ['source' => 'GL 2010; per investor: investor ledger profit bucket', 'total_usd' => $totals['profit'], 'gl_usd' => $gl2010],
                'actual_payment'     => ['source' => 'investor ledger withdrawal events; GL side is G1 (open)'],
            ],
            'distribution_lines' => $lineRows,
            'empty'    => $rows === [],
            'controls' => $controls,
        ];
    }

    /**
     * The investor's own view. Their row only, and none of the company's figures.
     * The keys are fixed (INVESTOR_VIEW_KEYS) and the self-test asserts nothing else appears.
     */
    public function forInvestor(User $user, ReportScope $scope): array
    {
        $row = $this->row($user, $scope);

        $statements = InvestorDocument::where('user_id', $user->id)
            ->where('type', InvestorDocument::TYPE_STATEMENT)->whereNull('revoked_at')
            ->orderByDesc('id')->limit(12)->get()
            ->map(fn ($d) => ['id' => $d->id, 'number' => $d->document_number, 'period_start' => $d->period_start?->toDateString(),
                'period_end' => $d->period_end?->toDateString(), 'generated_at' => $d->generated_at?->toDateTimeString(),
                'interim' => (bool) ($d->meta['interim'] ?? false)])->all();

        return [
            'report'   => 'investor-position',
            'scope'    => ['label' => $scope->label(), 'from' => $scope->from, 'to' => $scope->to],
            'investor' => $row['investor'],
            'opening'  => $row['opening'],
            'closing'  => $row['closing'],
            'movements' => $row['movements'],
            'distributions' => array_map(fn ($d) => [
                'reference' => $d['distribution'], 'period' => $d['period'], 'declared_on' => $d['declared_on'],
                'amount_usd' => $d['amount_usd'], 'status' => $d['status'], 'ledger_reference' => $d['ledger_reference'],
            ], $row['distributions']),
            'withdrawals' => $row['withdrawals'],
            'statements'  => $statements,
            'currency'    => Currency::where('default', true)->value('code') ?? 'USD',
            'interim'     => ! $scope->isFinal(),
        ];
    }

    private function row(User $user, ReportScope $scope): array
    {
        $start = $scope->start();
        $end = $scope->end();

        $before = $start
            ? LedgerEntry::forUser($user->id)->where('occurred_at', '<', $start)->orderByDesc('seq')->first()
            : null;
        $last = LedgerEntry::forUser($user->id)->where('occurred_at', '<=', $end)->orderByDesc('seq')->first();

        $within = LedgerEntry::forUser($user->id)->chronological()
            ->when($start, fn ($q) => $q->where('occurred_at', '>=', $start))
            ->where('occurred_at', '<=', $end)->get();

        $movements = [];

        foreach ($within as $e) {
            $movements[$e->event_type] ??= ['event' => $e->event_type, 'label' => LedgerEvent::label($e->event_type),
                'count' => 0, 'amount_usd' => 0.0, 'by_bucket' => Bucket::zeroed()];
            $movements[$e->event_type]['count']++;
            $movements[$e->event_type]['amount_usd'] = round($movements[$e->event_type]['amount_usd'] + (float) $e->amount_usd, 8);
            $movements[$e->event_type]['by_bucket'][$e->bucket] = round($movements[$e->event_type]['by_bucket'][$e->bucket] + (float) $e->amount_usd, 8);
        }

        $withdrawals = $within->filter(fn ($e) => in_array($e->event_type, self::WITHDRAWAL_EVENTS, true))
            ->map(fn ($e) => ['date' => $e->occurred_at->toDateTimeString(), 'event' => $e->event_type, 'label' => LedgerEvent::label($e->event_type),
                'bucket' => $e->bucket, 'amount_usd' => (float) $e->amount_usd, 'reference' => $e->reference,
                'gl' => 'not bridged to GL (G1 open)'])->values()->all();

        $wallet = $this->wallet($user);
        $closing = $this->position($last);
        $walletDiff = round(($closing[Bucket::AVAILABLE] - $wallet['balance']) + ($closing[Bucket::PROFIT] - $wallet['profit_balance']), 8);

        return [
            'investor' => [
                'id' => $user->id, 'username' => $user->username, 'email' => $user->email,
                'name' => trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) ?: $user->username,
            ],
            'opening'   => $this->position($before) + ['note' => $start ? 'position before ' . $start->toDateString() : 'from the beginning'],
            'closing'   => $closing,
            'movements' => array_values($movements),
            'distributions' => $this->distributionLines($scope, $user->id),
            'withdrawals'   => $withdrawals,
            'wallet' => $wallet + ['difference' => $walletDiff, 'note' => 'control figure only, never a source'],
            'entries_in_scope' => $within->count(),
            'profit_credited_in_scope' => round((float) $within->where('event_type', LedgerEvent::PROFIT_CREDITED)->sum('amount_usd'), 8),
        ];
    }

    /** Distribution lines in scope, with the ledger entry each points at. */
    private function distributionLines(ReportScope $scope, ?int $userId): array
    {
        $query = InvestorDistributionLine::with(['distribution.period', 'distribution.journal', 'ledgerEntry'])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))->orderBy('id');

        $out = [];

        foreach ($query->get() as $line) {
            $d = $line->distribution;
            $journal = $d?->journal;

            if (! $journal) {
                continue;
            }

            $date = $journal->journal_date->toDateString();
            $inScope = $scope->period
                ? (int) $journal->accounting_period_id === (int) $scope->period->id
                : ((! $scope->from || $date >= $scope->from) && $date <= $scope->end()->toDateString());

            if (! $inScope) {
                continue;
            }

            $out[] = [
                'distribution'      => $d->reference,
                'status'            => $d->status,
                'period'            => $d->period?->code,
                'declared_on'       => $date,
                'user_id'           => $line->user_id,
                'user'              => User::find($line->user_id)?->username ?? ('user #' . $line->user_id),
                'amount_usd'        => (float) $line->amount_usd,
                'ledger_reference'  => $line->ledger_reference,
                'ledger_amount_usd' => $line->ledgerEntry ? (float) $line->ledgerEntry->amount_usd : null,
                'trx_id'            => $line->trx_id,
            ];
        }

        return $out;
    }

    private function position(?LedgerEntry $entry): array
    {
        if (! $entry) {
            return Bucket::zeroed() + ['total' => 0.0];
        }

        $p = [Bucket::AVAILABLE => (float) $entry->balance_available, Bucket::PROFIT => (float) $entry->balance_profit,
            Bucket::COMMITTED => (float) $entry->balance_committed];

        return $p + ['total' => round(array_sum($p), 8)];
    }

    private function wallet(User $user): array
    {
        $currency = Currency::where('default', true)->first();
        $wallet = $currency ? UserWallet::where('user_id', $user->id)->where('currency_id', $currency->id)->first() : null;

        return ['balance' => (float) ($wallet->balance ?? 0), 'profit_balance' => (float) ($wallet->profit_balance ?? 0), 'exists' => $wallet !== null];
    }
}
