# Investor ledger (Phase 1)

The ledger is the accounting truth about what the company owes an investor.
Statements, receipts and the dashboard will all be read off it, so that they can
never disagree with each other.

## Three buckets

| Bucket | Meaning |
|---|---|
| `available` | USD the investor can spend or withdraw right now |
| `profit` | Trading profit credited to them, also spendable |
| `committed` | Their capital currently inside an open gold deal |

```
available + profit + committed = total investor position
```

A movement is either **external** — money genuinely entering or leaving the
relationship — or **internal**, shuffling money between the investor's own
buckets. Internal movements must have legs that sum to zero, which
`LedgerRecorder` enforces at the write. An internal movement therefore *cannot*
change the total position; it is not a convention, it is a constraint.

```
opening position + external in - external out = closing position
```

## Append only

`LedgerEntry` throws on update and delete. Mistakes are fixed by posting a
further movement that references the one it corrects, so both stay visible.
The guard binds the application, not the database; add a `BEFORE UPDATE` trigger
if that matters.

The one sanctioned exception is `ledger:backfill --rebuild`, which discards
entries it can derive again from the transactions table. It refuses once any
correction exists, because a correction was a human decision that exists nowhere
else.

## Why the ledger pulls instead of being pushed to

The platform writes transactions with the query builder (`DB::table('transactions')`)
at all seventeen call sites and never through the Eloquent model, so there are no
model events to hook. Nothing can push a movement into the ledger at the moment
it happens.

So the ledger pulls: `ledger:sync` runs every minute, and the gold commands call
it directly so a deal's movements appear at once. Reconciliation check 2 exists
to notice if the pull ever falls behind. A ledger that lags visibly is better
than one that is silently incomplete.

## Commands

```bash
php artisan ledger:backfill bhavin          # build from existing transactions
php artisan ledger:backfill --all --rebuild
php artisan ledger:sync                      # catch up (also on the schedule)
php artisan ledger:check bhavin              # the four reconciliation checks
php artisan ledger:fix-buckets bhavin --dry-run
php artisan ledger:verify bhavin             # full Phase 1 acceptance run
php artisan gold:statement bhavin            # cash book, read off the ledger
```

## The four checks

1. **Internal** — running balances follow from the movements, and internal legs net to zero.
2. **Transactions** — nothing has happened that the ledger has not seen, and no entry cites a transaction that does not exist.
3. **Wallets** — closing `available`/`profit` equal `user_wallets.balance`/`.profit_balance`.
4. **Allocations** — closing `committed` equals the sum of open locked allocations.

Nothing investor-facing may be produced for an investor who fails any of them.

## The bucket correction

Closing a deal used to return the whole commitment to the available balance,
even when part of it had been taken from the profit balance. The total was always
right; the split was not, so earned profit quietly became capital.

`gold:distribute` now returns each part to the bucket it came from
(`CapitalAllocation::returnSplit()`). History is put right by
`ledger:fix-buckets`, which posts a `bucket_correction` rather than editing what
was recorded — the original movement and its correction both stay on the record.

## What Phase 1 deliberately does not do

No PDF, no statements UI, no receipts, no admin screens. Those are Phases 2-5
and they all read from this table.
