<?php

namespace App\Console\Commands;

use App\Accounting\Models\BankReconciliation;
use App\Accounting\Models\CashAccount;
use App\Accounting\Services\BankReconciliationService;
use App\Investor\Support\Money;
use Illuminate\Console\Command;

/**
 * Reports whether the company's books agree with the bank, and records the
 * answer when they do.
 */
class BankReconcileCommand extends Command
{
    protected $signature = 'bank:reconcile
                            {account : cash account code}
                            {--as-at= : date to reconcile to, defaults to today}
                            {--closing-balance= : the balance the bank says}
                            {--complete : record the reconciliation}
                            {--notes= }
                            {--history : show past reconciliations}';

    protected $description = 'Reconcile a bank account against its statement';

    public function handle(BankReconciliationService $service): int
    {
        $account = CashAccount::where('code', $this->argument('account'))->first();

        if (! $account) {
            $this->error('No cash account with code ' . $this->argument('account'));

            return self::FAILURE;
        }

        if ($this->option('history')) {
            return $this->history($account);
        }

        if ($this->option('closing-balance') === null) {
            $this->error('Give the balance the bank says: --closing-balance=1234.56');

            return self::FAILURE;
        }

        $asAt = $this->option('as-at') ?: now()->toDateString();
        $summary = $service->summarise($account, $asAt, (float) $this->option('closing-balance'));

        $this->newLine();
        $this->line('Reconciliation - ' . $account->label() . ' as at ' . $asAt);
        $this->line(str_repeat('-', 60));
        $this->line('  Bank says       : ' . Money::format($summary['statement_closing']));
        $this->line('  Ledger says     : ' . Money::format($summary['ledger_balance']));
        $this->line('  Difference      : ' . Money::exact($summary['difference']));
        $this->line('  Statement lines : ' . $summary['lines_total'] . ' (' . $summary['lines_matched']
            . ' matched, ' . $summary['lines_unmatched'] . ' unmatched, ' . $summary['lines_ignored'] . ' set aside)');

        if ($summary['lines_unmatched'] > 0) {
            $this->newLine();
            $this->warn('Unmatched lines:');
            $this->table(
                ['ID', 'Date', 'Description', 'Amount'],
                $summary['unmatched']->map(fn ($l) => [
                    $l->id, $l->value_date->format('d M Y'), $l->description, Money::signed($l->amount),
                ])->all()
            );
        }

        $this->newLine();

        if (! $summary['reconciles']) {
            $this->error('DOES NOT RECONCILE - difference of ' . Money::exact($summary['difference'])
                . '. Find it and post a correction; reconciling never changes accounting by itself.');
        } else {
            $this->info('RECONCILES - the bank and the ledger agree.');
        }

        if (! $this->option('complete')) {
            return $summary['reconciles'] ? self::SUCCESS : self::FAILURE;
        }

        try {
            $record = $service->complete($account, $asAt, (float) $this->option('closing-balance'), null, $this->option('notes'));
        } catch (\Throwable $e) {
            $this->error('Not completed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('Recorded as ' . $record->reference . '.');

        return self::SUCCESS;
    }

    private function history(CashAccount $account): int
    {
        $records = BankReconciliation::where('cash_account_id', $account->id)->orderByDesc('as_at')->get();

        if ($records->isEmpty()) {
            $this->warn('No reconciliations recorded for ' . $account->label() . '.');

            return self::SUCCESS;
        }

        $this->table(
            ['Reference', 'As at', 'Bank', 'Ledger', 'Difference', 'Matched', 'Status', 'Completed'],
            $records->map(fn (BankReconciliation $r) => [
                $r->reference, $r->as_at->format('d M Y'),
                Money::format($r->statement_closing_balance), Money::format($r->ledger_balance),
                Money::exact($r->difference), $r->lines_matched, $r->status,
                $r->completed_at?->format('d M Y H:i') ?? '-',
            ])->all()
        );

        return self::SUCCESS;
    }
}
