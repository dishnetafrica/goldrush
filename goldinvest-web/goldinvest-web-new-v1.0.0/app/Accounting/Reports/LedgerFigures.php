<?php

namespace App\Accounting\Reports;

use App\Accounting\Ledger\AccountType;
use App\Accounting\Models\Account;
use App\Accounting\Models\Journal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The one way a report reads the general ledger.
 *
 * Every figure is a sum of posted journal lines, signed the way the account's
 * own type reads it: an asset or expense is positive when debits exceed
 * credits, a liability, equity or revenue account when credits exceed debits.
 * Nothing here is cached, computed elsewhere, or read from anything but
 * journal_lines joined to journals and accounts.
 */
class LedgerFigures
{
    /** The signed balance of one account, everything to a date. */
    public function balanceAt(string $code, ?string $asAt = null): float
    {
        $account = Account::where('code', $code)->first();

        if (! $account) {
            return 0.0;
        }

        $query = $this->lines()->where('accounts.code', $code);

        if ($asAt) {
            $query->whereDate('journals.journal_date', '<=', $asAt);
        }

        $sums = $query->selectRaw('COALESCE(SUM(journal_lines.debit),0) as d, COALESCE(SUM(journal_lines.credit),0) as c')->first();

        return $account->signedBalance((float) $sums->d, (float) $sums->c);
    }

    /** Debits, credits and the signed net movement of one account within a scope. */
    public function movement(string $code, ReportScope $scope): array
    {
        $account = Account::where('code', $code)->first();

        if (! $account) {
            return ['debits' => 0.0, 'credits' => 0.0, 'net' => 0.0];
        }

        $sums = $this->scoped($scope)->where('accounts.code', $code)
            ->selectRaw('COALESCE(SUM(journal_lines.debit),0) as d, COALESCE(SUM(journal_lines.credit),0) as c')->first();

        return [
            'debits'  => round((float) $sums->d, 8),
            'credits' => round((float) $sums->c, 8),
            'net'     => $account->signedBalance((float) $sums->d, (float) $sums->c),
        ];
    }

    /** Signed net movement across a range of account codes of one type (e.g. 6000-6899). */
    public function rangeMovement(string $from, string $to, string $type, ReportScope $scope): array
    {
        $rows = $this->scoped($scope)
            ->whereBetween('accounts.code', [$from, $to])
            ->where('accounts.type', $type)
            ->groupBy('accounts.code', 'accounts.name', 'accounts.normal_balance')
            ->select('accounts.code', 'accounts.name', 'accounts.normal_balance',
                DB::raw('SUM(journal_lines.debit) as d'), DB::raw('SUM(journal_lines.credit) as c'))
            ->orderBy('accounts.code')
            ->get();

        $accounts = [];

        foreach ($rows as $row) {
            $net = $row->normal_balance === AccountType::DEBIT
                ? round((float) $row->d - (float) $row->c, 8)
                : round((float) $row->c - (float) $row->d, 8);

            if (abs($net) <= Control::EPSILON) {
                continue;
            }

            $accounts[] = ['code' => $row->code, 'name' => $row->name, 'amount_usd' => $net];
        }

        return ['accounts' => $accounts, 'total' => round(array_sum(array_column($accounts, 'amount_usd')), 8)];
    }

    /**
     * Every account with its opening balance, movements in scope and closing
     * balance. The trial balance's rows.
     */
    public function accountsTable(ReportScope $scope): array
    {
        $opening = $this->balances($scope->openingDate(), true);
        $closing = $this->balances($scope->end()->toDateString(), false);

        $movements = $this->scoped($scope)
            ->groupBy('accounts.code')
            ->select('accounts.code', DB::raw('SUM(journal_lines.debit) as d'), DB::raw('SUM(journal_lines.credit) as c'))
            ->get()->keyBy('code');

        $rows = [];

        foreach (Account::orderBy('code')->get() as $account) {
            $open = $opening[$account->code] ?? 0.0;
            $d = round((float) ($movements[$account->code]->d ?? 0), 8);
            $c = round((float) ($movements[$account->code]->c ?? 0), 8);
            $close = $closing[$account->code] ?? 0.0;

            $rows[] = [
                'code'           => $account->code,
                'name'           => $account->name,
                'type'           => $account->type,
                'normal_balance' => $account->normal_balance,
                'control_of'     => $account->control_of,
                'opening'        => $open,
                'debits'         => $d,
                'credits'        => $c,
                'closing'        => $close,
                // Opening plus movements must reproduce the closing balance read
                // independently; if it does not, a line is dated outside its period.
                'consistent'     => abs(round($open + ($account->isDebitNormal() ? $d - $c : $c - $d), 8) - $close) <= Control::EPSILON,
            ];
        }

        return $rows;
    }

    /** Signed balances of every account as at a date, keyed by code. Null date means nothing yet. */
    public function balances(?string $asAt, bool $nullMeansNothing = false): array
    {
        if ($asAt === null && $nullMeansNothing) {
            return [];
        }

        $query = $this->lines()
            ->groupBy('accounts.code', 'accounts.normal_balance')
            ->select('accounts.code', 'accounts.normal_balance',
                DB::raw('SUM(journal_lines.debit) as d'), DB::raw('SUM(journal_lines.credit) as c'));

        if ($asAt) {
            $query->whereDate('journals.journal_date', '<=', $asAt);
        }

        $out = [];

        foreach ($query->get() as $row) {
            $out[$row->code] = $row->normal_balance === AccountType::DEBIT
                ? round((float) $row->d - (float) $row->c, 8)
                : round((float) $row->c - (float) $row->d, 8);
        }

        return $out;
    }

    /** Balances as at a date grouped by account type, with names, every account present. */
    public function balancesByType(?string $asAt): array
    {
        $balances = $this->balances($asAt);
        $byType = [];

        foreach (Account::orderBy('code')->get() as $account) {
            $byType[$account->type][] = [
                'code'       => $account->code,
                'name'       => $account->name,
                'control_of' => $account->control_of,
                'balance'    => $balances[$account->code] ?? 0.0,
            ];
        }

        return $byType;
    }

    /** Per-lot signed balance of one account (inventory and COGS per deal). */
    public function lotBalance(string $code, int $lotId, ?string $asAt = null): float
    {
        $account = Account::where('code', $code)->first();

        if (! $account) {
            return 0.0;
        }

        $query = $this->lines()->where('accounts.code', $code)->where('journal_lines.gold_lot_id', $lotId);

        if ($asAt) {
            $query->whereDate('journals.journal_date', '<=', $asAt);
        }

        $sums = $query->selectRaw('COALESCE(SUM(journal_lines.debit),0) as d, COALESCE(SUM(journal_lines.credit),0) as c')->first();

        return $account->signedBalance((float) $sums->d, (float) $sums->c);
    }

    /** Every journal in scope with a line on a cash-control account, with all its lines. */
    public function cashJournals(ReportScope $scope): array
    {
        $ids = $this->scoped($scope)
            ->where('accounts.control_of', Account::CONTROL_CASH)
            ->distinct()->pluck('journals.id')->all();

        if ($ids === []) {
            return [];
        }

        $lines = DB::table('journal_lines')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereIn('journal_lines.journal_id', $ids)
            ->select('journal_lines.journal_id', 'journal_lines.debit', 'journal_lines.credit',
                'accounts.code', 'accounts.name', 'accounts.type', 'accounts.control_of')
            ->orderBy('journal_lines.journal_id')->orderBy('journal_lines.line_no')
            ->get()->groupBy('journal_id');

        $journals = Journal::whereIn('id', $ids)->orderBy('journal_date')->orderBy('id')->get();

        $out = [];

        foreach ($journals as $journal) {
            $out[] = [
                'journal'  => $journal,
                'lines'    => $lines[$journal->id]->all(),
            ];
        }

        return $out;
    }

    public function journalCounts(ReportScope $scope): array
    {
        $query = Journal::query();

        if ($scope->period) {
            $query->where('accounting_period_id', $scope->period->id);
        } else {
            if ($scope->from) {
                $query->whereDate('journal_date', '>=', $scope->from);
            }
            $query->whereDate('journal_date', '<=', $scope->end()->toDateString());
        }

        return [
            'posted'   => (clone $query)->where('status', Journal::POSTED)->count(),
            'reversed' => (clone $query)->where('status', Journal::REVERSED)->count(),
            'total'    => $query->count(),
        ];
    }

    public function totalJournals(?string $asAt = null): int
    {
        $query = Journal::query();

        if ($asAt) {
            $query->whereDate('journal_date', '<=', $asAt);
        }

        return $query->count();
    }

    private function scoped(ReportScope $scope): \Illuminate\Database\Query\Builder
    {
        $query = $this->lines();

        if ($scope->period) {
            $query->where('journals.accounting_period_id', $scope->period->id);
        } else {
            if ($scope->from) {
                $query->whereDate('journals.journal_date', '>=', $scope->from);
            }
            $query->whereDate('journals.journal_date', '<=', $scope->end()->toDateString());
        }

        return $query;
    }

    private function lines(): \Illuminate\Database\Query\Builder
    {
        return DB::table('journal_lines')
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id');
    }
}
