<?php

namespace App\Console\Commands;

use App\Accounting\Exceptions\AccountingException;
use App\Accounting\Models\Account;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\BankStatementLine;
use App\Accounting\Models\CashAccount;
use App\Accounting\Models\Journal;
use App\Accounting\Security\AccountingPermission;
use App\Accounting\Services\CashAccountService;
use App\Accounting\Services\CashService;
use App\Accounting\Services\ExpenseWorkflow;
use App\Accounting\Services\GoldTradingPoster;
use App\Accounting\Services\JournalPoster;
use App\Accounting\Services\PeriodCloseService;
use App\Accounting\Services\RealizedResultRecorder;
use App\Accounting\Services\RealizedTradingResult;
use App\Accounting\Services\TrialBalance;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\GoldProcessing;
use App\GoldTrading\Models\GoldSale;
use App\GoldTrading\Models\LotResult;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * The Phase 3F period-close acceptance run.
 *
 * Builds a month of its own — a period nobody else uses, in the past — fills
 * it with complete deals through the posting services, and then tries to close
 * it in every way it must not be closed before closing it the one way it may.
 * Everything happens inside a transaction that is always rolled back, and the
 * real September period is never touched, not even transiently.
 *
 * The investor allocation is deliberately absent. This suite proves that a
 * close records the company's own result and refuses to invent a share of it.
 */
class PeriodSelfTestCommand extends Command
{
    protected $signature = 'period:selftest {--skip-regression : period close tests only}';

    protected $description = 'Verify period close controls, evidence, immutability and reopening';

    private array $results = [];
    private const EPS = 0.00000001;

    private ?AccountingPeriod $period = null;
    private string $date;
    private ExpenseWorkflow $expenses;
    private GoldTradingPoster $gold;
    private ?CashAccount $bank = null;
    private ?Admin $super = null;

    public function handle(
        PeriodCloseService $close,
        RealizedTradingResult $realized,
        RealizedResultRecorder $recorder,
        ExpenseWorkflow $expenses,
        GoldTradingPoster $gold,
        JournalPoster $poster,
        CashAccountService $accounts,
        CashService $cash,
        TrialBalance $trialBalance,
    ): int {
        // Artisan resolves a command once per process, so a suite invoked twice in
        // the same run would otherwise report the first run's results alongside
        // the second's and count them all.
        $this->results = [];

        $this->line('Period close self-test (Phase 3F, close only)');
        $this->line(str_repeat('-', 60));

        $this->expenses = $expenses;
        $this->gold = $gold;

        $investorsBefore = $this->investorSnapshot();
        $suspenseBefore = $this->accountBalance('1090');
        $realPeriods = AccountingPeriod::orderBy('code')->get(['code', 'status', 'close_reference'])->toArray();

        DB::beginTransaction();

        try {
            $this->super = $this->superAdmin();

            // A month of its own, well before any real one, so that nothing this
            // run does can be mistaken for a close of the company's actual books.
            $this->period = AccountingPeriod::create([
                'code' => 'ST-2025-01', 'starts_on' => '2025-01-01', 'ends_on' => '2025-01-31',
                'status' => AccountingPeriod::OPEN,
            ]);
            $this->date = '2025-01-10';

            $this->bank = $accounts->create([
                'name' => 'Period Close Bank', 'type' => CashAccount::TYPE_BANK, 'code' => 'PC-BANK',
            ]);
            $cash->receipt($this->bank, 20000.00, '3000', ['date' => $this->date, 'memo' => 'Self-test funding']);

            $lotA = $this->tradedLot(['cost' => 2000.00, 'grams' => 25.0, 'waste' => 2.0,
                'capitalised' => 100.00, 'sell_grams' => 23.0, 'price' => 130.00]);
            $lotB = $this->tradedLot(['cost' => 1500.00, 'grams' => 20.0, 'waste' => 1.0,
                'capitalised' => 0.0, 'sell_grams' => 19.0, 'price' => 100.00,
                'expenses' => [['transport', 60.00]]]);
            $inProgress = $this->tradedLot(['cost' => 800.00, 'grams' => 10.0, 'waste' => 0.0,
                'capitalised' => 0.0, 'sell_grams' => 4.0, 'price' => 120.00]);

            $this->interimBlocks($close, $recorder, $lotA, $lotB);
            $this->pendingExpenseBlocks($close, $lotB);
            $this->imbalanceBlocks($close);
            $this->unmatchedBankLineBlocks($close);
            $this->controls($close);
            $this->closes($close, $realized, $poster, $inProgress);
            $this->evidenceImmutable();
            $this->reopening($close, $poster);
            $this->trialBalance($trialBalance);
        } catch (\Throwable $e) {
            $this->check('Unexpected failure', false,
                $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
        } finally {
            DB::rollBack();
        }

        $this->untouched($investorsBefore, $suspenseBefore, $realPeriods);

        $this->table(
            ['#', 'Test', 'Result', 'Detail'],
            array_map(fn ($r, $i) => [$i + 1, $r[0], $r[1] ? 'PASS' : 'FAIL', $r[2]],
                $this->results, array_keys($this->results))
        );

        $failed = array_filter($this->results, fn ($r) => ! $r[1]);
        $this->line('  ' . (count($this->results) - count($failed)) . ' of ' . count($this->results) . ' passed.');
        $this->line('  Everything was rolled back; no period, lot, journal or balance remains.');

        $regressionOk = true;

        if (! $this->option('skip-regression')) {
            foreach ([
                'Phase 1' => ['ledger:check', ['user' => 'bhavin']],
                'Phase 2' => ['investor:selftest', ['user' => 'bhavin']],
                'Phase 3A' => ['accounting:selftest', ['--skip-regression' => true]],
                'Phase 3B' => ['cash:selftest', ['--skip-regression' => true]],
                'Phase 3C and D4' => ['gold:accounting-selftest', ['--skip-regression' => true]],
                'Phase 3D' => ['expense:selftest', ['--skip-regression' => true]],
                'Phase 3E' => ['trading:selftest', ['--skip-regression' => true]],
            ] as $label => [$command, $args]) {
                $this->newLine();
                $this->line($label . ' regression');
                $regressionOk = $this->call($command, $args) === 0 && $regressionOk;
            }
        }

        if ($failed !== [] || ! $regressionOk) {
            $this->error('FAIL');

            return self::FAILURE;
        }

        $this->info('PASS');

        return self::SUCCESS;
    }

    /** 1-2. A sold-out deal whose result is still interim holds the month open. */
    private function interimBlocks(PeriodCloseService $close, RealizedResultRecorder $recorder, GoldLot $a, GoldLot $b): void
    {
        $blockers = $close->blockers($this->period);
        $mentionsA = count(array_filter($blockers, fn ($x) => str_contains($x, $a->lot_code))) === 1;
        $mentionsB = count(array_filter($blockers, fn ($x) => str_contains($x, $b->lot_code))) === 1;

        $this->check(
            'A period cannot close while a sold-out deal\'s result is still interim',
            $mentionsA && $mentionsB && $this->refused(fn () => $close->close($this->period, $this->super, 'Month end')),
            count($blockers) . ' blocker(s); both sold-out deals named, closing refused'
        );

        $recorder->finaliseExpenses($a);
        $recorder->record($a, $this->super);
        $recorder->finaliseExpenses($b);
        $recorder->record($b, $this->super);

        $after = $close->blockers($this->period);

        $this->check(
            'Recording the results clears that blocker, and a deal still holding gold does not raise one',
            $after === [],
            'no blockers; the deal holding 6 g carries forward to the month it sells in'
        );
    }

    /** 3. A cost claimed for the month but not in its books holds it open. */
    private function pendingExpenseBlocks(PeriodCloseService $close, GoldLot $lot): void
    {
        $late = $this->expenses->draft([
            'expense_date' => $this->date, 'category' => 'security',
            'description'  => 'Approved but unposted, self-test', 'amount_local' => 45.00,
        ]);
        $this->expenses->submit($late);
        $this->expenses->approve($late, $this->super);

        $blockers = $close->blockers($this->period);
        $named = count(array_filter($blockers, fn ($x) => str_contains($x, $late->reference))) === 1;

        $refused = $this->refused(fn () => $close->close($this->period, $this->super, 'Month end'));

        // Reject it rather than post it: the point is that it was in the way, and
        // that either resolution clears it.
        $this->expenses->reject($late, 'Duplicate of an invoice already posted', $this->super);

        $this->check(
            'A period cannot close with an approved cost that has not reached the ledger',
            $named && $refused && $close->blockers($this->period) === [],
            $late->reference . ' blocked the close until it was resolved'
        );
    }

    /** 4. Books that do not balance cannot be stood behind. */
    private function imbalanceBlocks(PeriodCloseService $close): void
    {
        // The poster cannot produce an unbalanced journal, so the only way to
        // test the guard is to damage the books directly, the way a bad migration
        // or a hand edit would.
        $journal = Journal::where('accounting_period_id', $this->period->id)->first();
        $account = Account::where('code', '6100')->first();

        $id = DB::table('journal_lines')->insertGetId([
            'journal_id' => $journal->id, 'line_no' => 99, 'account_id' => $account->id,
            'debit' => 0.01, 'credit' => 0, 'memo' => 'self-test damage',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $blockers = $close->blockers($this->period);
        $named = count(array_filter($blockers, fn ($x) => str_contains($x, 'does not balance'))) === 1;
        $refused = $this->refused(fn () => $close->close($this->period, $this->super, 'Month end'));

        DB::table('journal_lines')->where('id', $id)->delete();

        $this->check(
            'A period cannot close while its trial balance does not balance',
            $named && $refused && $close->blockers($this->period) === [],
            'a 0.01 difference was enough to refuse the close'
        );
    }

    /** 5. Money the bank says moved that the books have not explained. */
    private function unmatchedBankLineBlocks(PeriodCloseService $close): void
    {
        $line = BankStatementLine::create([
            'cash_account_id' => $this->bank->id, 'statement_ref' => 'ST-3F', 'value_date' => $this->date,
            'description' => 'Bank charge, self-test', 'amount' => -25.00,
            'fingerprint' => hash('sha256', 'st-3f-' . uniqid('', true)),
            'status' => BankStatementLine::UNMATCHED,
        ]);

        $blockers = $close->blockers($this->period);
        $named = count(array_filter($blockers, fn ($x) => str_contains($x, 'unmatched'))) === 1;
        $refused = $this->refused(fn () => $close->close($this->period, $this->super, 'Month end'));

        $line->forceFill(['status' => BankStatementLine::IGNORED, 'ignore_reason' => 'Self-test: set aside'])->save();

        $this->check(
            'A period cannot close with an unmatched bank statement line in it',
            $named && $refused && $close->blockers($this->period) === [],
            'the 25.00 bank charge blocked the close until it was set aside'
        );
    }

    /** 6-7. Who may close, and not without saying why. */
    private function controls(PeriodCloseService $close): void
    {
        $stranger = new Admin();
        $stranger->id = -1;
        $stranger->username = 'selftest-no-permissions';

        $this->check(
            'Admin without permission cannot close a period',
            $this->refused(fn () => $close->close($this->period, $stranger, 'Month end'))
            && ! AccountingPermission::allows($stranger, AccountingPermission::PERIOD_CLOSE),
            'refused for an admin holding no grants'
        );

        $this->check(
            'Closing needs a reason or reference',
            $this->refused(fn () => $close->close($this->period, $this->super, ''))
            && $this->period->fresh()->status === AccountingPeriod::OPEN,
            'refused with nothing to record against the close'
        );
    }

    /** 8-12. The close itself, and what it records. */
    private function closes(PeriodCloseService $close, RealizedTradingResult $realized, JournalPoster $poster, GoldLot $inProgress): void
    {
        $company = $realized->forCompany(['period_id' => $this->period->id]);
        $expected = $company['trading_result_usd'];

        $closed = $close->close($this->period, $this->super, 'January 2025 month end, self-test');
        $snapshot = $closed->snapshot;

        $this->check(
            'A period closes when nothing in it is still moving',
            $closed->status === AccountingPeriod::CLOSED
            && str_starts_with((string) $closed->close_reference, 'PCL-')
            && (int) $closed->closed_by === (int) $this->super->id
            && $closed->closed_at !== null
            && $closed->close_reason === 'January 2025 month end, self-test',
            $closed->close_reference . ' by ' . $this->super->username . ' at '
            . $closed->closed_at->format('d M Y H:i') . ': "' . $closed->close_reason . '"'
        );

        $this->check(
            'The close records the company result as the ledger held it',
            abs((float) $snapshot['trading_result_usd'] - $expected) < self::EPS
            && abs((float) $snapshot['trading_revenue_usd'] - 5370.00) < self::EPS
            && abs((float) $snapshot['trading_cogs_usd'] - 3920.00) < self::EPS
            && abs((float) $snapshot['trading_expenses_usd'] - 60.00) < self::EPS
            && abs((float) $snapshot['trial_balance']['difference']) < self::EPS
            && count($snapshot['deals']) === 3,
            'revenue 5,370.00 - COGS 3,920.00 - expenses 60.00 = ' . Money::format((float) $snapshot['trading_result_usd'])
            . ' across 3 deals, trial balance difference 0.00000000'
        );

        $this->check(
            'The close does not decide an investor share, and says so',
            ! isset($snapshot['investor_profit_usd'])
            && str_contains((string) $snapshot['investor_allocation'], 'not determined')
            && LotResult::where('accounting_period_id', $this->period->id)->where('investor_profit_usd', '!=', 0)->doesntExist(),
            '"' . $snapshot['investor_allocation'] . '"'
        );

        $this->check(
            'A closed period refuses new postings',
            $this->refused(fn () => $poster->post([
                ['account' => '6100', 'debit' => 10.00],
                ['account' => $this->bank->glAccount, 'credit' => 10.00],
            ], ['date' => $this->date, 'memo' => 'Into a closed period'], $this->super)),
            'a posting dated ' . $this->date . ' was rejected'
        );

        $this->check(
            'A period cannot be closed twice',
            $this->refused(fn () => $close->close($this->period->fresh(), $this->super, 'Again'))
            && $this->period->fresh()->close_reference === $closed->close_reference,
            'second close refused; ' . $closed->close_reference . ' stands'
        );

        $r = $realized->forLot($inProgress->fresh());

        $this->check(
            'A deal still holding gold is unaffected by the close and carries forward',
            $r['trading_complete'] === false && abs($r['remaining_grams'] - 6.0) < 0.0001,
            $inProgress->lot_code . ' still holds ' . number_format($r['remaining_grams'], 4) . ' g'
        );
    }

    /** 13. What was closed cannot be quietly rewritten. */
    private function evidenceImmutable(): void
    {
        $period = $this->period->fresh();

        $this->check(
            'The close record and its snapshot are immutable',
            $this->refused(fn () => $period->update(['close_reason' => 'changed']))
            && $this->refused(fn () => $period->update(['snapshot' => ['trading_result_usd' => 1]]))
            && $this->refused(fn () => $period->update(['close_reference' => 'PCL-0']))
            && $period->fresh()->close_reason === 'January 2025 month end, self-test',
            'reason, snapshot and reference all refused; original stands'
        );
    }

    /** 14-16. Reopening, on the same terms as every other waived control. */
    private function reopening(PeriodCloseService $close, JournalPoster $poster): void
    {
        $period = $this->period->fresh();
        $reference = $period->close_reference;

        $stranger = new Admin();
        $stranger->id = -1;
        $stranger->username = 'selftest-not-super';

        $this->check(
            'Only a Super Admin may reopen, and only with a reason',
            $this->refused(fn () => $close->reopen($period, 'Late invoice', $stranger))
            && $this->refused(fn () => $close->reopen($period, '', $this->super), \Throwable::class)
            && $period->fresh()->status === AccountingPeriod::CLOSED,
            'a non Super Admin and an empty reason were both refused'
        );

        $close->reopen($period, 'A supplier invoice for January arrived in February', $this->super);
        $period->refresh();

        $this->check(
            'Reopening records who and why, and keeps the close as history',
            $period->status === AccountingPeriod::OPEN
            && (int) $period->reopened_by === (int) $this->super->id
            && $period->reopened_at !== null
            && $period->reopen_reason === 'A supplier invoice for January arrived in February'
            && $period->close_reference === $reference
            && $period->snapshot !== null,
            'open again; ' . $reference . ' and its snapshot retained'
        );

        $journal = $poster->post([
            ['account' => '6100', 'debit' => 10.00],
            ['account' => $this->bank->glAccount, 'credit' => 10.00],
        ], ['date' => $this->date, 'memo' => 'After reopening'], $this->super);

        $this->check(
            'A reopened period accepts postings again',
            $journal->balances() && (int) $journal->accounting_period_id === (int) $period->id,
            $journal->reference . ' posted into ' . $period->code
        );
    }

    /** 17. The books still balance. */
    private function trialBalance(TrialBalance $trialBalance): void
    {
        $tb = $trialBalance->build();

        $this->check(
            'Trial balance still balances',
            $tb['balanced'],
            'debits ' . Money::format($tb['total_debits']) . ' credits ' . Money::format($tb['total_credits'])
            . ' difference ' . Money::exact($tb['difference'])
        );
    }

    /** 18-20. Nothing outside this run moved — checked after the rollback. */
    private function untouched(array $investorsBefore, float $suspenseBefore, array $realPeriods): void
    {
        $this->check(
            'No investor balance or ledger entry moved',
            $investorsBefore === $this->investorSnapshot(),
            'wallets ' . Money::exact($investorsBefore['balance']) . ' / ' . Money::exact($investorsBefore['profit'])
            . ', ' . $investorsBefore['entries'] . ' ledger entries — unchanged'
        );

        $this->check(
            'Suspense is untouched and D2/D3 remain unresolved',
            abs($this->accountBalance('1090') - $suspenseBefore) < self::EPS
            && abs($this->accountBalance('1090')) < self::EPS,
            'account 1090 holds ' . Money::exact($this->accountBalance('1090'))
        );

        $now = AccountingPeriod::orderBy('code')->get(['code', 'status', 'close_reference'])->toArray();

        $this->check(
            'The real accounting periods are exactly as they were',
            $realPeriods === $now,
            implode(', ', array_map(fn ($p) => $p['code'] . ' ' . $p['status'], $now)) . ' — none closed'
        );
    }

    private function tradedLot(array $spec): GoldLot
    {
        $grams = $spec['grams'];
        $cost = $spec['cost'];
        $perGram = round($cost / $grams, 8);

        $lot = GoldLot::create([
            'lot_code' => 'PC-SELFTEST-' . substr(md5(uniqid('', true)), 0, 8),
            'purchase_date' => $this->date, 'project_name' => 'Period close self-test', 'location' => 'Test',
            'gross_grams' => $grams, 'purchase_currency' => 'USD', 'price_per_gram_local' => $perGram,
            'fx_rate_to_usd' => 1, 'price_per_gram_usd' => $perGram,
            'total_cost_local' => $cost, 'total_cost_usd' => $cost, 'status' => 'purchased',
        ]);

        $this->gold->purchase($lot, $this->bank, ['date' => $this->date]);

        $waste = $spec['waste'];
        $processing = GoldProcessing::create([
            'gold_lot_id' => $lot->id, 'processed_at' => $this->date, 'method' => 'Self-test refining',
            'input_grams' => $grams, 'waste_grams' => $waste,
            'waste_percent' => $grams > 0 ? round(($waste / $grams) * 100, 4) : 0,
            'output_grams' => round($grams - $waste, 4), 'output_purity' => '24K',
            'cost_usd' => 0.0, 'cost_capitalised' => true,
        ]);
        $this->gold->refine($processing, null, ['date' => $this->date]);

        if (($spec['capitalised'] ?? 0) > 0) {
            $charge = $this->expenses->draft([
                'expense_date' => $this->date, 'category' => 'refining', 'description' => 'Refinery charge, self-test',
                'amount_local' => $spec['capitalised'], 'capitalised' => true, 'gold_lot_id' => $lot->id,
            ]);
            $this->expenses->submit($charge);
            $this->expenses->approve($charge, $this->super);
            $this->expenses->post($charge, $this->bank, ['date' => $this->date]);
        }

        $sale = GoldSale::create([
            'sale_code' => $lot->lot_code . '-S1', 'gold_lot_id' => $lot->id, 'sale_date' => $this->date,
            'buyer_name' => 'Self-test buyer', 'grams_sold' => $spec['sell_grams'],
            'price_per_gram_usd' => $spec['price'],
            'gross_proceeds_usd' => round($spec['sell_grams'] * $spec['price'], 8),
            'settlement_currency' => 'USD', 'fx_rate_to_usd' => 1, 'status' => GoldSale::STATUS_SETTLED,
        ]);
        $this->gold->sell($sale, $this->bank, ['date' => $this->date]);

        foreach ($spec['expenses'] ?? [] as [$category, $amount]) {
            $expense = $this->expenses->draft([
                'expense_date' => $this->date, 'category' => $category,
                'description' => ucfirst($category) . ', self-test', 'amount_local' => $amount, 'gold_lot_id' => $lot->id,
            ]);
            $this->expenses->submit($expense);
            $this->expenses->approve($expense, $this->super);
            $this->expenses->post($expense, $this->bank, ['date' => $this->date]);
        }

        return $lot->fresh();
    }

    private function accountBalance(string $code): float
    {
        $account = Account::where('code', $code)->first();

        if (! $account) {
            return 0.0;
        }

        $sums = DB::table('journal_lines')->where('account_id', $account->id)
            ->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
        $net = (float) ($sums->d ?? 0) - (float) ($sums->c ?? 0);

        return round($account->normal_balance === 'credit' ? -$net : $net, 8);
    }

    private function investorSnapshot(): array
    {
        return [
            'balance'       => round((float) DB::table('user_wallets')->sum('balance'), 8),
            'profit'        => round((float) DB::table('user_wallets')->sum('profit_balance'), 8),
            'entries'       => (int) DB::table('investor_ledger_entries')->count(),
            'distributions' => (int) DB::table('gold_profit_distributions')->count(),
        ];
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::all()->first(fn (Admin $a) => $a->isSuperAdmin());

        if (! $admin) {
            throw new \RuntimeException('No Super Admin exists to run the close tests with.');
        }

        return $admin;
    }

    private function refused(callable $action, ?string $expected = null): bool
    {
        try {
            $action();
        } catch (\Throwable $e) {
            return $expected === null ? true : $e instanceof $expected;
        }

        return false;
    }

    private function check(string $name, bool $passed, string $detail = ''): void
    {
        $this->results[] = [$name, $passed, $detail];
    }
}
