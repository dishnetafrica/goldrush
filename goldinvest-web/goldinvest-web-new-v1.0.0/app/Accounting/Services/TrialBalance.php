<?php

namespace App\Accounting\Services;

use App\Accounting\Ledger\AccountType;
use App\Accounting\Models\Account;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\Journal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The trial balance: every account's debits and credits, and the one check that
 * says whether the books hold together at all.
 *
 * Reversed journals are included, both the original and its reversal, because
 * that is what actually happened. They cancel each other out, which is the
 * point — hiding them would make the ledger look tidier than the truth.
 */
class TrialBalance
{
    private const EPSILON = 0.00000001;

    /**
     * @param  string|null  $asAt  include journals up to and including this date
     * @param  int|null  $periodId  restrict to one period instead of everything to date
     */
    public function build(?string $asAt = null, ?int $periodId = null): array
    {
        $query = DB::table('journal_lines')
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->select(
                'accounts.id', 'accounts.code', 'accounts.name', 'accounts.type',
                'accounts.normal_balance', 'accounts.control_of',
                DB::raw('SUM(journal_lines.debit) as debits'),
                DB::raw('SUM(journal_lines.credit) as credits')
            )
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type',
                'accounts.normal_balance', 'accounts.control_of')
            ->orderBy('accounts.code');

        if ($asAt) {
            $query->whereDate('journals.journal_date', '<=', Carbon::parse($asAt)->toDateString());
        }

        if ($periodId) {
            $query->where('journals.accounting_period_id', $periodId);
        }

        $rows = [];
        $totalDebits = 0.0;
        $totalCredits = 0.0;
        $byType = [];

        foreach ($query->get() as $row) {
            $debits = round((float) $row->debits, 8);
            $credits = round((float) $row->credits, 8);

            $balance = $row->normal_balance === AccountType::DEBIT
                ? round($debits - $credits, 8)
                : round($credits - $debits, 8);

            $totalDebits += $debits;
            $totalCredits += $credits;
            $byType[$row->type] = round(($byType[$row->type] ?? 0) + $balance, 8);

            $rows[] = [
                'code'       => $row->code,
                'name'       => $row->name,
                'type'       => $row->type,
                'control_of' => $row->control_of,
                'debits'     => $debits,
                'credits'    => $credits,
                'balance'    => $balance,
            ];
        }

        $difference = round($totalDebits - $totalCredits, 8);

        return [
            'as_at'         => $asAt,
            'period'        => $periodId ? AccountingPeriod::find($periodId)?->code : 'all periods',
            'rows'          => $rows,
            'total_debits'  => round($totalDebits, 8),
            'total_credits' => round($totalCredits, 8),
            'difference'    => $difference,
            'balanced'      => abs($difference) <= self::EPSILON,
            'by_type'       => $byType,
            'journals'      => $this->journalCounts($asAt, $periodId),
        ];
    }

    /** The balance of one account, for a reconciliation to compare against. */
    public function balanceOf(string $code, ?string $asAt = null): float
    {
        $account = Account::where('code', $code)->first();

        if (! $account) {
            return 0.0;
        }

        $query = DB::table('journal_lines')
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->where('journal_lines.account_id', $account->id);

        if ($asAt) {
            $query->whereDate('journals.journal_date', '<=', Carbon::parse($asAt)->toDateString());
        }

        $sums = $query->selectRaw('SUM(journal_lines.debit) as d, SUM(journal_lines.credit) as c')->first();

        return $account->signedBalance((float) ($sums->d ?? 0), (float) ($sums->c ?? 0));
    }

    private function journalCounts(?string $asAt, ?int $periodId): array
    {
        $query = Journal::query();

        if ($asAt) {
            $query->whereDate('journal_date', '<=', Carbon::parse($asAt)->toDateString());
        }

        if ($periodId) {
            $query->where('accounting_period_id', $periodId);
        }

        return [
            'posted'   => (clone $query)->where('status', Journal::POSTED)->count(),
            'reversed' => (clone $query)->where('status', Journal::REVERSED)->count(),
            'total'    => $query->count(),
        ];
    }
}
