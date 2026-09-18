<?php

namespace App\Accounting\Services;

use App\Accounting\Ledger\AccountType;
use App\Accounting\Models\Account;

/**
 * The company's chart of accounts, as approved for Phase 3.
 *
 * Two of these matter more than the rest. 2000 and 2010 are control accounts:
 * their balances must equal the investor ledger's capital and profit buckets
 * exactly, and that single equality is what stops the company's books and the
 * investors' statements drifting apart.
 *
 * 1090 matters for a different reason. The company holds money whose origin has
 * not been established — an investor credit with no recorded receipt, and two
 * gold purchases paid outside the system. Suspense is where that sits, visibly,
 * until somebody produces the evidence. It is deliberately not Owner's Capital
 * or a Director's Loan, because nobody has yet shown that it is either.
 */
class ChartOfAccounts
{
    public const INVESTOR_CAPITAL_PAYABLE = '2000';
    public const INVESTOR_PROFIT_PAYABLE  = '2010';
    public const SUSPENSE                 = '1090';

    /** @return array<int, array<string, mixed>> */
    public static function definition(): array
    {
        return [
            // Assets
            ['1000', 'Cash on Hand', AccountType::ASSET, Account::CONTROL_CASH, 'Physical cash held by the company'],
            ['1010', 'Bank Accounts', AccountType::ASSET, Account::CONTROL_CASH, 'Balances at banks'],
            ['1090', 'Unidentified Receipts (Suspense)', AccountType::ASSET, null,
                'Money whose source or location has not been established. Must be resolved, never left to settle.'],
            ['1100', 'Gold Inventory - Unrefined', AccountType::ASSET, Account::CONTROL_INVENTORY,
                'Gold as purchased, before refining'],
            ['1110', 'Gold Inventory - Refined', AccountType::ASSET, Account::CONTROL_INVENTORY,
                'Refined gold held for sale, at cost including capitalised processing'],
            ['1300', 'Receivables from Buyers', AccountType::ASSET, null, 'Gold sold but not yet settled'],

            // Liabilities
            ['2000', 'Investor Capital Payable', AccountType::LIABILITY, Account::CONTROL_INVESTOR_CAPITAL,
                'Investor capital owed. Must equal the investor ledger available plus committed buckets.'],
            ['2010', 'Investor Profit Payable', AccountType::LIABILITY, Account::CONTROL_INVESTOR_PROFIT,
                'Investor profit owed. Must equal the investor ledger profit bucket.'],
            ['2100', 'Accrued Expenses Payable', AccountType::LIABILITY, null, 'Costs incurred but not yet paid'],
            ['2200', 'Withdrawals Payable', AccountType::LIABILITY, null,
                'Withdrawals requested and reserved, not yet paid out'],

            // Equity
            ['3000', 'Owner\'s Capital', AccountType::EQUITY, null, 'Capital contributed by the owners'],
            ['3100', 'Retained Earnings', AccountType::EQUITY, null, 'Accumulated result of prior periods'],

            // Revenue
            ['4000', 'Gold Sales Revenue', AccountType::REVENUE, null, 'Proceeds from selling gold'],
            ['4100', 'FX Gain', AccountType::REVENUE, null, 'Gains on currency movement'],

            // Cost of goods sold
            ['5000', 'Cost of Gold Sold', AccountType::COGS, null,
                'Cost of the grams actually sold, including the cost of grams lost in refining'],

            // Deal expenses
            ['6000', 'Transport', AccountType::EXPENSE, null, 'Moving gold between locations'],
            ['6010', 'Refining and Processing', AccountType::EXPENSE, null,
                'Processing costs not capitalised into inventory'],
            ['6020', 'Assay and Testing', AccountType::EXPENSE, null, 'Purity verification'],
            ['6030', 'Security', AccountType::EXPENSE, null, 'Guarding and secure movement'],
            ['6040', 'Travel', AccountType::EXPENSE, null, 'Travel attributable to a deal'],
            ['6050', 'Brokerage and Commission', AccountType::EXPENSE, null, 'Fees paid to intermediaries'],
            ['6060', 'Packaging and Handling', AccountType::EXPENSE, null,
                'Packaging not capitalised into inventory'],
            ['6070', 'Storage and Vaulting', AccountType::EXPENSE, null, 'Holding gold securely'],
            ['6100', 'Operating Expenses', AccountType::EXPENSE, null, 'Running costs not attributable to a deal'],
            ['6900', 'FX Loss', AccountType::EXPENSE, null, 'Losses on currency movement'],

            // Cost of investor capital, reported separately from operating costs
            ['7000', 'Investor Profit Share', AccountType::EXPENSE, null,
                'The investors\' agreed share of trading results. Reported separately from operating and deal expenses.'],
        ];
    }

    /**
     * Creates any account that is missing, leaving existing ones alone.
     *
     * Safe to run repeatedly. It never edits an account that is already in use,
     * because renaming or retyping an account with postings behind it would
     * silently rewrite history.
     */
    public function install(): array
    {
        $created = [];
        $existing = [];

        foreach (self::definition() as [$code, $name, $type, $control, $description]) {
            $account = Account::where('code', $code)->first();

            if ($account) {
                $existing[] = $code;
                continue;
            }

            Account::create([
                'code'           => $code,
                'name'           => $name,
                'type'           => $type,
                'normal_balance' => AccountType::normalBalance($type),
                'control_of'     => $control,
                'currency_code'  => 'USD',
                'active'         => true,
                'description'    => $description,
            ]);

            $created[] = $code;
        }

        return ['created' => $created, 'existing' => $existing];
    }
}
