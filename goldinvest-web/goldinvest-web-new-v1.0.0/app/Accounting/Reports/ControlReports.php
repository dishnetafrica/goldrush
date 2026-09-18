<?php

namespace App\Accounting\Reports;

use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\BankStatementLine;
use App\Accounting\Models\CashAccount;
use App\Accounting\Models\InvestorDistribution;
use App\Investor\Models\LedgerEntry;
use App\Investor\Services\LedgerReconciler;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Every control in one place, each with its two figures and its difference.
 *
 * A control here never adjusts anything. It reads both sides, subtracts, and
 * says RECONCILED, CONTROL EXCEPTION or PRE-BACKFILL. The last is the known
 * D2/D3 gap and is shown with exactly that label, so that a real exception
 * can never hide behind it and it can never hide behind a flag.
 */
class ControlReports
{
    public function __construct(
        private readonly LedgerFigures $ledger,
        private readonly FinancialStatements $statements,
        private readonly TradingReports $trading,
        private readonly InvestorLiabilityReport $investors,
        private readonly LedgerReconciler $reconciler,
    ) {
    }

    public function all(ReportScope $scope): array
    {
        $tb = $this->statements->trialBalance($scope);
        $bs = $this->statements->balanceSheet($scope);
        $cf = $this->statements->cashFlow($scope);
        $pl = $this->statements->profitAndLoss($scope);
        $gold = $this->trading->goldTrading(new ReportScope(lot: $scope->lot));
        $il = $this->investors->company($scope);

        $groups = [
            'Trial balance'        => $tb['controls'],
            'Accounting equation'  => $bs['controls'],
            'Profit and loss'      => $pl['controls'],
            'Cash flow'            => $cf['controls'],
            'Inventory and cost of sales, per deal' => $gold['controls'],
            'Investor liability'   => $il['controls'],
            'Distributions'        => $this->distributions(),
            'Transactions accounted for in the investor ledger' => $this->transactions(),
            'Bank'                 => $this->bank(),
            'Close snapshots'      => $this->closeSnapshots(),
        ];

        $flat = array_merge(...array_values($groups));

        return [
            'report'   => 'controls',
            'scope'    => $scope->toArray(),
            'groups'   => $groups,
            'controls' => $flat,
            'passed'   => Control::allPassed($flat),
            'only_pre_backfill' => Control::onlyPreBackfill($flat),
            'exceptions' => array_values(array_filter($flat, fn ($c) => $c['status'] === Control::EXCEPTION)),
            'pre_backfill' => array_values(array_filter($flat, fn ($c) => $c['status'] === Control::PRE_BACKFILL)),
            'empty'    => $tb['empty'],
        ];
    }

    /** Σ lines of each posted distribution equals its journal's Dr 7000 and Cr 2010. */
    private function distributions(): array
    {
        $controls = [];

        foreach (InvestorDistribution::with(['journal.lines.account', 'lines'])->orderBy('id')->get() as $d) {
            $lines = round((float) $d->lines->sum('amount_usd'), 8);
            $dr7000 = round((float) $d->journal?->lines->filter(fn ($l) => $l->account->code === '7000')->sum('debit'), 8);
            $cr2010 = round((float) $d->journal?->lines->filter(fn ($l) => $l->account->code === '2010')->sum('credit'), 8);

            $controls[] = Control::make('dist_7000_' . $d->reference, $d->reference . ': lines equal Dr 7000', 'Σ lines', $lines, 'Dr 7000', $dr7000);
            $controls[] = Control::make('dist_2010_' . $d->reference, $d->reference . ': lines equal Cr 2010', 'Σ lines', $lines, 'Cr 2010', $cr2010);
        }

        return $controls ?: [Control::flag('dist_none', 'No distributions have been posted', true, 'nothing to check')];
    }

    private function transactions(): array
    {
        $controls = [];

        foreach (User::whereIn('id', LedgerEntry::select('user_id')->distinct())->orderBy('id')->get() as $user) {
            $r = $this->reconciler->forUser($user);
            $controls[] = Control::flag('trx_' . $user->id, $user->username . ': every transaction is in the ledger',
                $r['checks']['transactions']['passed'], $r['checks']['transactions']['message'],
                $r['checks']['transactions']['passed'] ? null : implode(' ', $r['checks']['transactions']['discrepancies']));
        }

        return $controls ?: [Control::flag('trx_none', 'No investor ledgers exist', true, 'nothing to check')];
    }

    private function bank(): array
    {
        $controls = [];

        foreach (CashAccount::where('type', CashAccount::TYPE_BANK)->with('glAccount')->orderBy('code')->get() as $a) {
            $unmatched = BankStatementLine::where('cash_account_id', $a->id)->where('status', BankStatementLine::UNMATCHED)->count();
            $last = DB::table('bank_reconciliations')->where('cash_account_id', $a->id)->whereNotNull('completed_at')->orderByDesc('completed_at')->first();

            $controls[] = Control::flag('bank_' . $a->code, $a->code . ': no unmatched statement lines', $unmatched === 0,
                $unmatched . ' unmatched; ledger ' . number_format($a->glAccount ? $this->ledger->balanceAt($a->glAccount->code) : 0, 2)
                . '; last completed reconciliation ' . ($last?->completed_at ?? 'never'));

            if ($last) {
                $controls[] = Control::make('bankrec_' . $a->code, $a->code . ': last reconciliation ledger equals statement',
                    'Ledger at reconciliation', (float) $last->ledger_balance, 'Statement closing', (float) $last->statement_closing_balance);
            }
        }

        return $controls ?: [Control::flag('bank_none', 'No bank accounts are defined', true, 'nothing to check')];
    }

    private function closeSnapshots(): array
    {
        $controls = [];

        foreach (AccountingPeriod::whereNotNull('close_reference')->orderBy('code')->get() as $period) {
            $report = $this->trading->periodClose($period);
            $failed = array_filter($report['snapshot_checks'], fn ($c) => ! $c['passed']);
            $controls[] = Control::flag('snap_' . $period->code, $period->code . ' (' . $period->close_reference . '): close snapshot equals the ledger',
                $failed === [], count($report['snapshot_checks']) . ' figure(s) compared' . ($period->isClosed() ? '' : '; period since reopened'),
                $failed === [] ? null : 'The ledger no longer agrees with what was closed.');
        }

        return $controls ?: [Control::flag('snap_none', 'No period has been closed', true, 'nothing to check')];
    }
}
