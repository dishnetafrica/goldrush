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

### A known disagreement, reported rather than hidden

Directly attributable processing costs are capitalised into inventory here
(decision D4). `LotResultCalculator`, which produces the investor-facing deal
result, treats them as a deal expense instead.

While those charges are zero the two agree exactly, which is the case for both
existing deals. When they are not, net profit on a fully sold lot is the same
either way, but the split between cost of sales and expenses differs - and so
does the value of anything still unsold. `InventoryValuation::divergence()`
computes the gap and `gold:inventory` prints it. It has to be resolved before a
period is closed on a lot carrying such a charge.

### Historical deals are refused

A lot whose profit was already distributed before the ledger existed cannot be
posted by ordinary working. Reconstructing it means deciding where money nobody
has explained came from, which needs evidence rather than a default, and belongs
to 3H.

## Not in 3C

Expense workflow
(3D), period close (3E), the distribution bridge (3F), reports (3G), historical
backfill (3H) and admin screens (3I).

The suspense question is unchanged: 1090 is still empty, and the 2,000 investor
credit and the two gold purchases remain unexplained until records say otherwise.
3B gives the company somewhere real for money to have come from and gone to,
which is what will eventually make those answerable — but it does not answer
them, and nothing here has been plugged into cash or bank to make anything
balance.
