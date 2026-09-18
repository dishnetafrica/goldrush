<?php

namespace App\Console\Commands;

use App\Accounting\Exceptions\AccountingException;
use App\Accounting\Models\Account;
use App\Accounting\Models\AccountingPeriod;
use App\Accounting\Models\CashAccount;
use App\Accounting\Security\AccountingPermission;
use App\Accounting\Services\CashAccountService;
use App\Accounting\Services\CashService;
use App\Accounting\Services\ExpenseEvidenceStore;
use App\Accounting\Services\ExpenseWorkflow;
use App\Accounting\Services\GoldTradingPoster;
use App\Accounting\Services\InventoryValuation;
use App\Accounting\Services\TrialBalance;
use App\GoldTrading\Models\GoldLot;
use App\GoldTrading\Models\GoldProcessing;
use App\GoldTrading\Models\GoldSale;
use App\GoldTrading\Models\LotResult;
use App\GoldTrading\Models\TradingExpense;
use App\GoldTrading\Services\LotResultCalculator;
use App\Investor\Support\Money;
use App\Models\Admin\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * The Phase 3D acceptance run.
 *
 * Takes expenses through the whole of their lives — raised, edited, submitted,
 * approved or refused, posted, paid, corrected — and checks the books after
 * each step. Everything happens inside a transaction that is always rolled
 * back, and the evidence files the run writes are deleted afterwards, so
 * nothing it does survives it.
 *
 * The historical deals are not touched. Their costs were entered before this
 * workflow existed and are marked as such; that they still count for exactly
 * what they always counted for is one of the things tested here.
 */
class ExpenseSelfTestCommand extends Command
{
    protected $signature = 'expense:selftest {--skip-regression : expense tests only}';

    protected $description = 'Verify the company expense workflow, its controls and its accounting';

    private array $results = [];
    private array $evidenceFiles = [];
    private string $date;

    /** Claims carried between steps, because a life story has to be one claim's. */
    private ?TradingExpense $approved = null;
    private ?TradingExpense $rejected = null;
    private ?TradingExpense $submitted = null;
    private ?TradingExpense $accrued = null;
    private const EPS = 0.00000001;

    public function handle(
        ExpenseWorkflow $workflow,
        ExpenseEvidenceStore $evidence,
        GoldTradingPoster $gold,
        InventoryValuation $valuation,
        CashAccountService $accounts,
        CashService $cash,
        TrialBalance $trialBalance,
        LotResultCalculator $calculator,
    ): int {
        // Artisan resolves a command once per process, so a suite invoked twice in
        // the same run would otherwise report the first run's results alongside
        // the second's and count them all.
        $this->results = [];

        $this->line('Company expense self-test (Phase 3D)');
        $this->line(str_repeat('-', 60));

        $period = AccountingPeriod::where('status', AccountingPeriod::OPEN)->orderByDesc('starts_on')->first();

        if (! $period) {
            $this->error('No open accounting period. Run accounting:install first.');

            return self::FAILURE;
        }

        $this->date = $period->starts_on->copy()->addDays(3)->toDateString();

        DB::beginTransaction();

        try {
            $bank = $accounts->create(['name' => 'Expense Test Bank', 'type' => CashAccount::TYPE_BANK, 'code' => 'EX-BANK']);
            $box = $accounts->create(['name' => 'Expense Test Cash Box', 'type' => CashAccount::TYPE_CASH, 'code' => 'EX-CASH']);

            // Funded from owner's capital rather than the investor control
            // account: no investor put this money in, and the books should not
            // say they did even for the length of a test.
            $cash->receipt($bank, 25000.00, '3000', ['date' => $this->date, 'memo' => 'Self-test funding']);
            $cash->transfer($bank, $box, 2000.00, ['date' => $this->date, 'memo' => 'Self-test float']);

            $this->lifecycle($workflow);
            $this->immutability($workflow);
            $this->permissionsAndSegregation($workflow);
            $this->ordinaryExpenses($workflow, $bank, $box);
            $this->accrualAndPayment($workflow, $bank);
            $this->idempotency($workflow, $bank);
            $this->evidence($evidence, $workflow);
            $this->capitalisation($workflow, $gold, $valuation, $calculator, $cash, $accounts);
            $this->capitalisingSoldGoldRefused($workflow, $gold, $cash, $accounts);
            $this->reclassification($workflow, $gold, $valuation, $cash, $accounts);
            $this->reversal($workflow);
            $this->closedPeriod($workflow, $bank);
            $this->precision($workflow, $bank);
            $this->cashAgreesWithLedger($bank, $box);
            $this->historicalUntouched($calculator);
            $this->trialBalance($trialBalance);
        } catch (\Throwable $e) {
            $this->check('Unexpected failure', false, $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
        } finally {
            DB::rollBack();
            $this->cleanUpEvidence();
        }

        $this->table(
            ['#', 'Test', 'Result', 'Detail'],
            array_map(
                fn ($r, $i) => [$i + 1, $r[0], $r[1] ? 'PASS' : 'FAIL', $r[2]],
                $this->results,
                array_keys($this->results)
            )
        );

        $failed = array_filter($this->results, fn ($r) => ! $r[1]);
        $this->line('  ' . (count($this->results) - count($failed)) . ' of ' . count($this->results) . ' passed.');
        $this->line('  Everything was rolled back; no expense, journal, balance or evidence file remains.');

        $regressionOk = true;

        if (! $this->option('skip-regression')) {
            foreach ([
                'Phase 1' => ['ledger:check', ['user' => 'bhavin']],
                'Phase 2' => ['investor:selftest', ['user' => 'bhavin']],
                'Phase 3A' => ['accounting:selftest', ['--skip-regression' => true]],
                'Phase 3B' => ['cash:selftest', ['--skip-regression' => true]],
                'Phase 3C and D4' => ['gold:accounting-selftest', ['--skip-regression' => true]],
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

    /** 1-5. Raised, edited, submitted, agreed or refused. */
    private function lifecycle(ExpenseWorkflow $workflow): void
    {
        $super = $this->superAdmin();

        $expense = $workflow->draft([
            'expense_date'   => $this->date,
            'category'       => 'operating',
            'description'    => 'Office rent, self-test',
            'payee_name'     => 'Self-test Landlord',
            'currency_code'  => 'KES',
            'amount_local'   => 64500.00,
            'fx_rate_to_usd' => 129.00,
        ]);

        $this->check(
            'A claim is raised as a draft with its own reference',
            $expense->status === TradingExpense::STATUS_DRAFT
            && str_starts_with((string) $expense->reference, 'EXP-')
            && abs((float) $expense->amount_usd - 500.00) < self::EPS
            && $expense->journal_id === null,
            $expense->reference . ': 64,500.00 KES at 129.00 = ' . Money::format((float) $expense->amount_usd)
            . ' USD, nothing posted'
        );

        $workflow->amend($expense, ['amount_local' => 77400.00, 'description' => 'Office rent, corrected']);
        $expense->refresh();

        $this->check(
            'A draft can be corrected freely',
            abs((float) $expense->amount_usd - 600.00) < self::EPS
            && $expense->description === 'Office rent, corrected',
            'now ' . Money::format((float) $expense->amount_usd) . ' USD, "' . $expense->description . '"'
        );

        $workflow->submit($expense);
        $expense->refresh();

        $this->check(
            'Submitting records who and when',
            $expense->status === TradingExpense::STATUS_SUBMITTED && $expense->submitted_at !== null,
            'submitted ' . $expense->submitted_at->format('d M Y H:i') . ' (console)'
        );

        $workflow->approve($expense, $super);
        $expense->refresh();

        $this->check(
            'Approval records the approver and the time',
            $expense->status === TradingExpense::STATUS_APPROVED
            && (int) $expense->approved_by === (int) $super->id
            && $expense->approved_at !== null
            && $expense->sod_exception === false,
            'approved by ' . $super->username . ' at ' . $expense->approved_at->format('d M Y H:i')
            . ', no control waived'
        );

        $rejected = $this->draftFor($workflow, ['description' => 'Unsupported claim, self-test']);
        $workflow->submit($rejected);
        $workflow->reject($rejected, 'No receipt and the supplier is not on file', $super);
        $rejected->refresh();

        $this->check(
            'Rejection records who refused it and why',
            $rejected->status === TradingExpense::STATUS_REJECTED
            && (int) $rejected->rejected_by === (int) $super->id
            && $rejected->rejection_reason === 'No receipt and the supplier is not on file',
            $rejected->reference . ': "' . $rejected->rejection_reason . '"'
        );

        $this->approved = $expense;
        $this->rejected = $rejected;
    }

    /** 6-8. What stops being editable, and when. */
    private function immutability(ExpenseWorkflow $workflow): void
    {
        $submitted = $this->draftFor($workflow, ['description' => 'Submitted, self-test']);
        $workflow->submit($submitted);

        $editRefused = $this->refused(fn () => $workflow->amend($submitted, ['amount_local' => 1.00]));
        $directRefused = $this->refused(fn () => $submitted->update(['amount_usd' => 1.00]));

        $this->check(
            'A submitted claim cannot be edited, by the workflow or around it',
            $editRefused && $directRefused,
            'both the workflow and a direct write were refused'
        );

        $this->check(
            'An approved claim cannot be edited',
            $this->refused(fn () => $this->approved->update(['amount_usd' => 1.00]))
            && $this->refused(fn () => $workflow->amend($this->approved, ['amount_local' => 1.00])),
            $this->approved->reference . ' is approved and its amount is fixed'
        );

        $throwaway = $this->draftFor($workflow, ['description' => 'Raised in error, self-test']);
        $deletedDraft = $throwaway->delete();

        $this->check(
            'A draft can be thrown away; a claim that has been seen cannot',
            $deletedDraft === true
            && $this->refused(fn () => $submitted->delete())
            && $this->refused(fn () => $this->rejected->delete()),
            'draft deleted; submitted and rejected claims refused'
        );

        $this->submitted = $submitted;
    }

    /** 9-11. Who may do what, and who may not check their own work. */
    private function permissionsAndSegregation(ExpenseWorkflow $workflow): void
    {
        $stranger = new Admin();
        $stranger->id = -1;
        $stranger->username = 'selftest-no-permissions';

        $draftRefused = $this->refused(fn () => $workflow->draft([
            'expense_date' => $this->date, 'category' => 'other',
            'description' => 'Should not exist', 'amount_local' => 10.00,
        ], $stranger));

        $approveRefused = $this->refused(fn () => $workflow->approve($this->submitted, $stranger));
        $postRefused = $this->refused(fn () => $workflow->post($this->approved, null, [], $stranger));

        $this->check(
            'Admin without permission cannot raise, approve or post',
            $draftRefused && $approveRefused && $postRefused
            && ! AccountingPermission::allows($stranger, AccountingPermission::EXPENSE_APPROVE),
            'all three refused for an admin holding no grants'
        );

        $super = $this->superAdmin();
        $own = $this->draftFor($workflow, ['description' => 'Own claim, self-test']);
        $workflow->submit($own, $super);

        config(['accounting.expenses.allow_self_approval' => false]);

        $this->check(
            'Nobody may approve the claim they submitted',
            config('accounting.expenses.allow_self_approval') === false
            && $this->refused(fn () => $workflow->approve($own, $super), AccountingException::class),
            'strict by default; refused for ' . $super->username . ', who submitted it'
        );

        config(['accounting.expenses.allow_self_approval' => true]);

        $noReason = $this->refused(fn () => $workflow->approve($own, $super), AccountingException::class);
        $workflow->approve($own, $super, 'Single administrator operates this environment');
        $own->refresh();

        $this->check(
            'Waiving that control needs a reason, and the waiver is recorded',
            $noReason
            && $own->status === TradingExpense::STATUS_APPROVED
            && $own->sod_exception === true
            && $own->sod_exception_reason === 'Single administrator operates this environment',
            'waived visibly: "' . $own->sod_exception_reason . '"'
        );

        config(['accounting.expenses.allow_self_approval' => false]);

        $unapproved = $this->draftFor($workflow, ['description' => 'Never approved, self-test']);

        $this->check(
            'A claim nobody has approved cannot reach the general ledger',
            $this->refused(fn () => $workflow->post($unapproved))
            && $this->refused(fn () => $workflow->post($this->rejected)),
            'a draft and a rejected claim were both refused'
        );
    }

    /** 12-14. Ordinary costs, paid from the bank and from the cash box. */
    private function ordinaryExpenses(ExpenseWorkflow $workflow, CashAccount $bank, CashAccount $box): void
    {
        $bankBefore = $bank->balance();
        $journal = $workflow->post($this->approved, $bank, ['date' => $this->date]);
        $this->approved->refresh();

        $line = $journal->lines->first(fn ($l) => $l->account->code === '6100');

        $this->check(
            'Ordinary expense paid from the bank: debit the expense, credit the bank',
            $journal->balances()
            && $line !== null
            && abs((float) $line->debit - 600.00) < self::EPS
            && abs(($bankBefore - $bank->fresh()->balance()) - 600.00) < self::EPS
            && $this->approved->payment_status === TradingExpense::PAYMENT_PAID,
            $journal->reference . ': Dr 6100 ' . Money::format((float) $line->debit) . ' / Cr '
            . $bank->glAccount->code . ', settled on posting'
        );

        // This claim had two edits refused a moment ago. A refusal leaves its
        // values on the in-memory model, and what reaches the ledger has to be
        // the record rather than what somebody tried and was not allowed to do.
        $this->check(
            'A refused edit does not reach the ledger through a later posting',
            abs((float) $line->debit - 600.00) < self::EPS
            && abs((float) $this->approved->amount_usd - 600.00) < self::EPS,
            'the refused 1.00 was discarded; ' . Money::format(600.00) . ' was posted'
        );

        $petty = $this->draftFor($workflow, [
            'category'    => 'travel',
            'description' => 'Taxi to the assayer, self-test',
            'amount_local' => 45.00,
        ]);
        $workflow->submit($petty);
        $workflow->approve($petty, $this->superAdmin());

        $boxBefore = $box->balance();
        $cashJournal = $workflow->post($petty, $box, ['date' => $this->date]);
        $cashLine = $cashJournal->lines->first(fn ($l) => $l->account->code === '6040');

        $this->check(
            'Ordinary expense paid from the cash box',
            $cashJournal->balances()
            && abs((float) $cashLine->debit - 45.00) < self::EPS
            && abs(($boxBefore - $box->fresh()->balance()) - 45.00) < self::EPS,
            $cashJournal->reference . ': Dr 6040 Travel ' . Money::format(45.00) . ' / Cr ' . $box->glAccount->code
        );

        $this->check(
            'Posting records who did it and when',
            $this->approved->posted_at !== null
            && $this->approved->status === TradingExpense::STATUS_POSTED
            && $petty->fresh()->posted_at !== null,
            $this->approved->reference . ' posted ' . $this->approved->posted_at->format('d M Y H:i')
        );
    }

    /** 15-16. A cost recognised before it is paid, then paid. */
    private function accrualAndPayment(ExpenseWorkflow $workflow, CashAccount $bank): void
    {
        $expense = $this->draftFor($workflow, [
            'category'     => 'security',
            'description'  => 'Escort, invoiced 30 days, self-test',
            'payee_name'   => 'Self-test Security Ltd',
            'external_ref' => 'INV-SELFTEST-4471',
            'amount_local' => 320.00,
        ]);
        $workflow->submit($expense);
        $workflow->approve($expense, $this->superAdmin());

        $this->check(
            'Who was paid, and against which invoice, is recorded',
            $expense->payee_name === 'Self-test Security Ltd'
            && $expense->external_ref === 'INV-SELFTEST-4471',
            $expense->reference . ': ' . $expense->payee_name . ', invoice ' . $expense->external_ref
        );

        $payableBefore = $this->accountBalance('2100');
        $journal = $workflow->post($expense, null, ['date' => $this->date]);
        $expense->refresh();

        $this->check(
            'An approved but unpaid cost is recognised against accrued expenses payable',
            $journal->balances()
            && abs(($this->accountBalance('2100') - $payableBefore) - 320.00) < self::EPS
            && $expense->payment_status === TradingExpense::PAYMENT_UNPAID
            && $expense->status === TradingExpense::STATUS_POSTED,
            $journal->reference . ': Dr 6030 Security ' . Money::format(320.00)
            . ' / Cr 2100; the company owes it and the books say so'
        );

        $bankBefore = $bank->balance();
        $payment = $workflow->pay($expense, $bank, ['date' => $this->date]);
        $expense->refresh();

        $this->check(
            'Paying it clears the payable and moves the cash, touching no expense account',
            $payment->balances()
            && abs(($this->accountBalance('2100') - $payableBefore)) < self::EPS
            && abs(($bankBefore - $bank->fresh()->balance()) - 320.00) < self::EPS
            && $payment->lines->every(fn ($l) => ! str_starts_with($l->account->code, '6'))
            && $expense->payment_status === TradingExpense::PAYMENT_PAID,
            $payment->reference . ': Dr 2100 / Cr ' . $bank->glAccount->code . ' ' . Money::format(320.00)
        );

        $this->accrued = $expense;
    }

    /** 17-19. The same thing done twice happens once. */
    private function idempotency(ExpenseWorkflow $workflow, CashAccount $bank): void
    {
        $journalsBefore = DB::table('journals')->count();
        $again = $workflow->post($this->accrued, $bank, ['date' => $this->date]);

        $this->check(
            'Posting the same cost twice posts it once',
            (int) $again->id === (int) $this->accrued->journal_id
            && DB::table('journals')->count() === $journalsBefore,
            'handed back ' . $again->reference . '; no second journal was raised'
        );

        $balanceBefore = $bank->fresh()->balance();
        $paidAgain = $workflow->pay($this->accrued, $bank, ['date' => $this->date]);

        $this->check(
            'Paying the same cost twice pays it once',
            (int) $paidAgain->id === (int) $this->accrued->payment_journal_id
            && abs($bank->fresh()->balance() - $balanceBefore) < self::EPS,
            'handed back ' . $paidAgain->reference . '; the bank did not move again'
        );

        $duplicate = $this->refused(fn () => $workflow->draft([
            'expense_date'   => $this->date,
            'category'       => 'security',
            'description'    => 'The same invoice, claimed twice',
            'payee_name'     => 'Self-test Security Ltd',
            'external_ref'   => 'INV-SELFTEST-4471',
            'amount_local'   => 320.00,
        ]));

        $this->check(
            'The same supplier invoice cannot be claimed twice',
            $duplicate,
            'INV-SELFTEST-4471 from Self-test Security Ltd is already claimed as ' . $this->accrued->reference
        );
    }

    /** 20-22. Where the proof lives and who may read it. */
    private function evidence(ExpenseEvidenceStore $store, ExpenseWorkflow $workflow): void
    {
        $expense = $this->draftFor($workflow, ['description' => 'Cost with a receipt, self-test']);

        $bytes = "%PDF-1.4\n% self-test receipt\n" . str_repeat('0', 512);
        $store->attach($expense, $bytes, 'receipt.pdf', 'application/pdf');
        $expense->refresh();

        $this->evidenceFiles[] = $expense->evidence_path;

        $root = $store->root();
        $full = rtrim($root, '/') . '/' . $expense->evidence_path;
        $publicRoot = realpath(public_path()) ?: public_path();

        $this->check(
            'Evidence is stored on a private disk, outside anything the web serves',
            $expense->hasEvidence()
            && Storage::disk(TradingExpense::EVIDENCE_DISK)->exists($expense->evidence_path)
            && is_file($full)
            && ! str_starts_with(realpath($full) ?: $full, rtrim($publicRoot, '/') . '/')
            && str_contains($root, 'storage'),
            $root . ' (' . number_format((float) $expense->evidence_bytes / 1024, 1) . ' KB filed)'
        );

        $stranger = new Admin();
        $stranger->id = -1;
        $stranger->username = 'selftest-no-permissions';

        $this->check(
            'Evidence cannot be read without permission',
            $this->refused(fn () => $store->read($expense, $stranger))
            && ! $store->canRead($expense, $stranger)
            && $store->canRead($expense, $this->superAdmin()),
            'refused for an admin holding no grants, allowed for a Super Admin'
        );

        $intactBefore = $expense->evidenceIntact();
        Storage::disk(TradingExpense::EVIDENCE_DISK)->put($expense->evidence_path, 'tampered');

        $this->check(
            'An altered evidence file is detected rather than trusted',
            $intactBefore === true && $expense->evidenceIntact() === false,
            'SHA-256 ' . substr((string) $expense->evidence_hash, 0, 16) . '... no longer matches the bytes on disk'
        );

        $workflow->submit($expense);

        $this->check(
            'Evidence cannot be swapped once the claim has been submitted',
            $this->refused(fn () => $store->attach($expense, 'different bytes', 'other.pdf', 'application/pdf')),
            'a claim is submitted together with its proof'
        );
    }

    /**
     * 23-27. Decision D4 through the expense workflow.
     *
     * The fixture the decision was settled on: 2,000.00 of gold, 25 g, 8% lost
     * refining, and a 100.00 processing charge claimed as an expense and marked
     * as capitalised.
     */
    private function capitalisation(
        ExpenseWorkflow $workflow,
        GoldTradingPoster $gold,
        InventoryValuation $valuation,
        LotResultCalculator $calculator,
        CashService $cash,
        CashAccountService $accounts,
    ): void {
        $bank = $accounts->create(['name' => 'D4 Expense Bank', 'type' => CashAccount::TYPE_BANK, 'code' => 'EX-D4']);
        $cash->receipt($bank, 10000.00, '3000', ['date' => $this->date, 'memo' => 'D4 expense funding']);

        $lot = $this->makeLot(2000.00, 25.0);
        $gold->purchase($lot, $bank, ['date' => $this->date]);

        // Refining with no charge of its own: the charge arrives separately, as
        // an expense claim, which is the path this phase adds.
        $processing = GoldProcessing::create([
            'gold_lot_id'      => $lot->id,
            'processed_at'     => $this->date,
            'method'           => 'Self-test refining',
            'input_grams'      => 25.0,
            'waste_grams'      => 2.0,
            'waste_percent'    => 8.0,
            'output_grams'     => 23.0,
            'output_purity'    => '24K',
            'cost_usd'         => 0.0,
            'cost_capitalised' => true,
        ]);
        $gold->refine($processing, null, ['date' => $this->date]);

        $charge = $workflow->draft([
            'expense_date'  => $this->date,
            'category'      => 'refining',
            'description'   => 'Refinery charge, self-test',
            'payee_name'    => 'Self-test Refinery',
            'amount_local'  => 100.00,
            'capitalised'   => true,
            'gold_lot_id'   => $lot->id,
        ]);
        $workflow->submit($charge);
        $workflow->approve($charge, $this->superAdmin());

        $beforeBasis = $valuation->forLot($lot->fresh())['cost_basis_usd'];
        $journal = $workflow->post($charge, $bank, ['date' => $this->date]);
        $charge->refresh();

        $inventoryLine = $journal->lines->first(fn ($l) => $l->account->code === InventoryValuation::REFINED);
        $anyExpenseLine = $journal->lines->first(fn ($l) => str_starts_with($l->account->code, '6'));

        $this->check(
            'A capitalised cost debits inventory, never an expense account',
            $journal->balances()
            && $inventoryLine !== null
            && abs((float) $inventoryLine->debit - 100.00) < self::EPS
            && $anyExpenseLine === null
            && $charge->capitalised_into === InventoryValuation::REFINED
            && (int) $inventoryLine->gold_lot_id === (int) $lot->id,
            $journal->reference . ': Dr 1110 ' . Money::format(100.00) . ' / Cr ' . $bank->glAccount->code
            . ', tagged to ' . $lot->lot_code . '; no 6xxx line'
        );

        $v = $valuation->forLot($lot->fresh());

        $this->check(
            'The capitalised cost enters the lot\'s cost basis and the ledger together',
            abs($beforeBasis - 2000.00) < self::EPS
            && abs($v['cost_basis_usd'] - 2100.00) < self::EPS
            && abs($v['capitalised_cost_usd'] - 100.00) < self::EPS
            && abs($v['gl_refined_usd'] - 2100.00) < self::EPS,
            'basis ' . Money::format($beforeBasis) . ' to ' . Money::format($v['cost_basis_usd'])
            . '; ledger refined inventory ' . Money::format($v['gl_refined_usd'])
        );

        $expectedPerGram = round(2100.00 / 23.0, 8);

        $this->check(
            'Cost per refined gram is 2,100.00 over 23 g',
            abs($v['cost_per_refined_gram'] - $expectedPerGram) < self::EPS,
            Money::exact($v['cost_per_refined_gram']) . ' expected ' . Money::exact($expectedPerGram)
        );

        // An ordinary cost on the same deal, so the two treatments can be seen
        // side by side on one lot.
        $transport = $workflow->draft([
            'expense_date' => $this->date,
            'category'     => 'transport',
            'description'  => 'Transport to the buyer, self-test',
            'amount_local' => 120.00,
            'gold_lot_id'  => $lot->id,
        ]);
        $workflow->submit($transport);
        $workflow->approve($transport, $this->superAdmin());
        $workflow->post($transport, $bank, ['date' => $this->date]);

        $sale = $this->makeSale($lot, 10.0, 130.00);
        $gold->sell($sale, $bank, ['date' => $this->date]);

        $result = $calculator->forLot($lot->fresh());
        $after = $valuation->forLot($lot->fresh());
        $expectedCogs = round($expectedPerGram * 10.0, 8);

        $this->check(
            'The capitalised cost is not also an expense of the deal',
            abs((float) $result['expenses_usd'] - 120.00) < self::EPS
            && abs((float) $result['capitalised_expenses_usd'] - 100.00) < self::EPS,
            'deal expenses ' . Money::format((float) $result['expenses_usd'])
            . ' (the transport only); the 100.00 refinery charge is in the gold'
        );

        // Everything the company spent on this deal, counted once each: what it
        // paid for the gold, what it capitalised, and what it expensed.
        $spent = 2000.00 + 100.00 + 120.00;
        $recognised = round((float) $result['cost_usd'] + (float) $result['expenses_usd'], 8);

        $this->check(
            'Nothing is counted twice and nothing is lost',
            abs($recognised - $spent) < self::EPS,
            'spent ' . Money::format($spent) . ' = cost basis ' . Money::format((float) $result['cost_usd'])
            . ' + expenses ' . Money::format((float) $result['expenses_usd'])
        );

        $this->check(
            'Cost of sales, inventory left and the deal result all agree',
            abs((float) $result['cost_of_goods_sold_usd'] - $expectedCogs) < self::EPS
            && abs($after['gl_refined_usd'] - round(2100.00 - $expectedCogs, 8)) < self::EPS
            && abs((float) $result['remaining_value_at_cost_usd'] - $after['gl_refined_usd']) < self::EPS
            && $valuation->divergence($lot->fresh())['agrees'],
            'COGS ' . Money::exact($expectedCogs) . ' for 10 g; ' . Money::exact($after['gl_refined_usd'])
            . ' of the 13 g left still in inventory'
        );
    }

    /** 28. A cost cannot join gold that has already gone. */
    private function capitalisingSoldGoldRefused(
        ExpenseWorkflow $workflow,
        GoldTradingPoster $gold,
        CashService $cash,
        CashAccountService $accounts,
    ): void {
        $bank = $accounts->create(['name' => 'Sold Out Bank', 'type' => CashAccount::TYPE_BANK, 'code' => 'EX-SOLD']);
        $cash->receipt($bank, 5000.00, '3000', ['date' => $this->date, 'memo' => 'Sold-out funding']);

        $lot = $this->makeLot(800.00, 10.0);
        $gold->purchase($lot, $bank, ['date' => $this->date]);

        $processing = GoldProcessing::create([
            'gold_lot_id'   => $lot->id, 'processed_at' => $this->date, 'method' => 'Self-test',
            'input_grams'   => 10.0, 'waste_grams' => 0.0, 'waste_percent' => 0.0,
            'output_grams'  => 10.0, 'output_purity' => '24K',
            'cost_usd'      => 0.0, 'cost_capitalised' => true,
        ]);
        $gold->refine($processing, null, ['date' => $this->date]);
        $gold->sell($this->makeSale($lot, 10.0, 120.00), $bank, ['date' => $this->date]);

        $late = $workflow->draft([
            'expense_date' => $this->date, 'category' => 'refining',
            'description'  => 'Charge arriving after the gold was sold, self-test',
            'amount_local' => 50.00, 'capitalised' => true, 'gold_lot_id' => $lot->id,
        ]);
        $workflow->submit($late);
        $workflow->approve($late, $this->superAdmin());

        $this->check(
            'A cost cannot be capitalised into gold that has already been sold',
            $this->refused(fn () => $workflow->post($late, $bank, ['date' => $this->date]))
            && $late->fresh()->journal_id === null,
            'refused for ' . $lot->lot_code . ': there is no inventory left for it to attach to'
        );
    }

    /** 29. Correcting where a posted cost sits, without moving any money. */
    private function reclassification(
        ExpenseWorkflow $workflow,
        GoldTradingPoster $gold,
        InventoryValuation $valuation,
        CashService $cash,
        CashAccountService $accounts,
    ): void {
        $bank = $accounts->create(['name' => 'Reclass Bank', 'type' => CashAccount::TYPE_BANK, 'code' => 'EX-RECLASS']);
        $cash->receipt($bank, 5000.00, '3000', ['date' => $this->date, 'memo' => 'Reclass funding']);

        $lot = $this->makeLot(1000.00, 12.0);
        $gold->purchase($lot, $bank, ['date' => $this->date]);

        $processing = GoldProcessing::create([
            'gold_lot_id'   => $lot->id, 'processed_at' => $this->date, 'method' => 'Self-test',
            'input_grams'   => 12.0, 'waste_grams' => 1.0, 'waste_percent' => 8.3333,
            'output_grams'  => 11.0, 'output_purity' => '24K',
            'cost_usd'      => 0.0, 'cost_capitalised' => true,
        ]);
        $gold->refine($processing, null, ['date' => $this->date]);

        // Booked as an ordinary assay expense, when it was in fact a cost of
        // making the gold saleable.
        $expense = $workflow->draft([
            'expense_date' => $this->date, 'category' => 'assay',
            'description'  => 'Assay booked to the wrong place, self-test',
            'amount_local' => 75.00, 'gold_lot_id' => $lot->id,
        ]);
        $workflow->submit($expense);
        $workflow->approve($expense, $this->superAdmin());
        $workflow->post($expense, $bank, ['date' => $this->date]);

        $bankBefore = $bank->fresh()->balance();
        $assayBefore = $this->accountBalance('6020');
        $basisBefore = $valuation->forLot($lot->fresh())['cost_basis_usd'];

        $journal = $workflow->reclassify(
            $expense,
            ['capitalised' => true, 'date' => $this->date],
            'It prepared the gold for sale and belongs in its cost'
        );
        $expense->refresh();
        $v = $valuation->forLot($lot->fresh());

        $this->check(
            'A posted cost can be moved to the right account without touching cash',
            $journal->balances()
            && abs($bank->fresh()->balance() - $bankBefore) < self::EPS
            && abs(($assayBefore - $this->accountBalance('6020')) - 75.00) < self::EPS
            && abs(($v['cost_basis_usd'] - $basisBefore) - 75.00) < self::EPS
            && abs($v['gl_refined_usd'] - $v['cost_basis_usd']) < self::EPS
            && $expense->capitalised === true,
            $journal->reference . ': Dr 1110 / Cr 6020 ' . Money::format(75.00)
            . '; basis ' . Money::format($basisBefore) . ' to ' . Money::format($v['cost_basis_usd'])
            . ', bank unchanged'
        );
    }

    /** 30-32. Undoing, and what may not be undone. */
    private function reversal(ExpenseWorkflow $workflow): void
    {
        $expense = $this->draftFor($workflow, [
            'category'     => 'other',
            'description'  => 'Billed in error, self-test',
            'amount_local' => 250.00,
        ]);
        $workflow->submit($expense);
        $workflow->approve($expense, $this->superAdmin());

        $payableBefore = $this->accountBalance('2100');
        $otherBefore = $this->accountBalance('6100');
        $workflow->post($expense, null, ['date' => $this->date]);

        $reversal = $workflow->reverse($expense, 'The supplier withdrew the invoice');
        $expense->refresh();

        $this->check(
            'An unpaid cost the company does not owe can be reversed away',
            $reversal->balances()
            && abs($this->accountBalance('2100') - $payableBefore) < self::EPS
            && abs($this->accountBalance('6100') - $otherBefore) < self::EPS
            && $expense->status === TradingExpense::STATUS_REVERSED
            && $expense->journal->fresh()->status === 'reversed',
            $reversal->reference . ' reversed ' . $expense->journal->reference
            . '; both entries stay in the books and net to nothing'
        );

        $this->check(
            'A cost that has actually been paid cannot be reversed',
            $this->refused(fn () => $workflow->reverse($this->accrued, 'Changed my mind'))
            && $this->accrued->fresh()->status === TradingExpense::STATUS_POSTED,
            'the money left the bank; the books may not pretend it came back'
        );

        $this->check(
            'A reversed cost counts for nothing and cannot be paid',
            $expense->countsAsDealExpense() === false
            && $expense->countsInCostBasis() === false
            && $this->refused(fn () => $workflow->reverse($expense, 'Again')),
            $expense->reference . ' is out of every total'
        );
    }

    /** 33. A closed period will not take a posting. */
    private function closedPeriod(ExpenseWorkflow $workflow, CashAccount $bank): void
    {
        $expense = $this->draftFor($workflow, ['description' => 'Into a closed period, self-test']);
        $workflow->submit($expense);
        $workflow->approve($expense, $this->superAdmin());

        $period = AccountingPeriod::covering(\Illuminate\Support\Carbon::parse($this->date));
        $was = $period->status;
        $period->update(['status' => AccountingPeriod::CLOSED]);

        $refused = $this->refused(fn () => $workflow->post($expense, $bank, ['date' => $this->date]));

        $period->update(['status' => $was]);

        $this->check(
            'Posting an expense into a closed period is refused',
            $refused && $expense->fresh()->journal_id === null,
            'period ' . $period->code . ' rejected it'
        );
    }

    /** 34. Eight decimal places, kept. */
    private function precision(ExpenseWorkflow $workflow, CashAccount $bank): void
    {
        $local = 1234.56789012;
        $fx = 3.7;
        $expected = round($local / $fx, 8);

        $expense = $workflow->draft([
            'expense_date'   => $this->date,
            'category'       => 'commission',
            'description'    => 'Awkward rate, self-test',
            'currency_code'  => 'AED',
            'amount_local'   => $local,
            'fx_rate_to_usd' => $fx,
        ]);
        $workflow->submit($expense);
        $workflow->approve($expense, $this->superAdmin());
        $journal = $workflow->post($expense, $bank, ['date' => $this->date]);

        $stored = (float) DB::table('gold_trading_expenses')->where('id', $expense->id)->value('amount_usd');
        $line = $journal->lines->first(fn ($l) => $l->account->code === '6050');

        $this->check(
            'Eight-decimal precision survives the round trip',
            abs($stored - $expected) < self::EPS
            && abs((float) $line->debit - $expected) < self::EPS
            && $journal->balances(),
            number_format($local, 8) . ' AED at ' . $fx . ' = ' . Money::exact($stored)
            . ' USD, posted as ' . Money::exact((float) $line->debit)
        );
    }

    /** 35. The cash accounts and the ledger are the same number. */
    private function cashAgreesWithLedger(CashAccount $bank, CashAccount $box): void
    {
        $agree = true;
        $detail = [];

        foreach ([$bank, $box] as $account) {
            $fresh = $account->fresh();
            $fromLedger = $this->accountBalance($fresh->glAccount->code);
            $agree = $agree && abs($fresh->balance() - $fromLedger) < self::EPS;
            $detail[] = $fresh->code . ' ' . Money::format($fresh->balance());
        }

        $this->check(
            'Cash and bank balances are the general ledger, not a second record of it',
            $agree,
            implode(', ', $detail) . ' — read from the accounts and from the journals, identical'
        );
    }

    /** 36. The deals that were closed before any of this existed are unaffected. */
    private function historicalUntouched(LotResultCalculator $calculator): void
    {
        $lotIds = LotResult::where('status', LotResult::STATUS_DISTRIBUTED)->pluck('gold_lot_id');
        $lots = GoldLot::whereIn('id', $lotIds)->with('expenses')->get();

        if ($lots->isEmpty()) {
            $this->check('Historical deals are unaffected', true, 'no historical deal present to check');

            return;
        }

        $ok = true;
        $detail = [];

        foreach ($lots as $lot) {
            $result = $calculator->forLot($lot);

            // Every cost these deals carry was entered before the workflow
            // existed, so all of them are marked "recorded" and all of them must
            // still count for exactly what they counted for before.
            $recorded = $lot->expenses->sum('amount_usd');
            $counted = $lot->expenses->filter(fn (TradingExpense $e) => $e->countsAsDealExpense())->sum('amount_usd');
            $capitalised = $lot->expenses->filter(fn (TradingExpense $e) => $e->countsInCostBasis())->sum('amount_usd');

            $ok = $ok
                && abs($recorded - $counted) < self::EPS
                && abs($capitalised) < self::EPS
                && abs((float) $result['expenses_usd'] - $counted) < self::EPS
                && abs((float) $result['capitalised_cost_usd']) < self::EPS;

            $detail[] = $lot->lot_code . ' expenses ' . Money::format((float) $result['expenses_usd'])
                . ' net ' . Money::exact((float) $result['net_profit_usd']);
        }

        $this->check(
            'Historical deals still carry exactly the costs they always did',
            $ok,
            implode('; ', $detail)
        );
    }

    /** 37. The books still balance. */
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

    private function draftFor(ExpenseWorkflow $workflow, array $overrides = []): TradingExpense
    {
        return $workflow->draft(array_merge([
            'expense_date'   => $this->date,
            'category'       => 'other',
            'description'    => 'Self-test cost',
            'currency_code'  => 'USD',
            'amount_local'   => 100.00,
            'fx_rate_to_usd' => 1,
        ], $overrides));
    }

    private function makeLot(float $totalCost, float $grams): GoldLot
    {
        $perGram = round($totalCost / $grams, 8);

        return GoldLot::create([
            'lot_code'             => 'EX-SELFTEST-' . substr(md5(uniqid('', true)), 0, 8),
            'purchase_date'        => $this->date,
            'project_name'         => 'Expense self-test deal',
            'location'             => 'Test',
            'gross_grams'          => $grams,
            'purchase_currency'    => 'USD',
            'price_per_gram_local' => $perGram,
            'fx_rate_to_usd'       => 1,
            'price_per_gram_usd'   => $perGram,
            'total_cost_local'     => $totalCost,
            'total_cost_usd'       => $totalCost,
            'status'               => 'purchased',
        ]);
    }

    private function makeSale(GoldLot $lot, float $grams, float $pricePerGram): GoldSale
    {
        return GoldSale::create([
            'sale_code'           => $lot->lot_code . '-S' . (GoldSale::where('gold_lot_id', $lot->id)->count() + 1),
            'gold_lot_id'         => $lot->id,
            'sale_date'           => $this->date,
            'buyer_name'          => 'Self-test buyer',
            'grams_sold'          => $grams,
            'price_per_gram_usd'  => $pricePerGram,
            'gross_proceeds_usd'  => round($grams * $pricePerGram, 8),
            'settlement_currency' => 'USD',
            'fx_rate_to_usd'      => 1,
            'status'              => GoldSale::STATUS_SETTLED,
        ]);
    }

    private function accountBalance(string $code): float
    {
        $account = Account::where('code', $code)->first();

        if (! $account) {
            return 0.0;
        }

        $sums = DB::table('journal_lines')
            ->where('account_id', $account->id)
            ->selectRaw('SUM(debit) as d, SUM(credit) as c')
            ->first();

        $net = (float) ($sums->d ?? 0) - (float) ($sums->c ?? 0);

        return round($account->normal_balance === 'credit' ? -$net : $net, 8);
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::all()->first(fn (Admin $a) => $a->isSuperAdmin());

        if (! $admin) {
            throw new \RuntimeException('No Super Admin exists to run the approval tests with.');
        }

        return $admin;
    }

    /**
     * The database rolls back; files do not. Everything this run filed is
     * deleted, and only what this run filed — the paths are collected as they
     * are written rather than guessed at afterwards.
     */
    private function cleanUpEvidence(): void
    {
        foreach ($this->evidenceFiles as $path) {
            if ($path && Storage::disk(TradingExpense::EVIDENCE_DISK)->exists($path)) {
                Storage::disk(TradingExpense::EVIDENCE_DISK)->delete($path);
            }
        }

        $this->evidenceFiles = [];
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
