<?php

namespace App\Console\Commands;

use App\Accounting\Models\CashAccount;
use App\Accounting\Services\CashAccountService;
use App\Investor\Support\Money;
use Illuminate\Console\Command;

/**
 * Creates and lists the company's cash boxes and bank accounts.
 */
class CashAccountCommand extends Command
{
    protected $signature = 'cash:account
                            {name? : name of the account to create}
                            {--type=bank : cash|bank}
                            {--code= : short code, derived from the name if omitted}
                            {--currency=USD}
                            {--bank= : bank name, for bank accounts}
                            {--ref= : account number or identifier}
                            {--allow-negative : permit an overdraft}
                            {--overdraft=0 : overdraft limit when negatives are permitted}
                            {--list : list the accounts and their balances}';

    protected $description = 'Create or list company cash and bank accounts';

    public function handle(CashAccountService $service): int
    {
        if ($this->option('list') || ! $this->argument('name')) {
            return $this->list();
        }

        try {
            $account = $service->create([
                'name'            => $this->argument('name'),
                'type'            => $this->option('type'),
                'code'            => $this->option('code'),
                'currency_code'   => strtoupper($this->option('currency')),
                'bank_name'       => $this->option('bank'),
                'account_ref'     => $this->option('ref'),
                'allow_negative'  => (bool) $this->option('allow-negative'),
                'overdraft_limit' => (float) $this->option('overdraft'),
            ]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Created ' . $account->label() . ' (' . $account->type . ')');
        $this->line('  GL account: ' . $account->glAccount->label());
        $this->line('  Balance   : ' . Money::format($account->balance()) . ' ' . $account->currency_code);

        return self::SUCCESS;
    }

    private function list(): int
    {
        $accounts = CashAccount::with('glAccount')->orderBy('code')->get();

        if ($accounts->isEmpty()) {
            $this->warn('No cash or bank accounts yet. Create one with: cash:account "Main Bank" --type=bank');

            return self::SUCCESS;
        }

        $this->table(
            ['Code', 'Name', 'Type', 'GL', 'Currency', 'Balance', 'Floor'],
            $accounts->map(fn (CashAccount $a) => [
                $a->code, $a->name, $a->type, $a->glAccount->code, $a->currency_code,
                Money::format($a->balance()), Money::format($a->floor()),
            ])->all()
        );

        $this->line('  Total held: ' . Money::format($accounts->sum(fn (CashAccount $a) => $a->balance())));

        return self::SUCCESS;
    }
}
