# Gold trading module (company side)

First vertical slice of the company-side accounting described in the gap analysis.
It records what the company does with pooled investor capital: buy gold, refine it,
sell it, pay costs, and work out what was actually earned.

## The principle it enforces

Investor money is a **liability** of the company. Gold is a **company asset**.
An investor never owns a gram of a specific lot. The only link between the two sides
is `gold_capital_allocations`, which records whose capital funded a lot **for attribution
only**: creating an allocation never changes `user_wallets` or writes a `transactions` row.

## What it is not, yet

- No admin screens. Everything is recorded through artisan commands for now.
- No double-entry general ledger, no P&L or balance sheet.
- No automatic investor payout. Profit distribution stays a manual admin action until
  the distribution engine replaces GoldInvest's fixed-return profit engine.
- Money is handled as PHP floats rounded to 8 decimal places. That is accurate at this
  scale, but a move to integer minor units or bcmath is required before production.

## Tables

| Table | Holds |
|---|---|
| `gold_lots` | A purchase: grams, local price, FX rate, cost in both currencies |
| `gold_processings` | Refining passes: input grams, waste grams, output grams, purity |
| `gold_sales` | Sales out of a lot: grams, price basis, price per gram, proceeds |
| `gold_trading_expenses` | Transport, refining, assay, security, commission, operating costs |
| `gold_capital_allocations` | Which investor capital funded which lot (attribution only) |
| `gold_lot_results` | Frozen result once a lot is closed and approved |

## How profit is calculated

`App\GoldTrading\Services\LotResultCalculator` applies these rules:

1. **Waste is a loss of grams, not an expense.** It raises the cost of every gram that
   survives refining. 2,000 USD spent on 25 g that cleans down to 23 g is a cost basis of
   **86.96 USD/g**, not 80.00 USD/g.
2. **Proceeds are not profit.** Profit is proceeds minus the cost of the gold actually
   sold, minus the costs of doing the deal.
3. **Only gold that has been sold is charged to profit.** Unsold grams stay as inventory
   valued at cost, so a partly sold lot does not overstate earnings.
4. **Break-even price per gram** is reported so a sale can be judged before it is agreed.

## Deal terms

Terms are negotiated **per deal**, because the split depends on market demand and on
the source of the gold. They are stored on the lot, and on a single allocation when one
investor's terms differ from the rest of that deal. Nothing is assumed: if no share is
recorded, the report says so instead of inventing one.

Two percentages are at work and must not be confused:

| Percentage | Meaning |
|---|---|
| `capital_share_percent` | how much of the lot's capital that investor funded |
| `profit_share_percent` | the agreed cut of the profit their capital earned |

An investor's profit is `pool × capital_share × profit_share`.

`expense_policy` on the lot decides what the pool is:

| Policy | Pool | Effect |
|---|---|---|
| `deal_before_split` (default) | net profit, after expenses | both sides carry costs in proportion to the split |
| `company_share` | gross profit, before expenses | investors are paid first and the company absorbs every cost |

The difference is invisible while expenses are zero and large as soon as they are not.
On the Boromedina deal at a 60% investor share, 500 USD of costs would leave the company
177.32 USD under the first policy and **minus 122.68 USD** under the second.

## Commands

```bash
# Record the Boromedina 08-09-2026 deal exactly as the source spreadsheet states it
php artisan gold:record-boromedina --investor=USERNAME --trx=TRXID

# Record the terms agreed for that deal
php artisan gold:set-terms BOR-2026-09-08 --investor-share=60 --expense-policy=deal_before_split --note="demand in Juba, direct source"

# Give one investor different terms inside the same deal
php artisan gold:set-terms BOR-2026-09-08 --investor=USERNAME --investor-share=55

# Report any lot: weights, cost, proceeds, real profit, break-even, split
php artisan gold:lot-report BOR-2026-09-08
```

## Boromedina 08-09-2026, as recorded

| Line | Value | Source |
|---|---|---|
| Purchase price | 600,000 SSP/g | spreadsheet C10 |
| FX rate | 7,500 SSP per USD | C7 |
| Capital deployed | 2,000 USD = 15,000,000 SSP | C15, C16 |
| Gold purchased | 25.00 g | C18 |
| Cleaning waste (8%) | 2.00 g | C20 |
| Refined gold | 23.00 g | C22 |
| Sale price, Juba | 127.97 USD/g (international 142.19 less 10%) | C6, C25 |
| Gross proceeds | 2,943.31 USD | C28 |
| Cost of gold sold | 2,000.00 USD | derived |
| **Net profit** | **943.31 USD (47.17% on capital)** | derived, absent from the sheet |

The spreadsheet labels C28 "Total Net Profit". It is gross proceeds. Distributing a share
of 2,943.31 instead of 943.31 would pay out roughly three times what the deal earned.
No expenses are recorded for this deal; transport, refining, security and travel will
reduce the 943.31 further.
