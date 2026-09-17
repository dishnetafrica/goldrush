@php
    use App\Investor\Support\Money;
    $c = $statement['currency'];
@endphp
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Investor Account Statement</title>
@include('investor.documents._styles')
</head>
<body>

@include('investor.documents._header', ['title' => 'Investor Account Statement'])

@if ($interim)
    <div class="interim">
        <strong>INTERIM STATEMENT.</strong> This period includes today, so the figures may still change.
        A final statement can be issued once the period has closed.
    </div>
@endif

<table>
    <tr>
        <td style="width:50%; vertical-align:top;">
            <table class="kv">
                <tr><td class="k">Investor</td><td><strong>{{ $statement['investor']['name'] }}</strong></td></tr>
                <tr><td class="k">Account number</td><td>{{ $statement['investor']['account'] }}</td></tr>
                <tr><td class="k">Email</td><td>{{ $statement['investor']['email'] }}</td></tr>
            </table>
        </td>
        <td style="width:50%; vertical-align:top;">
            <table class="kv">
                <tr><td class="k">Statement period</td><td>{{ $statement['period']['label'] }}</td></tr>
                <tr><td class="k">Currency</td><td>{{ $c }}</td></tr>
                <tr><td class="k">Movements</td><td>{{ count($statement['movements']) }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<h2>Your position</h2>
<table class="buckets">
    <tr>
        <td style="width:33.3%"><div class="label">Available Balance</div>
            <div class="value">{{ Money::format($statement['closing']['available']) }}</div></td>
        <td style="width:33.3%"><div class="label">Profit Balance</div>
            <div class="value">{{ Money::format($statement['closing']['profit']) }}</div></td>
        <td style="width:33.4%"><div class="label">Capital in Active Deals</div>
            <div class="value">{{ Money::format($statement['closing']['committed']) }}</div></td>
    </tr>
    <tr class="total-row">
        <td colspan="2"><div class="label">Total Investor Position</div>
            <div class="value">{{ Money::format($statement['closing']['total']) }} {{ $c }}</div></td>
        <td><div class="label">Available to withdraw</div>
            <div class="value">{{ Money::format($statement['closing']['available'] + $statement['closing']['profit']) }}</div></td>
    </tr>
</table>

<h2>Account activity</h2>
<table class="grid">
    <thead>
    <tr>
        <th style="width:11%">Date</th>
        <th style="width:27%">Description</th>
        <th style="width:14%">Reference</th>
        <th class="num" style="width:10%">Money In</th>
        <th class="num" style="width:10%">Money Out</th>
        <th class="num" style="width:9%">Available</th>
        <th class="num" style="width:9%">Profit</th>
        <th class="num" style="width:10%">Committed</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td colspan="3"><strong>Opening position</strong></td>
        <td class="num"></td><td class="num"></td>
        <td class="num">{{ Money::format($statement['opening']['available']) }}</td>
        <td class="num">{{ Money::format($statement['opening']['profit']) }}</td>
        <td class="num">{{ Money::format($statement['opening']['committed']) }}</td>
    </tr>
    @foreach ($statement['movements'] as $m)
        <tr class="{{ $m['internal'] ? 'internal' : '' }}">
            <td>{{ $m['date']->format('d M Y') }}</td>
            <td>{{ $m['description'] }}
                @if ($m['internal'])<br><span class="muted">Internal transfer between your balances</span>@endif
            </td>
            <td>{{ $m['reference'] }}</td>
            <td class="num">{{ $m['money_in'] !== null ? Money::format($m['money_in']) : '' }}</td>
            <td class="num">{{ $m['money_out'] !== null ? Money::format($m['money_out']) : '' }}</td>
            <td class="num">{{ Money::format($m['balance_available']) }}</td>
            <td class="num">{{ Money::format($m['balance_profit']) }}</td>
            <td class="num">{{ Money::format($m['balance_committed']) }}</td>
        </tr>
    @endforeach
    <tr>
        <td colspan="3"><strong>Closing position</strong></td>
        <td class="num"><strong>{{ Money::format($statement['totals']['external_in']) }}</strong></td>
        <td class="num"><strong>{{ Money::format($statement['totals']['external_out']) }}</strong></td>
        <td class="num"><strong>{{ Money::format($statement['closing']['available']) }}</strong></td>
        <td class="num"><strong>{{ Money::format($statement['closing']['profit']) }}</strong></td>
        <td class="num"><strong>{{ Money::format($statement['closing']['committed']) }}</strong></td>
    </tr>
    </tbody>
</table>

<div class="note">
    Rows shown in grey are internal transfers between your own balances - for example capital moving into a
    deal, or being returned from one. They move money between the three balances above but never change your
    Total Investor Position, which is why they appear in neither the Money In nor the Money Out column.
</div>

<h2>Statement reconciliation</h2>
<table class="kv" style="width:60%">
    <tr><td class="k">Opening position</td><td class="num">{{ Money::format($statement['opening']['total']) }}</td></tr>
    <tr><td class="k">Plus money in</td><td class="num">{{ Money::format($statement['totals']['external_in']) }}</td></tr>
    <tr><td class="k">Less money out</td><td class="num">{{ Money::format($statement['totals']['external_out']) }}</td></tr>
    <tr><td class="k"><strong>Closing position</strong></td>
        <td class="num"><strong>{{ Money::format($statement['closing']['total']) }}</strong></td></tr>
    <tr><td class="k">Moved between balances</td><td class="num">{{ Money::format($statement['totals']['internal']) }}</td></tr>
    <tr><td class="k">Effect on total position</td><td class="num">0.00</td></tr>
</table>
<p class="muted" style="margin-top:4px;">
    Available {{ Money::format($statement['closing']['available']) }}
    + Profit {{ Money::format($statement['closing']['profit']) }}
    + Committed {{ Money::format($statement['closing']['committed']) }}
    = {{ Money::format($statement['closing']['total']) }} {{ $c }}.
    This statement was verified against the account ledger before it was issued.
</p>

<div style="page-break-before: always;"></div>
@include('investor.documents._header', ['title' => 'Investor Account Statement'])

<h2>What happened to your money</h2>
@php $n = $statement['narrative']; @endphp
<table class="grid">
    <tbody>
    <tr><td style="width:70%">You deposited</td><td class="num">{{ Money::format($n['deposits']) }}</td></tr>
    <tr><td>Your capital was deployed into trading deals</td><td class="num">{{ Money::format($n['capital_deployed']) }}</td></tr>
    <tr><td>Capital returned to you when those deals closed</td><td class="num">{{ Money::format($n['capital_returned']) }}</td></tr>
    <tr><td>Trading profit allocated to you</td><td class="num">{{ Money::format($n['profit_earned']) }}</td></tr>
    <tr><td>You withdrew</td><td class="num">{{ Money::format($n['withdrawn']) }}</td></tr>
    </tbody>
</table>

<table class="kv" style="margin-top:12px;">
    <tr><td class="k" style="width:30%">Available Balance</td>
        <td>USD held in your account and free to withdraw or commit to a new deal.</td>
        <td class="num" style="width:16%">{{ Money::format($n['available']) }}</td></tr>
    <tr><td class="k">Profit Balance</td>
        <td>Your allocated share of the net result of completed deals.</td>
        <td class="num">{{ Money::format($n['profit_balance']) }}</td></tr>
    <tr><td class="k">Capital in Active Deals</td>
        <td>Your capital currently deployed in a trading deal that has not yet closed. Owed to you, but not
            available to withdraw until the deal completes.</td>
        <td class="num">{{ Money::format($n['committed']) }}</td></tr>
    <tr><td class="k"><strong>Total Investor Position</strong></td>
        <td><strong>The total USD amount the company owes you.</strong></td>
        <td class="num"><strong>{{ Money::format($n['total']) }}</strong></td></tr>
</table>

<div class="note">
    <strong>How your account works.</strong> You deposit USD with the company. The company pools investor
    capital and uses it to buy, process and sell gold as its own trading business. The company records the
    result of each deal and allocates your agreed share of the profit to your account in USD, and returns your
    capital when the deal closes. You may then withdraw that money or commit it to another deal.
    <br><br>
    Your position with the company is a <strong>USD account balance</strong>. You do not own, hold title to, or
    receive any physical gold, and no gold is held in your name. The gold is the property of the company.
</div>

@if (count($statement['deals']))
    <h2>Recorded trading activity</h2>
    <table class="grid">
        <thead>
        <tr>
            <th style="width:15%">Deal</th>
            <th style="width:20%">Name</th>
            <th style="width:11%">Date</th>
            <th class="num" style="width:12%">Capital</th>
            <th class="num" style="width:9%">Gold (g)</th>
            <th class="num" style="width:8%">Share</th>
            <th class="num" style="width:12%">Your profit</th>
            <th style="width:13%">Status</th>
            <th style="width:14%">Expenses</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($statement['deals'] as $d)
            <tr class="{{ $d['ledger_backed'] ? '' : 'internal' }}">
                <td>{{ $d['reference'] }}</td>
                <td>{{ $d['name'] }}</td>
                <td>{{ $d['date'] ? \Illuminate\Support\Carbon::parse($d['date'])->format('d M Y') : '-' }}</td>
                <td class="num">{{ Money::format($d['capital']) }}</td>
                <td class="num">{{ $d['gold_grams'] !== null ? number_format($d['gold_grams'], 2) : '-' }}</td>
                <td class="num">{{ $d['investor_share'] !== null ? number_format($d['investor_share'], 0) . '%' : '-' }}</td>
                <td class="num">{{ $d['investor_profit'] !== null ? Money::format($d['investor_profit']) : '-' }}</td>
                <td>{{ $d['status'] }}</td>
                <td>{{ $d['expense_status'] ?? '-' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @if (collect($statement['deals'])->contains(fn ($d) => ! $d['ledger_backed']))
        <p class="muted" style="margin-top:5px;">
            Rows in grey are historical deal attribution records. They predate the account ledger and were
            recorded before capital commitments moved through your balances, so they do not appear as movements
            in the activity above. They are shown for completeness of your trading history.
        </p>
    @endif
    @if (collect($statement['deals'])->contains(fn ($d) => ! $d['expenses_finalised']))
        <div class="note">
            <strong>Expense capture.</strong> Expenses are recorded as they are incurred. The results above
            reflect expenses recorded to date and may change when additional deal expenses are recorded.
            Deals marked as having expenses pending are not final.
        </div>
    @endif
    <p class="muted">
        Gold quantities describe what the company bought and sold in each deal. They are shown so you can see
        how the trading result arose. They do not represent gold held for you or on your behalf.
    </p>
@endif

<div class="foot">
    {{ $branding['name'] }} - Investor Account Statement {{ $number }} ·
    {{ $statement['investor']['account'] }} · issued {{ $statement['generated_at']->format('d M Y H:i') }} ·
    figures in {{ $c }}, shown to 2 decimal places and calculated on exact stored values ·
    page <span class="pagenum"></span>
</div>

</body></html>
