@php use App\Investor\Support\Money; $pl = $data; $t = $pl['trading']; @endphp
@include('admin.sections.accounting-reports._partials.banner', ['data' => $pl])
<table class="rpt">
    <tbody>
    <tr class="section"><td colspan="3">Trading result</td></tr>
    <tr><td>4000</td><td>Gold sales revenue</td><td class="n">{{ Money::format($t['revenue_usd']) }}</td></tr>
    <tr><td>5000</td><td>Cost of gold sold</td><td class="n">({{ Money::format($t['cogs_usd']) }})</td></tr>
    <tr class="total"><td></td><td>Gross trading profit</td><td class="n">{{ Money::format($t['gross_usd']) }}</td></tr>
    @foreach ($t['expenses'] as $e)
        <tr><td>{{ $e['code'] }}</td><td>{{ $e['name'] }}</td><td class="n">({{ Money::format($e['amount_usd']) }})</td></tr>
    @endforeach
    <tr class="total"><td></td><td>REALIZED TRADING RESULT<br><small>4000 - 5000 - (6000-6899); deals {{ Money::format($t['deal_result_usd']) }}, overheads belonging to no deal {{ Money::format($t['overheads_usd']) }}</small></td>
        <td class="n">{{ Money::format($t['realized_result_usd']) }}</td></tr>
    @if (abs($pl['fx']['net_usd']) > 0.00000001)
        <tr><td>4100/6900</td><td>FX gain / loss (as posted; never computed)</td><td class="n">{{ Money::format($pl['fx']['net_usd']) }}</td></tr>
    @endif
    <tr class="section"><td colspan="3">Appropriation (declared in this scope{{ $pl['appropriation']['source_period_filter'] ? '; source period ' . $pl['appropriation']['source_period_filter'] : '' }})</td></tr>
    <tr><td>7000</td><td>Investor profit share</td><td class="n">({{ Money::format($pl['appropriation']['amount_usd']) }})</td></tr>
    @foreach ($pl['appropriation']['distributions'] as $d)
        <tr><td></td><td>&nbsp;&nbsp;{{ $d['caption'] }} @if ($d['status'] === 'reversed')<strong>(REVERSED)</strong>@endif - dated {{ $d['journal_date'] }} in {{ $d['declared_in'] }}; close {{ $d['close_reference'] }}</td>
            <td class="n">{{ Money::format($d['net_usd']) }}</td></tr>
    @endforeach
    <tr class="total"><td></td><td>RESULT AFTER APPROPRIATION</td><td class="n">{{ Money::format($pl['result_after_appropriation_usd']) }}</td></tr>
    </tbody>
</table>
@foreach ($pl['appropriation']['appropriated_elsewhere'] as $d)
    <p class="rpt-note">Note: this period's result was appropriated in <strong>{{ $d['declared_in'] }}</strong> under {{ $d['reference'] }}
        ({{ Money::format($d['pool_usd']) }}, {{ $d['status'] }}, journal {{ $d['journal'] }} dated {{ $d['journal_date'] }}). The trading result above is unchanged by it.</p>
@endforeach
@if (! empty($t['interim_lots']))
    <p class="rpt-note">Interim deals in this scope: {{ implode(', ', $t['interim_lots']) }}.</p>
@endif
@if (! empty($t['deals']))
<h4>By deal</h4>
<table class="rpt">
    <thead><tr><th>Deal</th><th class="n">Revenue</th><th class="n">COGS</th><th class="n">Expenses</th><th class="n">Result</th><th>Stage</th></tr></thead>
    <tbody>
    @foreach ($t['deals'] as $l)
        <tr><td>{{ $l['lot_code'] }}</td><td class="n">{{ Money::format($l['revenue_usd']) }}</td><td class="n">{{ Money::format($l['cost_of_goods_sold_usd']) }}</td>
            <td class="n">{{ Money::format($l['ordinary_expenses_usd']) }}</td><td class="n">{{ Money::format($l['net_realized_usd']) }}</td>
            <td>{{ $l['stage'] }}@if ($l['qualification'])<br><small>{{ $l['qualification'] }}</small>@endif</td></tr>
    @endforeach
    </tbody>
</table>
@endif
@include('admin.sections.accounting-reports._partials.controls', ['controls' => $pl['controls']])
