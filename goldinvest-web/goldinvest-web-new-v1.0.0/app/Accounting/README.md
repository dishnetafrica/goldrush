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

## Not in 3A

Cash and bank accounts (3B), purchase/sale/COGS posting (3C), expense workflow
(3D), period close (3E), the distribution bridge (3F), reports (3G), historical
backfill (3H) and admin screens (3I). 3A is the foundation those sit on.
