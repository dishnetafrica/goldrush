<?php

namespace App\Accounting\Ledger;

use InvalidArgumentException;

/**
 * The six kinds of account, and which way each one normally leans.
 *
 * Normal balance is what turns a pile of debits and credits into a readable
 * figure: an asset with more debits than credits has a positive balance, a
 * liability with more credits than debits likewise. Get this wrong and every
 * report reads backwards.
 */
final class AccountType
{
    public const ASSET     = 'asset';
    public const LIABILITY = 'liability';
    public const EQUITY    = 'equity';
    public const REVENUE   = 'revenue';
    public const COGS      = 'cogs';
    public const EXPENSE   = 'expense';

    public const DEBIT  = 'debit';
    public const CREDIT = 'credit';

    private const NORMAL = [
        self::ASSET     => self::DEBIT,
        self::LIABILITY => self::CREDIT,
        self::EQUITY    => self::CREDIT,
        self::REVENUE   => self::CREDIT,
        self::COGS      => self::DEBIT,
        self::EXPENSE   => self::DEBIT,
    ];

    public static function all(): array
    {
        return array_keys(self::NORMAL);
    }

    public static function assert(string $type): void
    {
        if (! isset(self::NORMAL[$type])) {
            throw new InvalidArgumentException('Unknown account type: ' . $type);
        }
    }

    public static function normalBalance(string $type): string
    {
        self::assert($type);

        return self::NORMAL[$type];
    }

    /** Balance sheet accounts carry forward; the rest close into retained earnings. */
    public static function isBalanceSheet(string $type): bool
    {
        return in_array($type, [self::ASSET, self::LIABILITY, self::EQUITY], true);
    }

    public static function label(string $type): string
    {
        return match ($type) {
            self::ASSET     => 'Assets',
            self::LIABILITY => 'Liabilities',
            self::EQUITY    => 'Equity',
            self::REVENUE   => 'Revenue',
            self::COGS      => 'Cost of Goods Sold',
            self::EXPENSE   => 'Expenses',
            default         => $type,
        };
    }
}
