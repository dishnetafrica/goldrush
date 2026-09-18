<?php

namespace App\Console\Commands;

use App\Accounting\Models\BankStatementLine;
use App\Accounting\Models\CashAccount;
use App\Accounting\Models\JournalLine;
use App\Accounting\Services\BankReconciliationService;
use App\Investor\Support\Money;
use Illuminate\Console\Command;

/**
 * Brings the bank's own record of events into the system, and matches it
 * against what the company recorded.
 */
class BankStatementCommand extends Command
{
    protected $signature = 'bank:statement
                            {account : cash account code}
                            {--import= : CSV file with date,description,amount[,external_ref]}
                            {--statement-ref= : a name for this statement}
                            {--list : show the lines held for this account}
                            {--suggest : propose matches without making any}
                            {--match= : statement line id to match}
                            {--to-line= : journal line id it corresponds to}
                            {--unmatch= : statement line id to unmatch}
                            {--ignore= : statement line id to set aside}
                            {--reason= : why a line is being set aside}';

    protected $description = 'Import, list and match bank statement lines';

    public function handle(BankReconciliationService $service): int
    {
        $account = CashAccount::where('code', $this->argument('account'))->first();

        if (! $account) {
            $this->error('No cash account with code ' . $this->argument('account'));

            return self::FAILURE;
        }

        try {
            return match (true) {
                (bool) $this->option('import')   => $this->import($service, $account),
                (bool) $this->option('suggest')  => $this->suggest($service, $account),
                (bool) $this->option('match')    => $this->match($service),
                (bool) $this->option('unmatch')  => $this->unmatch($service),
                (bool) $this->option('ignore')   => $this->ignore($service),
                default                          => $this->list($account),
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function import(BankReconciliationService $service, CashAccount $account): int
    {
        $path = $this->option('import');

        if (! is_file($path)) {
            $this->error('No such file: ' . $path);

            return self::FAILURE;
        }

        $rows = [];
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }

            $rows[] = [
                'date'         => $row[0],
                'description'  => $row[1] ?? '',
                'amount'       => $row[2] ?? 0,
                'external_ref' => $row[3] ?? null,
            ];
        }

        fclose($handle);

        $result = $service->import($account, $rows, $this->option('statement-ref'));

        $this->info($result['imported'] . ' line(s) imported, ' . $result['duplicates'] . ' already present.');

        return $this->list($account);
    }

    private function list(CashAccount $account): int
    {
        $lines = BankStatementLine::where('cash_account_id', $account->id)->orderBy('value_date')->get();

        if ($lines->isEmpty()) {
            $this->warn('No statement lines for ' . $account->label() . '.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Date', 'Description', 'Amount', 'Status', 'Journal line'],
            $lines->map(fn (BankStatementLine $l) => [
                $l->id, $l->value_date->format('d M Y'), $l->description,
                Money::signed($l->amount), $l->status,
                $l->matched_journal_line_id ?? ($l->status === BankStatementLine::IGNORED ? $l->ignore_reason : '-'),
            ])->all()
        );

        $this->line('  ' . $lines->where('status', BankStatementLine::UNMATCHED)->count() . ' unmatched, '
            . $lines->where('status', BankStatementLine::MATCHED)->count() . ' matched, '
            . $lines->where('status', BankStatementLine::IGNORED)->count() . ' set aside.');
        $this->line('  Ledger balance: ' . Money::format($account->balance()));

        return self::SUCCESS;
    }

    private function suggest(BankReconciliationService $service, CashAccount $account): int
    {
        $suggestions = $service->suggest($account);

        if ($suggestions === []) {
            $this->info('Nothing to suggest: every line is either matched or has no obvious counterpart.');

            return self::SUCCESS;
        }

        $this->table(
            ['Statement line', 'Date', 'Amount', 'Journal line', 'Journal', 'Match with'],
            array_map(fn ($s) => [
                $s['statement_line']->id,
                $s['statement_line']->value_date->format('d M Y'),
                Money::signed($s['statement_line']->amount),
                $s['journal_line']->id,
                $s['journal_line']->journal->reference,
                'bank:statement ' . $s['statement_line']->cashAccount->code
                . ' --match=' . $s['statement_line']->id . ' --to-line=' . $s['journal_line']->id,
            ], $suggestions)
        );

        $this->warn('These are suggestions only. Nothing has been matched.');

        return self::SUCCESS;
    }

    private function match(BankReconciliationService $service): int
    {
        $line = BankStatementLine::findOrFail((int) $this->option('match'));
        $journalLine = JournalLine::findOrFail((int) $this->option('to-line'));

        $service->match($line, $journalLine);
        $this->info('Matched statement line #' . $line->id . ' to journal line #' . $journalLine->id . '.');

        return self::SUCCESS;
    }

    private function unmatch(BankReconciliationService $service): int
    {
        $line = BankStatementLine::findOrFail((int) $this->option('unmatch'));
        $service->unmatch($line);
        $this->info('Statement line #' . $line->id . ' is unmatched again.');

        return self::SUCCESS;
    }

    private function ignore(BankReconciliationService $service): int
    {
        $line = BankStatementLine::findOrFail((int) $this->option('ignore'));
        $service->ignore($line, (string) $this->option('reason'));
        $this->info('Statement line #' . $line->id . ' set aside.');

        return self::SUCCESS;
    }
}
