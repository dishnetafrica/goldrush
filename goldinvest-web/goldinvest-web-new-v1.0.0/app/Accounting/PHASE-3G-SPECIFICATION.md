# Phase 3G — Reporting & Management Specification

**Status: APPROVED WITH FOUR DECISIONS (incorporated below). Implementation authorised and delivered under this text.**

Decisions incorporated at the approval gate:

1. **Appropriation dating: approved as specified.** May's trading result stays in May; the distribution declared on 5 June is recorded in June; the June P&L shows 7000 with a reference to the May source period; May is unchanged. Added: a *source-period* reference and filter on the P&L (`source_period=`) so management can answer "which earlier trading results were appropriated in this period?". It narrows the listing only; the 7000 figure and the trading result are the GL's whatever the filter. No alternative accounting calculation exists.
2. **Investor Liability Report: two views.** The admin view (all investors, capital payable, profit payable, ledger reconciliation, wallet control, distribution references) and the investor's own view (their row only: capital position, profit position, movements, distributions, withdrawals, statements). The investor view carries a fixed key list and the self-test asserts that no other investor, no company GL figure, no company cash, no gold lot, no supplier cost and no company-wide P&L can appear in it.
3. **G1/G2 differences are shown, never suppressed.** On real data the controls read, for example, `Investor Profit Payable - GL 2010 0.00 / Investor Ledger Profit 1,804.37632 / Unreconciled historical difference -1,804.37632 PRE-BACKFILL` and `Investor ledger capital 2,000.00 / GL 2000 0.00 / Difference -2,000.00 PRE-BACKFILL`, captioned *"Pre-backfill reconciliation difference - historical attribution/funding not yet posted to company GL. D2/D3 open."* No flag hides them and no journal is manufactured to close them. 1090 remains visible and untouched.
4. **Period-close pack: included in 3G.** One private, immutable, hashed PDF per close reference containing the trial balance, P&L, balance sheet, cash flow, period close report, controls, and the allocation/distribution summary; its header carries period, status, close reference, closed by/at, generated at, scope, FINAL status, control results and the SHA-256 note. It is a reporting snapshot, not an accounting record.

Tightened before implementation: **the GL is the financial authority; `gold_lot_results` is the recorded deal-result artifact.** For a final deal the recorded result and the GL-derived result are both shown; a difference is a CONTROL EXCEPTION, never an automatic correction. **G3 stays open**: no retained-earnings sweep is implemented; the balance sheet carries a computed current-result line.

Governing rule, from which everything below follows:

> Every accounting report derives its financial truth from the posted general
> ledger and the recorded accounting results — never from investor wallets,
> legacy precomputed profit fields, or the old investment-plan profit engine.

3G is a reporting layer over an accounting system that is frozen. A report may
read; it may never write, recompute, or reinterpret. Where a report would need
a fact the ledger does not hold, the report says so rather than sourcing it
from somewhere weaker.

---

## 0. The twelve invariants, as they bind reports

| # | Invariant (README) | What it means for a report |
|---|---|---|
| 1 | Closed period inviolate | A closed period's figures are read from its close snapshot and the ledger; both must agree, and the report shows if they do not |
| 2 | Result belongs to its trading period | P&L for a period never includes appropriations declared in a later period |
| 3 | Appropriation dated when declared | 7000/2010 appear in the period of the *declaration*, shown below the trading result with the source period named |
| 4 | 7000/2010 only after result + close + allocation | A distribution row on any report links to its close reference and allocation snapshot |
| 5 | 7000 never feeds the trading result | Realized result = 4000 − 5000 − (6000–6899). 7000 is a separate section. No report subtracts 7000 to get a trading figure |
| 6 | 2010 = Σ investor profit credits | The Investor Liability Report proves it every time it renders |
| 7 | Capital stays in 2000 | Capital and profit liabilities are never summed into one "investor balance" line |
| 8 | Investor ledger is the only investor trail | Investor history comes from `investor_ledger_entries`; wallets are a control figure only |
| 9 | Historical attribution stays historical | Shown in a labelled section, excluded from every accounting total |
| 10 | 1090 untouched | Shown as its own line wherever assets are shown; never netted, never hidden |
| 11 | Distribution immutable, reversal only | Reports show posted and reversed distributions both |
| 12 | September open, undistributed | Reports must render correctly on an empty ledger and say the ledger is empty |

---

## 1. Source-of-truth matrix

Every material figure on every report resolves to exactly one row here. A
figure with no row is not a reportable figure.

| Figure | Authoritative source | Calculation | Never from |
|---|---|---|---|
| Cash on hand | `journal_lines` on accounts under control `cash`, type `cash` (1000-series) | Σ debit − Σ credit | `cash_accounts` has no balance column; none exists |
| Bank | `journal_lines` on bank-type cash accounts (1010-series) | Σ debit − Σ credit | bank statement lines (those are evidence, not balance) |
| Suspense | `journal_lines` on 1090 | Σ debit − Σ credit | — |
| Gold inventory, unrefined | `journal_lines` on 1100, per `gold_lot_id` | Σ debit − Σ credit | `gold_lots.total_cost_usd` |
| Gold inventory, refined | `journal_lines` on 1110, per `gold_lot_id` | Σ debit − Σ credit | — |
| Inventory, quantity | `LotCostBasis::forLot()` (`remaining_grams`) | refined − sold | — |
| Inventory, unit cost | `LotCostBasis::forLot()` (`cost_per_refined_gram_usd`) | basis ÷ refined grams | any recomputation |
| Receivables | `journal_lines` on 1300 | Σ debit − Σ credit | `gold_sales` |
| Investor capital payable | `journal_lines` on 2000 | Σ credit − Σ debit | wallet `balance`, `gold_capital_allocations` |
| Investor profit payable | `journal_lines` on 2010 | Σ credit − Σ debit | wallet `profit_balance` |
| Accrued expenses | `journal_lines` on 2100 | Σ credit − Σ debit | `gold_trading_expenses.payment_status` |
| Withdrawals payable | `journal_lines` on 2200 | Σ credit − Σ debit | — (see §10, gap G1) |
| Owner's capital, retained earnings | `journal_lines` on 3000, 3100 | Σ credit − Σ debit | — |
| Revenue | `journal_lines` on 4000, optionally per lot | Σ credit − Σ debit | `gold_sales.gross_proceeds_usd` |
| COGS | `journal_lines` on 5000, optionally per lot | Σ debit − Σ credit | `LotResultCalculator` |
| Ordinary trading expenses | `journal_lines` on 6000–6899 | Σ debit − Σ credit | `gold_trading_expenses.amount_usd` |
| FX gain / loss | `journal_lines` on 4100 / 6900 | as posted | any computed FX; none exists |
| Investor appropriation | `journal_lines` on 7000 | Σ debit − Σ credit | `investor_distributions.pool_usd` (that is the *claim*; 7000 is the *posting*; both shown, reconciled) |
| Realized trading result, company | `RealizedTradingResult::forCompany(scope)` | 4000 − 5000 − (6000–6899) | anything involving 7000 |
| Realized result, per deal, **final** | GL-derived (4000 − 5000 − (6000–6899) per lot); `gold_lot_results` with `realized_at` set is the recorded artifact shown beside it as a **control** | GL first; recorded compared; difference = CONTROL EXCEPTION | treating the artifact as the authority; live "correction" of either |
| Realized result, per deal, **interim** | `RealizedTradingResult::forLot()` | live, labelled interim | — |
| Deal terms | `gold_lots.investor_share_percent`, `expense_policy`, `gold_capital_allocations.share_percent` | as recorded | `investment_plans` |
| Allocation | `InvestorAllocation::forPeriod()` for preview; `investor_distributions.snapshot` once distributed | policy | any recomputation of a distributed period |
| Distribution | `investor_distributions` + `_lines` | as posted | — |
| Investor position (available / profit / committed) | `investor_ledger_entries`, last entry per investor | running balances | `user_wallets` |
| Investor transaction history | `investor_ledger_entries` | chronological by `seq` | `transactions` (source, not trail) |
| Wallet balance | `user_wallets` | as stored | **control figure only, never a total** |
| Period status, close evidence | `accounting_periods` | as recorded | — |
| Bank reconciliation status | `bank_reconciliations`, `bank_statement_lines` | as recorded | — |
| Expense pipeline | `gold_trading_expenses.status`, `payment_status` | as recorded | — |
| Historical attribution | `gold_lot_results` with `status = distributed` and `realized_at` null; `gold_profit_distributions` | as recorded, **labelled**, excluded from totals | — |

Three sources are explicitly **forbidden** as accounting truth: `user_wallets.*`,
`investment_plans.*` / `investment_profit_logs`, and any figure the legacy
`gold:distribute` path wrote into `gold_lot_results.investor_profit_usd` for a
non-realized row.

---

## 2. State semantics — one table, applied everywhere

| State | Treatment on every report |
|---|---|
| **Open period** | Figures are live and labelled *"interim — period open"*. Totals render; nothing is called final. |
| **Closed period** | Figures come from the ledger; the close snapshot is shown beside them; a mismatch is a **control exception**, shown red, never reconciled away. |
| **Reopened period** | Treated as open, **with** its prior close reference and snapshot shown as history: *"closed as PCL-… on …, reopened on … by …: reason"*. |
| **Interim deal result** | Shown with `stage` and `qualification` from `RealizedTradingResult` — *"trading complete, expenses pending: 75.00 not yet in the ledger"*. Never summed into a "final" line. |
| **Unsold gold** | Inventory at cost on the balance sheet (1100/1110); grams and unit cost from `LotCostBasis`; **no market revaluation** — none is implemented and none is invented. |
| **Unposted approved expense** | Not in the P&L (not in the ledger). Listed on the Period Close Report as a blocker and on the dashboard as *outstanding expenses*. |
| **Accrued expense** (posted, unpaid) | In the P&L (posted to 6xxx) and on the balance sheet as 2100. |
| **Investor distribution** | In the P&L of the period *declared in*, below the trading result, citing the source period. On the balance sheet as 2010. Reversed ones shown with both references. |
| **Investor withdrawal** | Investor ledger event `withdrawal_requested` / `withdrawal_paid`. **No GL posting exists today** (gap G1, §10). Reported from the ledger, flagged *"not yet bridged to GL"*. |
| **Cash/bank transfer** | Journal with source_type `cash` and both lines on cash-control accounts. **Excluded** from cash-flow operating/investing/financing; shown once in a *transfers between company accounts* memo line. |
| **Reconciliation difference** | Shown as the number, signed, with the unmatched lines listed. Never absorbed. |
| **Historical attribution** | Own section, heading *"HISTORICAL ATTRIBUTION — NOT POSTED TO COMPANY GL"*, figures from `gold_lot_results` / `gold_profit_distributions`, **excluded from every accounting total**, with the D2/D3 note. |
| **1090 Suspense** | Own line on the balance sheet and dashboard whenever its balance is non-zero *or* whenever the D2/D3 decision is open (i.e. always, until resolved), with the text *"unresolved: origin of funds not established (D2/D3)"*. |

---

## 3. Reports

Conventions for all reports: amounts are 8dp internally and 2dp on screen with
the exact figure on hover / in export; every period-filtered report accepts
`period=` (code) **or** `from=`/`to=` (dates), never both; every report shows
the scope it rendered for and whether it is interim or final; every report
that has a control check shows it as a coloured status line with the exact
difference.

### 3.1 Trial Balance

**Source:** `TrialBalance::build(asAt, periodId)` — extended (read-only) to add
opening balances. Opening = `balanceOf(code, periodStart − 1 day)`.

| Column | Source | Calculation |
|---|---|---|
| Account code, name, type, normal | `accounts` | — |
| Opening | journal lines dated < period start | signed by normal balance |
| Debits / Credits | journal lines in period | Σ |
| Closing | — | opening + debits − credits (debit-normal) or opening + credits − debits (credit-normal) |
| Status line | Σ closing debits vs Σ closing credits, and Σ period debits vs Σ period credits | both must be 0.00000000; either not → **unbalanced**, red |

Filters: period, as-at date, account type, include zero-balance accounts.
Journal drill-down per account.

### 3.2 Profit & Loss

Two sections, and a rule between them.

```
TRADING RESULT (period)                        source
  Gold sales revenue                   4000    Σ credit − Σ debit
  Cost of gold sold                    5000    Σ debit − Σ credit
  ─────────────────────────────────
  Gross trading profit
  Ordinary deal expenses           6000–6899   by account, each listed
  ─────────────────────────────────
  REALIZED TRADING RESULT                      = 4000 − 5000 − (6000–6899)
  FX gain / loss                  4100 / 6900  shown only if non-zero; never computed

APPROPRIATION (declared in this period)        source
  Investor profit share                7000    Σ debit − Σ credit
    of which for period X: DIST-X-…            investor_distributions.snapshot
  ─────────────────────────────────
  RESULT AFTER APPROPRIATION                   = realized − 7000 (+4100 − 6900)
```

**Demonstration that 7000 does not alter the trading result:** the
`RealizedTradingResult` service never reads 7000 (range 6000–6899 is closed
above at 6899 by constant). The P&L renders the trading result line *before*
reading 7000 at all, from that service, and the appropriation section from a
separate query. The 3G self-test posts Dr 7000 and asserts the first section's
figure is unchanged — the same proof as allocation test 13, repeated at the
report boundary.

**Where the appropriation for May appears:** on the *June* P&L (declared 5
June), captioned *"appropriation of May 2025 result, DIST-ST-2025-05-000002"*.
The May P&L shows the realized result and, in a footnote, *"appropriated in
June under DIST-…"*. Neither period's trading result changes.

Filters: period or date range; per-deal breakdown toggle (rows from
`RealizedTradingResult::forCompany()['lots']`, each with `stage`).

### 3.3 Balance Sheet

```
ASSETS                                         source
  Cash on hand                    1000-series  Σ dr − Σ cr
  Bank                            1010-series  Σ dr − Σ cr
  Unidentified receipts (suspense)     1090    Σ dr − Σ cr   ← always shown, D2/D3 note
  Gold inventory — unrefined           1100    Σ dr − Σ cr   (grams, unit cost from LotCostBasis)
  Gold inventory — refined             1110    Σ dr − Σ cr
  Receivables from buyers              1300    Σ dr − Σ cr
LIABILITIES
  Investor capital payable             2000    Σ cr − Σ dr   ← never merged with 2010
  Investor profit payable              2010    Σ cr − Σ dr   ← reconciles to ledger profit credits
  Accrued expenses payable             2100    Σ cr − Σ dr
  Withdrawals payable                  2200    Σ cr − Σ dr   (gap G1: 0.00 until bridged)
EQUITY
  Owner's capital                      3000    Σ cr − Σ dr
  Retained earnings                    3100    Σ cr − Σ dr
  Current period result (unclosed)             P&L result after appropriation for periods not yet swept to 3100
```

**Accounting equation, proved on render:** Σ assets − (Σ liabilities + Σ equity
+ current result) must be 0.00000000; otherwise a red control line with the
difference. This is the trial balance restated by type, so it holds whenever
the trial balance does — and the report says so if it does not.

**How investor money is represented:** an investor's total position (available
+ profit + committed) is a liability of the company split across 2000 and
2010. It is never an asset, never gold, never a share of a lot. The balance
sheet shows gold as the company's inventory; the Investor Liability Report is
where the liability is broken down by investor.

Note on sweeping to 3100: no period-close journal to retained earnings exists
today. The balance sheet therefore carries a computed *"current period
result"* line. Whether close should post a sweep is a 3G open question (§11),
not something 3G decides.

### 3.4 Cash Flow

**Source:** every journal line on a cash-control account (1000/1010-series),
classified by the *counter-account* of its journal.

| Class | Counter-account | Direction |
|---|---|---|
| Operating — gold purchases | 1100 | out |
| Operating — capitalised processing | 1100 / 1110 (via expense) | out |
| Operating — gold sales | 4000 or 1300 | in |
| Operating — expenses paid | 6000–6899, or 2100 when settling an accrual | out |
| Financing — investor capital received | 2000 | in |
| Financing — investor profit paid | 2010 | out — **G1: no such posting exists yet** |
| Financing — owner capital | 3000 | in / out |
| Unclassified — suspense | 1090 | shown separately, never netted |
| **Transfers between company accounts** | another cash-control account | **excluded from all classes; memo line only** |

**No double counting:** a transfer journal has both its lines on cash-control
accounts. The classifier detects *"every line of this journal is a cash-control
account"* and routes the journal to the memo line exactly once. The self-test
posts a 1,300.00 transfer and asserts net operating + investing + financing is
unchanged and the memo line shows 1,300.00 once.

**Reconciliation, proved on render:** opening cash+bank (Σ cash-control
balances at period start − 1 day) + Σ classified movements + Σ suspense
movements = closing cash+bank; difference must be 0.00000000.

Investor capital receipts vs investor profit payments are distinguished by
counter-account (2000 vs 2010). Until G1 is closed, the profit-payments line
renders 0.00 with the caption *"investor withdrawals are in the investor
ledger but not yet bridged to the GL"* — the gap is shown, not hidden.

### 3.5 Gold Trading / Lot Performance

Per lot, per period or all-time. Two source families, kept in two column groups.

| Group | Column | Source |
|---|---|---|
| Physical | Purchased grams, waste grams and %, refined grams, sold grams, unsold grams | `LotCostBasis::forLot()` |
| Cost | Purchase cost, capitalised processing, **cost basis**, cost/refined gram, COGS, inventory at cost | `LotCostBasis::forLot()` — and the GL 1100/1110/5000 per lot shown beside, with `InventoryValuation::divergence()` as the control |
| Result | Revenue (4000 per lot), COGS (5000), ordinary expenses (6000–6899 per lot), realized result, stage | `RealizedTradingResult::forLot()`; if `realized_at` set, the recorded `gold_lot_results` figures are shown as the final figures and the live ones as a control |
| Terms | investor share %, expense policy, capital committed | `gold_lots`, `gold_capital_allocations` |

Capitalised processing appears **once**, inside the cost basis. The expenses
column reads 6000–6899 only; 6010 is expensed processing, and a lot with a
capitalised charge shows 0.00 there for it. The self-test asserts that for the
D4 fixture the report shows basis 2,100.00, expenses 0.00, and that basis +
expenses = purchase + capitalised + expensed with nothing counted twice.

Historical lots render in their own section under the attribution heading,
with the `gold_lot_results` figures and *no* GL columns (there are none).

### 3.6 Investor Liability Report

The chain, in this order and with these sources, per investor and in total:

```
CAPITAL LIABILITY          2000 per investor?  → see G2. Company total from 2000; per-investor from the
                                                 investor ledger available+committed buckets, with
                                                 Σ per-investor vs 2000 as the control.
TRADING ALLOCATION         investor_distributions.snapshot (posted), InvestorAllocation preview (open)
PROFIT PAYABLE             2010 company total; per investor from ledger profit bucket; Σ vs 2010 as control
ACTUAL PAYMENT/WITHDRAWAL  investor ledger withdrawal events; GL side is G1
```

Columns per investor: opening available / profit / committed; period
movements by ledger event type (`deposit`, `profit_credited`,
`capital_allocated`, `capital_returned`, `withdrawal_*`, `bucket_correction`);
closing buckets; **wallet balance shown in a final "control" column with the
difference to the ledger, which must be 0.00000000**.

Controls proved on render:
- Σ investor ledger profit buckets = 2010 balance (invariant 6);
- Σ (available + committed) = 2000 balance (subject to G2, §10);
- every `investor_distribution_lines` row has a matching ledger entry (`ledger_entry_id`) of the same amount;
- wallet = ledger per investor.

The report **never** shows grams, lots, or "your gold" against an investor. A
distribution line cites the period and the deal terms it was calculated under,
as terms of a share of the company's result, and nothing else.

### 3.7 Period Close Report

One page per period, from `accounting_periods` + the services already built:

| Section | Source |
|---|---|
| Status, close reference, closed by / at, reason; reopen history | `accounting_periods` |
| Blockers (if open) | `PeriodCloseService::blockers()` |
| Revenue, COGS, expenses, realized result | `RealizedTradingResult::forCompany(period)` |
| Close snapshot beside live figures, with differences | `accounting_periods.snapshot` — **a non-zero difference is a control exception** |
| Deals in the period with stage | `forCompany()['lots']` |
| Allocation pool, refusals, per-investor | `InvestorAllocation::forPeriod()`; snapshot if distributed |
| Distribution reference, journal, lines, reversal | `investor_distributions` |
| Remaining payable | 2010 balance as at period end vs as at now, with the payments between (G1) |
| Reconciliation exceptions | unmatched bank lines in period, incomplete reconciliations |

### 3.8 Management Dashboard

Every tile names its source and its as-at time; every tile is a link to the
report it summarises. No tile is computed on the dashboard itself.

| Tile | Source |
|---|---|
| Cash on hand / Bank | 1000 / 1010-series balances |
| Suspense | 1090 — **shown whenever D2/D3 is open**, with the note |
| Gold held: grams, at cost | Σ `LotCostBasis` remaining over open lots; 1100 + 1110 |
| Realized trading result, current period (interim) | `RealizedTradingResult::forCompany(open period)` — labelled interim |
| Realized trading result, last closed period | close snapshot |
| Investor capital liability | 2000 |
| Investor profit payable | 2010 |
| Current / open period; last close | `accounting_periods` |
| Reconciliation status | per bank account: last completed reconciliation date, unmatched lines count |
| Outstanding expenses | count and Σ of `draft / submitted / approved` |
| Deals awaiting: costs to finalise / results to record / allocation blocked | `RealizedTradingResult` stages, `InvestorAllocation` blocked |
| Ledger health | `ledger:check` outcome per investor, wallet ≠ ledger count |

### 3.9 Reconciliation / control reports

Each renders the check, the two figures, the signed difference, and the
offending rows. All must read 0.00000000 for the books to be called clean.

| Control | Left | Right |
|---|---|---|
| Trial balance | Σ debits | Σ credits |
| Accounting equation | assets | liabilities + equity + current result |
| Inventory | 1100 + 1110 per lot | `LotCostBasis` remaining value per lot |
| Cost of sales | 5000 per lot | `LotCostBasis` COGS per lot (`InventoryValuation::divergence()`) |
| Profit liability | 2010 | Σ investor ledger profit buckets |
| Capital liability | 2000 | Σ investor ledger available + committed (G2) |
| Distribution | Σ `investor_distribution_lines` | 7000 movement for the distribution's journal, and 2010 credit |
| Investor ledger vs wallet | ledger buckets | `user_wallets` per investor |
| Transactions accounted | `transactions` per investor | ledger entries citing them (`LedgerReconciler`) |
| Bank | ledger balance per bank account | statement closing balance, unmatched lines |
| Cash flow | opening + movements | closing |
| Close snapshot vs ledger | `accounting_periods.snapshot` | live figures for that closed period |

---

## 4. Sample calculations (the distribution fixture, real chart of accounts)

Period ST-2025-05, five deals, all sold, all recorded; distribution declared
5 June. Figures are those the staging suite produced.

**P&L, May**
```
4000 Revenue            5,700.00   (2,990 + 1,200 + 650 + 500 + 360)
5000 COGS              (4,100.00)  (2,100 + 800 + 500 + 400 + 300)
6000–6899 expenses         (0.00)
REALIZED TRADING RESULT 1,600.00   ← RealizedTradingResult::forCompany
Appropriation                —     (none declared in May)
```

**P&L, June**
```
Trading result              0.00   (no trading in June)
7000 Investor profit share (1,373.00)  "appropriation of ST-2025-05, DIST-ST-2025-05-000002"
RESULT AFTER APPROPRIATION (1,373.00)
```
May's 1,600.00 is unchanged by June's appropriation. The 227.00 the company
retains is 1,600.00 − 1,373.00 across the two periods, never a single-period
trading figure.

**Balance sheet, after the June distribution**
```
ASSETS
  Bank (DS-BANK)               31,600.00   = 30,000 funding − 4,000 purchases − 100 capitalised + 5,700 sales
  1090 Suspense                     0.00   shown: D2/D3 unresolved
  1100 / 1110 Inventory             0.00   all sold
                                31,600.00
LIABILITIES
  2000 Investor capital payable     0.00   (fixture funded from owner's capital, not investors)
  2010 Investor profit payable  1,373.00   = Σ profit credits 1,233 + 140  ✓
  2100 Accrued expenses             0.00
EQUITY
  3000 Owner's capital         30,000.00
  Current result                  227.00   = 1,600 − 1,373
                                31,600.00  ✓ assets = liabilities + equity
```

**Investor Liability, after the June distribution**
```
                 capital   profit    ledger profit   wallet profit   Δ
st-dist-a           0.00  1,233.00     1,233.00        1,233.00     0.00000000
st-dist-b           0.00    140.00       140.00          140.00     0.00000000
Σ                   0.00  1,373.00                       2010 = 1,373.00  ✓
```

**Cash flow, May–June**
```
Opening cash+bank                          0.00
Financing  owner capital in            30,000.00
Operating  gold purchases              (4,000.00)
Operating  capitalised processing        (100.00)
Operating  gold sales                   5,700.00
Financing  investor profit paid              0.00   (G1: not yet bridged)
Transfers between company accounts    [memo 0.00]
Closing cash+bank                      31,600.00   ✓ opening + movements = closing
```

---

## 5. Permissions and audit visibility

New tokens, following `AccountingPermission` (route names, deny-by-default):

| Token | Grants |
|---|---|
| `admin.accounting.report.view` | (exists) trial balance, P&L, balance sheet, cash flow, gold trading, period close |
| `admin.accounting.report.investor` | Investor Liability Report — separate, because it exposes per-investor positions |
| `admin.accounting.report.export` | PDF / CSV of any report the actor may view |
| `admin.accounting.dashboard.view` | dashboard |
| `admin.accounting.control.view` | reconciliation / control reports |

Every report render is auditable: actor, report, scope, as-at, rendered-at,
and the control outcomes (balanced / unbalanced, differences) written to an
append-only `accounting_report_views` table — small, indexed by actor and
report. Exports additionally record the SHA-256 of the file produced, the same
discipline as investor documents. **No report writes anything else.**

---

## 6. Routes, pages, filters, exports

Under the existing `Route::prefix('admin')->name('admin.')` group, a
`ReportController` group `accounting/reports`, names `admin.accounting.report.*`:

| Route name | Page |
|---|---|
| `trial-balance` | §3.1 |
| `profit-loss` | §3.2 |
| `balance-sheet` | §3.3 |
| `cash-flow` | §3.4 |
| `gold-trading` (+ `/{lot}`) | §3.5 |
| `investor-liability` (+ `/{user}`) | §3.6 |
| `period-close/{period}` | §3.7 |
| `dashboard` | §3.8 |
| `controls` | §3.9 |
| `…/export?format=pdf\|csv` | any of the above |

Filters: `period` **or** `from`/`to`; `as_at`; `lot`; `user`; `account_type`;
`show_zero`. Defaults: the current open period; balance sheet as-at today.

Console equivalents for every report (`report:trial-balance`, `report:pl`,
`report:balance-sheet`, `report:cash-flow`, `report:gold`, `report:investors`,
`report:period`, `report:controls`), because every phase so far has been
verified from the console first.

Exports: PDF via the existing dompdf path (`Pdf::loadHTML()->setPaper('a4')`),
core fonts only (Helvetica / Courier — the Phase 2 lesson: no embedded fonts,
no em-dashes), stored on the private disk when the export is a period-close
pack, streamed otherwise; CSV with exact 8dp values. Every export carries the
scope, the as-at time, the interim/final label and the control outcomes in its
header, so a printed page cannot be mistaken for a final figure when it was
interim.

Theme: the vendor's admin classes (`custom-card`, `custom-table`,
`dashbord-item`) — the Phase 2 lesson, again.

---

## 7. Historical attribution and 1090 — explicit treatment

- `BOR-2026-09-08` and `BOR-2026-10-02` appear on the Gold Trading report and
  the Investor Liability report in a section headed **HISTORICAL ATTRIBUTION —
  NOT POSTED TO COMPANY GL**, with their `gold_lot_results` and
  `gold_profit_distributions` figures, and with no GL column, no revenue, no
  COGS, no realized result. They contribute **0.00** to every accounting total
  and are excluded from every P&L, balance sheet, cash flow and control.
- Their prior payouts (943.31, 861.06632) are visible on the investor ledger as
  `profit_credited` events and therefore in Bhavin's ledger position — that is
  the investor-side fact and stays. On the company side the corresponding 2010
  credit **does not exist**, which is exactly the D2/D3 gap: the Investor
  Liability control *"Σ ledger profit = 2010"* will read **1,804.37632 vs
  0.00** on the real September data. **The report must show that difference,
  captioned "historical attribution not yet backfilled (3H); D2/D3 open"**, and
  must not be tuned to pass. 3G's self-test asserts the control reports the
  difference rather than hiding it.
- 1090 is a permanent line on the balance sheet, dashboard and controls until
  D2/D3 is resolved, whatever its balance.

---

## 8. What 3G must not do

- Post any journal, ledger entry, or wallet change. Reports are read-only; the
  only write is the audit row.
- Read `user_wallets` for any total.
- Read `investment_plans`, `investment_profit_logs`, `profit_percentage`.
- Compute an FX figure. 4100/6900 are shown as posted, which today is 0.00.
- Revalue inventory to market.
- Sweep results to 3100, sum 2000 with 2010, net 1090 against anything, or
  present a historical attribution figure as an accounting result.
- Recompute a distributed period's allocation; read the snapshot.
- Show grams, lots or ownership against an investor.

---

## 9. Mandatory self-tests and regression

`report:selftest`, same pattern (own periods, rolled back), at minimum:

1. Trial balance: opening + movements = closing per account; balanced; an
   injected 0.01 raw line reports **unbalanced** with the difference.
2. P&L trading result equals `RealizedTradingResult::forCompany()` exactly.
3. P&L: Dr 7000 posted → trading result unchanged; appropriation section shows it.
4. P&L: appropriation appears in the *declared* period, footnoted in the source period.
5. Balance sheet: equation holds; a forced imbalance is reported red.
6. Balance sheet: 2000 and 2010 rendered as separate lines, never merged.
7. Balance sheet: 1090 line present with the D2/D3 caption even at 0.00.
8. Cash flow: opening + movements = closing.
9. Cash flow: a 1,300.00 transfer changes no class and appears once as a memo.
10. Cash flow: 2000 receipts and 2010 payments classified separately.
11. Gold trading: D4 fixture shows basis 2,100.00 and expenses 0.00; nothing twice.
12. Gold trading: per-lot GL 5000 = `LotCostBasis` COGS (`divergence()` agrees).
13. Gold trading: interim lot carries its `qualification`; final lot shows recorded figures.
14. Investor liability: Σ ledger profit = 2010 after a distribution; wallet Δ = 0.
15. Investor liability: every distribution line ↔ ledger entry, same amount.
16. Investor liability: no grams/lot fields on any investor row (schema assertion).
17. Period close: snapshot = live for a closed period; a raw-line injection after close reports a control exception.
18. Historical: both BOR lots in the attribution section, excluded from totals, and the 2010 control **reports** the 1,804.37632 difference on real data with the D2/D3 caption.
19. Permissions: a stranger cannot view, an investor-report grant does not grant exports.
20. Audit: a render writes exactly one audit row with actor, scope, controls.
21. Export: PDF under 100 KB with core fonts; header carries scope and interim/final; SHA-256 recorded.
22. Empty ledger (real September): every report renders, says the ledger is empty, no division by zero, no false zero-result "final".
23. Legacy: a 99% `investment_plans` row inserted at runtime changes no report figure (static + runtime, hardened).
24. Read-only: journal, ledger entry, wallet, transaction counts identical before and after rendering every report.
25. Trial balance regression, and the complete chain: Phase 1, 2, 3A, 3B, 3C/D4, 3D, 3E, 3F close, 3F allocation, 3F distribution.

Staging verification as always: run on real MySQL, everything rolled back,
September open, no distribution, Bhavin unchanged, 1090 = 0, no real journal.

---

## 10. Gaps this specification surfaces (not decided here)

**G1 — investor withdrawals have no GL posting.** The vendor's money-out flow
writes `transactions` and the investor ledger records `withdrawal_requested` /
`withdrawal_paid`, but nothing posts `Dr 2010 (or 2000) / Cr bank` and 2200
Withdrawals Payable has never been posted to. Until a bridge exists, the
company's 2010 will not fall when profit is actually paid out. 3G reports the
gap; closing it is a separate phase (a "3F-W" or part of 3H) and needs a
decision on whether a withdrawal is paid from profit first, capital first, or
as the investor chose.

**G2 — investor capital has no GL entry today.** The fixture funds from 3000
because no real investor capital has ever been posted to 2000 (D2/D3). On real
data, *"Σ ledger available + committed = 2000"* will read 2,000.00 vs 0.00.
Same treatment as §7: shown, captioned, not hidden.

**G3 — no retained-earnings sweep at close.** The balance sheet carries a
computed current-result line. Whether close should post `Dr/Cr 3100` is a
period-close policy question.

**G4 — no market valuation of inventory.** Reported at cost only, by design.

---

## 11. Questions for review before implementation

1. Is the June-dated appropriation on the June P&L (with a May footnote) the
   presentation you want, or should the P&L offer a *"by source period"* view
   as well?
2. Should the Investor Liability Report be visible to the investor themselves
   (their row only) in Phase 2's statement area, or admin-only for now?
3. G1 and G2 are shown as unreconciled differences on real data. Do you want
   them captioned as above, or suppressed behind a *"pre-backfill"* flag until
   3H? (Recommendation: captioned. Hidden differences are how books drift.)
4. Period-close pack: one PDF bundling §3.1–3.4, 3.7 and 3.9 for a closed
   period, stored privately with a hash — wanted in 3G, or later?

**End of specification. Implemented under commit series ending with the 3G delivery; see `README.md` "3G: reporting" for the code map.**
