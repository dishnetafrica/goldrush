@php use App\Investor\Support\Money; $il = $data; @endphp
@include('admin.sections.accounting-reports._partials.banner', ['data' => $il])
<table class="rpt">
    <thead><tr><th>Step</th><th>Source</th><th class="n">Ledger total</th><th class="n">GL</th></tr></thead>
    <tbody>
    <tr><td>1. Capital liability</td><td>{{ $il['chain']['capital_liability']['source'] }}</td><td class="n">{{ Money::format($il['chain']['capital_liability']['total_usd']) }}</td><td class="n">{{ Money::format($il['chain']['capital_liability']['gl_usd']) }} (2000)</td></tr>
    <tr><td>2. Trading allocation</td><td>{{ $il['chain']['trading_allocation']['source'] }}</td><td></td><td></td></tr>
    <tr><td>3. Profit payable</td><td>{{ $il['chain']['profit_payable']['source'] }}</td><td class="n">{{ Money::format($il['chain']['profit_payable']['total_usd']) }}</td><td class="n">{{ Money::format($il['chain']['profit_payable']['gl_usd']) }} (2010)</td></tr>
    <tr><td>4. Actual payment</td><td>{{ $il['chain']['actual_payment']['source'] }}</td><td></td><td></td></tr>
    </tbody>
</table>
<table class="rpt">
    <thead><tr><th>Investor</th><th class="n">Opening total</th><th>Movements in scope</th><th class="n">Available</th><th class="n">Profit</th><th class="n">Committed</th><th class="n">Total</th><th class="n">Wallet (control)</th><th class="n">Δ</th></tr></thead>
    <tbody>
    @forelse ($il['rows'] as $r)
        <tr><td>{{ $r['investor']['username'] }}<br><small>{{ $r['investor']['email'] }}</small></td>
            <td class="n">{{ Money::format($r['opening']['total']) }}</td>
            <td>@foreach ($r['movements'] as $m)<small>{{ $m['label'] }} x{{ $m['count'] }}: {{ Money::signed($m['amount_usd']) }}</small><br>@endforeach
                @foreach ($r['distributions'] as $d)<small>{{ $d['distribution'] }} ({{ $d['period'] }}) {{ Money::format($d['amount_usd']) }} -> {{ $d['ledger_reference'] }}</small><br>@endforeach
                @foreach ($r['withdrawals'] as $w)<small>{{ $w['label'] }} {{ Money::format($w['amount_usd']) }} - {{ $w['gl'] }}</small><br>@endforeach</td>
            <td class="n">{{ Money::format($r['closing']['available']) }}</td><td class="n">{{ Money::format($r['closing']['profit']) }}</td>
            <td class="n">{{ Money::format($r['closing']['committed']) }}</td><td class="n"><strong>{{ Money::format($r['closing']['total']) }}</strong></td>
            <td class="n">{{ Money::format($r['wallet']['balance']) }} / {{ Money::format($r['wallet']['profit_balance']) }}</td>
            <td class="n {{ abs($r['wallet']['difference']) > 0.00000001 ? 'ctl-ex' : 'ctl-ok' }}">{{ Money::exact($r['wallet']['difference']) }}</td></tr>
    @empty
        <tr><td colspan="9">No investor ledger exists.</td></tr>
    @endforelse
    <tr class="total"><td colspan="3">Totals</td><td class="n">{{ Money::format($il['totals']['available']) }}</td><td class="n">{{ Money::format($il['totals']['profit']) }}</td>
        <td class="n">{{ Money::format($il['totals']['committed']) }}</td><td class="n">{{ Money::format($il['totals']['available'] + $il['totals']['profit'] + $il['totals']['committed']) }}</td>
        <td class="n">{{ Money::format($il['totals']['wallet_balance']) }} / {{ Money::format($il['totals']['wallet_profit']) }}</td><td></td></tr>
    </tbody>
</table>
<table class="rpt">
    <tbody>
    <tr><td>Investor Profit Payable - GL 2010</td><td class="n">{{ Money::exact($il['gl']['profit_2010_usd']) }}</td></tr>
    <tr><td>Investor Ledger Profit</td><td class="n">{{ Money::exact($il['totals']['profit']) }}</td></tr>
    <tr class="total"><td>{{ abs($il['gl']['profit_2010_usd'] - $il['totals']['profit']) > 0.00000001 ? 'Unreconciled historical difference' : 'Reconciled' }}</td><td class="n">{{ Money::exact($il['gl']['profit_2010_usd'] - $il['totals']['profit']) }}</td></tr>
    <tr><td>Investor ledger capital (available + committed)</td><td class="n">{{ Money::exact($il['totals']['available'] + $il['totals']['committed']) }}</td></tr>
    <tr><td>GL 2000 Investor Capital Payable</td><td class="n">{{ Money::exact($il['gl']['capital_2000_usd']) }}</td></tr>
    <tr class="total"><td>Difference</td><td class="n">{{ Money::exact($il['gl']['capital_2000_usd'] - $il['totals']['available'] - $il['totals']['committed']) }}</td></tr>
    </tbody>
</table>
<p class="rpt-note">The wallet is a control figure only, never a source. Nothing on this report is a gram, a lot or a share of one.</p>
@include('admin.sections.accounting-reports._partials.controls', ['controls' => $il['controls']])
