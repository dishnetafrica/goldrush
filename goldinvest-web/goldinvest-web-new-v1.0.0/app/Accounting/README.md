# Company general ledger (Phase 3A)

The company side of the business: what it owns, what it owes, what it earned and
what it spent. It sits alongside the investor ledger rather than replacing it.

## The boundary

```
investor_ledger_entries              accounts / journals / journal_lines
(what the company owes investors)    (what the company owns, owes, earned, spent)
            │                                         │
            └──────── two control accounts ───────────┘
                  2000 Investor Capital Payable
                  2010 Investor Profit Payable
```

`2000` must equal the sum of every investor's **available + committed** buckets.
`2010` must equal the sum of their **profit** buckets. Those two equalities are
the only connection between the systems, and the only thing keeping the
company's books and the investors' statements from drifting apart.

Nothing else crosses. No journal writes a ledger entry; no ledger entry writes a
journal. In 3F the distribution service will write both inside one transaction
and record each one's id on the other.

## What is enforced

A journal is refused, before anything is written, if it:

- has fewer than two lines
- does not balance to 8 decimal places
- has a line that is both a debit and a credit, or neither
- has a negative amount — a debit of minus something is a credit; say which
- names an account that does not exist or is inactive
- falls in a period that is closed or locked, or in no period at all
- comes from an admin without `admin.accounting.journal.post`
- has no memo

Posted journals and their lines are immutable. A mistake is answered by a
reversing journal, which posts the mirror image and marks the original as
reversed. Both stay in the account — an auditor can see that something was
posted and then undone, and why.

## Commands

```bash
php artisan accounting:install --from=2026-09-01   # chart of accounts + monthly periods
php artisan accounting:trial-balance               # debits vs credits
php artisan accounting:trial-balance --period=2026-09
php artisan accounting:selftest                    # 13 tests + Phase 1/2 regression
```

## Suspense (1090)

Two things happened before the accounts existed: an investor was credited 2,000
with no recorded company receipt, and two gold purchases were paid with money
the system never held. Until supporting records establish where that money came
from, it belongs in 1090, visibly.

It is deliberately **not** Owner's Capital or a Director's Loan. Booking it to
either would assert something nobody has shown to be true, and a balance sheet
that balances because of a plug is worse than one that openly says "unresolved".

## Cash and bank (3B)

Each cash box or bank account owns exactly one GL account, created with it under
1000 (cash) or 1010 (bank). Its balance *is* that GL account's balance — there
is no second figure anywhere, so there is nothing to reconcile between them.

There is deliberately no cash movements table either. A receipt, a payment or a
transfer is a journal and nothing else; recording it twice would create two sets
of books that could disagree.

```bash
php artisan cash:account "Main Bank" --type=bank --bank="..." --ref=...
php artisan cash:account --list
php artisan cash:post receipt  MAIN-BANK --amount=2000 --contra=2000 --memo="..."
php artisan cash:post payment  MAIN-BANK --amount=120  --contra=6000 --memo="..."
php artisan cash:post transfer MAIN-BANK --to=CASH-BOX --amount=400
php artisan bank:statement MAIN-BANK --import=statement.csv --statement-ref=SEP-2026
php artisan bank:statement MAIN-BANK --suggest
php artisan bank:statement MAIN-BANK --match=12 --to-line=34
php artisan bank:reconcile MAIN-BANK --as-at=2026-09-30 --closing-balance=1234.56 --complete
php artisan cash:selftest
```

Money the company does not have cannot leave it: a payment that would take an
account below its floor is refused, and an overdraft has to be permitted on the
account and stays within its limit.

### Reconciling never changes accounting

Matching a statement line to a journal line records that the two refer to the
same event. It does not adjust, create or correct a journal, and it refuses to
match lines whose amounts disagree — a match that papers over a difference hides
the very thing a reconciliation exists to surface. Fixing a real error is a
separate, deliberate act: post a correcting journal, then reconcile again.

A reconciliation will not complete while any line is unmatched, or over any
difference at all. Lines can be set aside, but never silently: an ignored line
needs a stated reason. Completed reconciliations are immutable, and a later
check is a new reconciliation, so the history shows what was believed at each
point rather than only the latest opinion.

The person completing a reconciliation may not be the only person who has seen
the evidence: if one admin imported the statement, someone else signs it off.

## Gold trading (3C)

```
purchase   Dr 1100 Inventory-Unrefined   Cr cash / 2100 Payable
refining   Dr 1110 Inventory-Refined     Cr 1100          (cost carried across)
           Dr 1110                       Cr cash          (charge capitalised, D4)
sale       Dr cash / 1300 Receivable     Cr 4000 Revenue
           Dr 5000 Cost of Gold Sold     Cr 1110          (all in one journal)
expense    Dr 6000-6100 by category      Cr cash / 2100
```

**Wastage posts no journal.** Grams vanish; dollars do not. The cost attaches to
fewer grams, which is why a lot bought at 80.00/g carries 86.96/g after an 8%
loss. Booking the lost grams as a write-off would charge that cost twice - once
through the raised unit cost and again as an expense.

A sale posts revenue and cost of sales in **one** journal. Splitting them would
let a sale exist with no cost against it, which is how a set of books starts
flattering itself.

Selling more grams than a lot holds is refused: inventory cannot go negative.
A sale that empties a lot takes whatever cost is left rather than a rounded
multiple, so nothing is stranded in inventory.

```bash
php artisan gold:post purchase <LOT> --from=<cash account>
php artisan gold:post refining <processing id> --from=<cash account>
php artisan gold:post sale <SALE-CODE> --to=<cash account>
php artisan gold:post expense <expense id> --from=<cash account>
php artisan gold:inventory
php artisan gold:accounting-selftest
```

### One cost basis, read by both (decision D4)

Costs incurred to bring gold to a saleable condition are part of what the gold
cost. They go into inventory, and having gone there they are never counted as an
expense again.

`App\GoldTrading\Services\LotCostBasis` computes this once. Both the general
ledger and `LotResultCalculator` read it, so the company's books and the
investor-facing deal result cannot hold different opinions about what a lot
cost. Two systems that must agree are given one thing to read rather than two
formulas to keep in step.

```
cost basis        = purchase cost + capitalised processing + capitalised expenses
cost per gram     = cost basis / refined grams (after loss)
cost of sales     = cost per gram x grams sold
inventory held    = cost basis - cost of sales
```

Only charges marked `cost_capitalised` enter inventory. Transport, security,
travel and the rest do not prepare inventory and stay expenses, flowing through
the expense workflow to their own accounts.

`InventoryValuation::divergence()` remains as a standing check that both sides
are still wired to the same source; a difference now means something has been
rewired to compute its own, which `gold:inventory` reports.

### Historical deals are refused

A lot whose profit was already distributed before the ledger existed cannot be
posted by ordinary working. Reconstructing it means deciding where money nobody
has explained came from, which needs evidence rather than a default, and belongs
to 3H.

## 3D: the company expense workflow

A cost passes through a life before it becomes money:

```
draft -> submitted -> approved -> posted -> paid
                   -> rejected
```

Each step records who took it and when. A rejection records why. A draft is
freely editable and can be thrown away, because nobody has relied on it yet;
everything after that is fixed, and the workflow's own steps are the only things
that may change it.

There is no second expense table and no second expense ledger. The lifecycle
columns were added to `gold_trading_expenses`, and posting hands the expense to
`GoldTradingPoster`, which hands its journal to `JournalPoster`. An expense
cannot reach the books by a route that skips the rules the books are kept by.

```bash
php artisan gold:expense --lot=LOT --category=transport --amount=120 \
    --description="Juba to Nairobi" --payee="Hauler Ltd" --invoice=INV-4471
php artisan expense submit   EXP-20260917-000001
php artisan expense approve  EXP-20260917-000001
php artisan expense post     EXP-20260917-000001 --from=MAIN-BANK
php artisan expense pay      EXP-20260917-000001 --from=MAIN-BANK
php artisan expense show     EXP-20260917-000001
php artisan expense:selftest
```

### Posting and paying are different facts

| Posted with | Entry | Payment status |
|---|---|---|
| `--from=<account>` | Dr expense / Cr cash or bank | paid there and then |
| nothing | Dr expense / Cr 2100 Accrued Expenses Payable | unpaid: the company owes it |

An expense posted against payable is a real cost that has not been paid, and
collapsing the two would hide what the company owes. Paying it later is
Dr 2100 / Cr cash, through the 3B cash system, touching no expense account.

Period-close accrual mechanics are not built here. 2100 is used because an
unpaid cost has to be credited somewhere truthful today, not because 3D closes
periods; that is 3E.

### Capitalised costs (decision D4)

A cost that prepares gold for sale is debited to inventory, not to an expense
account, and `LotCostBasis` reads it back:

```
capitalised cost -> Dr 1100 or 1110 (whichever holds the lot's cost) / Cr cash or 2100
```

Three rules keep it from being counted twice:

- a capitalised cost never appears in `LotResultCalculator`'s expenses;
- an ordinary cost never enters the cost basis;
- a capitalised cost enters the basis only once **posted**, because until then
  the general ledger does not hold it in inventory either.

A cost cannot be capitalised into gold that has already been sold. There is no
inventory left for it to attach to, so it would sit in an asset account nothing
will ever relieve. It is refused, and the message says to treat it as an expense
of the period.

### Corrections

| Situation | Route |
|---|---|
| Posted, unpaid, not owed after all | `expense reverse` — the mirror journal; both entries stay |
| Posted and paid, but booked to the wrong account | `expense reclassify` — Dr right / Cr wrong, cash untouched |
| Posted and paid, supplier refunding | record the refund as a receipt |

A paid expense is never reversed. The money left the account and the bank
statement will go on saying so; the books must not pretend otherwise.

### Controls

- Nobody may approve a claim they submitted. Waiving that needs a Super Admin
  and a recorded reason, exactly as bank reconciliation sign-off does
  (`ACCOUNTING_ALLOW_SELF_APPROVAL`, false by default).
- Posting the same expense twice hands back the journal it already has. Paying
  it twice hands back the payment. The same supplier invoice cannot be claimed
  twice, at the database and with a message naming the claim that has it.
- Evidence is stored on the private `expense-private` disk under `storage/`,
  hashed when filed, never under the web root, and read only through
  `ExpenseEvidenceStore`, where the permission check cannot be forgotten.
- Costs entered before this workflow existed are marked `recorded`. They still
  count for exactly what they always counted for; they are simply not claimed to
  have been approved by anybody.

## 3E: the realized trading result

What the company actually made, read out of its own books:

```
revenue          credits on 4000, per deal
cost of sales    debits  on 5000, per deal
                 -------------------------
gross profit
ordinary costs   debits on 6000-6899, per deal
                 -------------------------
realized result
```

Nothing is read from a wallet, a distribution, a precomputed investor return or
a second calculation kept alongside the ledger. If the accounting is wrong the
answer is wrong in a way somebody can find, rather than quietly right for the
wrong reason.

6900 FX and 7000 Investor Profit Share are deliberately outside that range. FX
is a consequence of holding currency rather than of trading gold, and the
investors' share is a distribution *of* the result — including it would let the
answer depend on itself.

Reversals need no special handling: a reversing journal posts the mirror of what
it undoes, so summing every line nets it out.

```bash
php artisan trading result                       # the company, all periods
php artisan trading result --period=2026-09
php artisan trading result BOR-2026-10-02
php artisan trading expenses-final LOT
php artisan trading realize LOT
php artisan trading:selftest
```

### Interim is not final

Four things happen in order and are not the same event:

| | |
|---|---|
| trading complete | the last gram has been sold |
| costs complete | `expenses_finalised_at` is set, and no recorded cost is still outside the ledger |
| result recorded | `realized_at` is set: somebody has stood behind the figures |
| period closed | 3E respects this; it does not implement it |

A result is **final only when all of the first three hold**. Until then it is
reported with what is holding it up — "Interim: 75.00 of recorded costs have not
reached the ledger yet" — and recording is refused. A sold-out deal is not final
merely because the gold has gone.

Declaring costs complete and recording the result are separate acts, under
separate permissions (`expense.approve` and `result.record`), because collapsing
them would mean the only check on a final figure was the wish to produce one.
Once recorded, the figures are immutable: a late cost is posted to the ledger in
the period it belongs to, and the deal is not reopened.

### Company and deal

Deal results are added up; costs belonging to no deal are shown separately
rather than pushed into one, so no deal's number depends on how the overheads
were shared out. The report proves the addition lost nothing: every posting on
4000 and 5000 must be attributable to a deal, or it says it does not reconcile.

### FX: not required here, and not invented

4100 and 6900 exist in the chart of accounts, but **no FX realization rule is
implemented anywhere**, and 3E does not add one. It does not need one: every
stored amount is already USD (`amount_usd`, `gross_proceeds_usd`,
`total_cost_usd`) and every journal posts in USD, so a realized result is a sum
of USD figures.

An FX rule becomes necessary the moment an amount is *settled* at a rate
different from the one it was *booked* at — a receivable on 1300 collected later,
or a payable on 2100 paid at a new rate. Nothing in the system does that yet. If
it ever does, the difference belongs on 4100 or 6900 and needs a formal decision
first; it must not be absorbed into a trading result.

### Historical deals

`BOR-2026-09-08` and `BOR-2026-10-02` report a realized result of 0.00 and a
stage of `historical`, with the reason stated. That is correct and deliberate:
the ledger holds nothing for them, and their recorded 943.31 and 861.06632 are
attribution records rather than accounting until the backfill establishes where
the money came from. Recording a realized result for either is refused.

## 3F, first half: period close

Closing a period is the company saying the figures in it are the ones it will
answer for. It is refused while anything in the month is still moving:

| Blocker | Why |
|---|---|
| trial balance out of balance | a period that does not balance is not a period |
| a sold-out deal whose result is interim | its figure would change after the close |
| an expense dated in the month still draft / submitted / approved | it would land in the month after the close, or be lost |
| an unmatched bank statement line in the month | money the books have not explained |

A deal still holding gold does **not** block a close. Its result belongs to
whichever month it finally sells in and carries forward.

```bash
php artisan period status
php artisan period check  2026-09         # the month-end to-do list; read it as often as you like
php artisan period close  2026-09 --reason="September month end"
php artisan period reopen 2026-09 --reason="..."   # Super Admin, reason recorded, close kept as history
php artisan period:selftest
```

The close records who, when, why, a reference (`PCL-…`) and a snapshot of what
the ledger said: the company's trading result, the trial balance, the deals
counted. Once closed, the record and its snapshot are immutable and nothing
further may be posted. Reopening follows the same discipline as every other
waived control: a Super Admin, a reason, both recorded, and the original close
kept as history.

**The snapshot records the company's own result only.** It carries the line
`investor_allocation: not determined at close; requires an approved allocation
rule`, deliberately. What share of a result becomes an investor's is the second
half of 3F, and it is not built, because the rule it needs is not defined — see
below.

### An approved claim can now be withdrawn before posting

Found by the close blocker: an approved-but-unposted expense had no exit at all
(not draft, so not deletable; not submitted, so not rejectable; not posted, so
not reversible). `reject()` now also accepts an approved claim that has not
reached the ledger. Approval is a decision about a claim; posting is what makes
it a cost, and a duplicate can still come to light between the two.

## 3F, second half: the investor allocation policy (calculation only)

What share of a period's realized result is the investors', under the approved
policy. **A calculation and a report. Nothing is distributed**: no journal, no
investor ledger entry, no wallet. `InvestorAllocation::forPeriod()` produces the
figure a later, separately approved distribution would act on, and every reason
it cannot yet be acted on.

```bash
php artisan allocation preview 2026-09
php artisan allocation:selftest
```

### The policy, decision by decision

| | Rule | Where |
|---|---|---|
| basis | sum of the period's **finalized, recorded** deal results, each split under **that deal's own recorded terms** | `LotResult` (immutable) → `splitResult()` |
| overheads | costs belonging to no deal **do not reduce the pool**; shown, excluded | `company_overheads_excluded_usd` |
| eligibility | a recorded `realized_at` in this period; nothing interim, nothing still holding gold, nothing historical | `eligible` / `ineligible` |
| missing terms | an eligible deal with no investor share **refuses the whole allocation, by name** | `blocked` |
| no capital | an eligible deal with no investor capital recorded **also refuses** — not silently treated as the company's | `blocked` |
| loss | a negative pool **refuses**: `Negative investor allocation requires an approved loss policy.` | `loss_policy_status` |
| reserve | **0**, from `accounting.allocation.reserve_percent`; declared in one place, never wired into a formula | `reserve_usd` |
| the 100% | a per-deal term on the two historical deals, **never a default** | test 9 |
| sequence | a distribution follows a close; an open period is not distributable | `refusals` |

### The formula

```
for each eligible deal d (recorded LotResult in the period, terms present, capital present):
    pool_basis(d) = net_profit(d)            if expense_policy = deal_before_split
                  = gross_profit(d)          if expense_policy = company_share
    for each investor i on d:
        capital_share(i,d) = capital(i,d) / Σ capital(d)
        profit_share(i,d)  = allocation.share_percent(i,d) ?? lot.investor_share_percent
        allocation(i,d)    = pool_basis(d) × capital_share(i,d) × profit_share(i,d)
    investor(d) = Σ_i allocation(i,d)

gross_pool = Σ_d investor(d)
reserve    = gross_pool × reserve_percent          (= 0 by policy)
pool       = gross_pool − reserve
```

The figures come from the **recorded** `LotResult`, not a recomputation. The
recorded result is immutable, so the allocation cannot drift from what was
stood behind.

### Account 7000 is an outcome, never an input

The company's result is read from 4000, 5000 and 6000–6899 first; this
calculation is applied to it afterwards. 7000 is outside the trading range by
construction (see 3E) and this service never reads it. Test 13 posts 500.00 to
7000 inside the period and proves neither the trading result nor the pool
moves. When a distribution is eventually built, `Dr 7000 / Cr 2010` is the
appropriation *of* the result, posted after the result is established — and
the result stays the same number.

### What is not read

`investment_plans`, `profit_percentage`, `plan_duration`, `investment_profit_logs`.
Test 14 checks the compiled source of the allocation and result-calculator
classes for any reference (comments stripped), and where the legacy table
exists inserts a 99% plan at runtime and proves the pool does not move.

## Not in 3F

The distribution itself (posting `Dr 7000 / Cr 2010`, the investor ledger
credit, idempotency by period / investor / reference), reports (3G),
historical backfill (3H) and admin screens (3I).

The suspense question is unchanged: 1090 is still empty, and the 2,000 investor
credit and the two gold purchases remain unexplained until records say otherwise.
3B gives the company somewhere real for money to have come from and gone to,
which is what will eventually make those answerable — but it does not answer
them, and nothing here has been plugged into cash or bank to make anything
balance.
