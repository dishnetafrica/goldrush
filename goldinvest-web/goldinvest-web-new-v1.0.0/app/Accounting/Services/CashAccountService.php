<?php

namespace App\Accounting\Services;

use App\Accounting\Exceptions\AccountingException;
use App\Accounting\Ledger\AccountType;
use App\Accounting\Models\Account;
use App\Accounting\Models\CashAccount;
use App\Accounting\Security\AccountingPermission;
use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;

/**
 * Creates cash boxes and bank accounts, each with its own GL account.
 *
 * The GL account is created here rather than chosen, because the one-to-one
 * relationship is the point: an account's balance is its GL account's balance,
 * and nothing else. Letting two cash accounts share a GL account would put the
 * company back in the position of having a figure that needs reconciling
 * against itself.
 */
class CashAccountService
{
    /** Cash boxes hang under 1000, banks under 1010. 1090 is suspense and is not available. */
    private const RANGES = [
        CashAccount::TYPE_CASH => ['parent' => '1000', 'from' => 1001, 'to' => 1009],
        CashAccount::TYPE_BANK => ['parent' => '1010', 'from' => 1011, 'to' => 1089],
    ];

    public function create(array $attributes, ?Admin $actor = null): CashAccount
    {
        AccountingPermission::assert($actor, AccountingPermission::CASH_MANAGE);

        $type = $attributes['type'] ?? CashAccount::TYPE_CASH;

        if (! isset(self::RANGES[$type])) {
            throw new AccountingException('A cash account is either cash or bank, not "' . $type . '".');
        }

        $name = trim((string) ($attributes['name'] ?? ''));

        if ($name === '') {
            throw new AccountingException('A cash account needs a name.');
        }

        $code = $attributes['code'] ?? $this->slug($name);

        if (CashAccount::where('code', $code)->exists()) {
            throw new AccountingException('A cash account with code ' . $code . ' already exists.');
        }

        return DB::transaction(function () use ($type, $name, $code, $attributes, $actor) {
            $glCode = $this->nextGlCode($type);
            $parent = Account::where('code', self::RANGES[$type]['parent'])->first();

            $glAccount = Account::create([
                'code'           => $glCode,
                'name'           => $name,
                'type'           => AccountType::ASSET,
                'normal_balance' => AccountType::DEBIT,
                'parent_id'      => $parent?->id,
                'control_of'     => Account::CONTROL_CASH,
                'currency_code'  => $attributes['currency_code'] ?? 'USD',
                'active'         => true,
                'description'    => ucfirst($type) . ' account: ' . $name,
            ]);

            return CashAccount::create([
                'code'            => $code,
                'name'            => $name,
                'type'            => $type,
                'gl_account_id'   => $glAccount->id,
                'currency_code'   => $attributes['currency_code'] ?? 'USD',
                'bank_name'       => $attributes['bank_name'] ?? null,
                'account_ref'     => $attributes['account_ref'] ?? null,
                'allow_negative'  => (bool) ($attributes['allow_negative'] ?? false),
                'overdraft_limit' => (float) ($attributes['overdraft_limit'] ?? 0),
                'active'          => true,
                'notes'           => $attributes['notes'] ?? null,
                'created_by'      => $actor?->id,
            ]);
        });
    }

    /**
     * The next free GL code in this type's range.
     *
     * Running out is a real possibility for cash boxes, which have only nine
     * codes, so it fails loudly rather than silently spilling into a range that
     * means something else.
     */
    private function nextGlCode(string $type): string
    {
        $range = self::RANGES[$type];

        for ($code = $range['from']; $code <= $range['to']; $code++) {
            if (! Account::where('code', (string) $code)->exists()) {
                return (string) $code;
            }
        }

        throw new AccountingException(
            'No GL account codes left for ' . $type . ' accounts (range ' . $range['from'] . '-' . $range['to'] . ').'
        );
    }

    private function slug(string $name): string
    {
        $slug = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $name));

        return trim(substr($slug, 0, 30), '-');
    }
}
