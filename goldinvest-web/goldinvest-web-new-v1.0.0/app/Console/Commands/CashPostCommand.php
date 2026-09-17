<?php

namespace App\Console\Commands;

use App\Accounting\Models\CashAccount;
use App\Accounting\Services\CashService;
use App\Investor\Support\Money;
use Illuminate\Console\Command;

/**
 * Records money arriving at, leaving, or moving between the company's accounts.
 * Each one posts a balanced journal and nothing else.
 */
class CashPostCommand extends Command
{
    protected $signature = 'cash:post
                            {type : receipt|payment|transfer}
                            {account : cash account code}
                            {--to= : for a transfer, the receiving cash account code}
                            {--amount= }
                            {--contra= : GL account code the money came from or went to}
                            {--memo= }
                            {--date= : defaults to today}
                            {--dry-run : show what would be posted and change nothing}';

    protected $description = 'Post a cash receipt, payment or transfer';

    public function handle(CashService $cash): int
    {
        $account = CashAccount::where('code', $this->argument('account'))->first();

        if (! $account) {
            $this->error('No cash account with code ' . $this->argument('account'));

            return self::FAILURE;
        }

        $amount = (float) $this->option('amount');

        if ($amount <= 0) {
            $this->error('Give an amount: --amount=1000');

            return self::FAILURE;
        }

        $context = array_filter([
            'memo' => $this->option('memo'),
            'date' => $this->option('date'),
        ]);

        if ($this->option('dry-run')) {
            $this->line('Would post ' . $this->argument('type') . ' of ' . Money::format($amount)
                . ' on ' . $account->label() . ' (balance now ' . Money::format($account->balance()) . ')');

            return self::SUCCESS;
        }

        try {
            $journal = match ($this->argument('type')) {
                'receipt'  => $cash->receipt($account, $amount, $this->requireContra(), $context),
                'payment'  => $cash->payment($account, $amount, $this->requireContra(), $context),
                'transfer' => $cash->transfer($account, $this->requireDestination(), $amount, $context),
                default    => throw new \InvalidArgumentException('Type must be receipt, payment or transfer.'),
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Posted ' . $journal->reference . ' - ' . $journal->memo);
        $this->table(
            ['Account', 'Debit', 'Credit'],
            $journal->lines->map(fn ($l) => [
                $l->account->label(), Money::format($l->debit), Money::format($l->credit),
            ])->all()
        );
        $this->line('  ' . $account->label() . ' balance: ' . Money::format($account->balance()));

        return self::SUCCESS;
    }

    private function requireContra(): string
    {
        if (! $this->option('contra')) {
            throw new \InvalidArgumentException('Say where the money came from or went to: --contra=2000');
        }

        return $this->option('contra');
    }

    private function requireDestination(): CashAccount
    {
        $to = CashAccount::where('code', $this->option('to'))->first();

        if (! $to) {
            throw new \InvalidArgumentException('A transfer needs a destination: --to=<cash account code>');
        }

        return $to;
    }
}
