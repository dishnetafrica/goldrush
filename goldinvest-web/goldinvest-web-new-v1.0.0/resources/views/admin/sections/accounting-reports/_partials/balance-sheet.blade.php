@php use App\Investor\Support\Money; $bs = $data; @endphp
@include('admin.sections.accounting-reports._partials.banner', ['data' => $bs + ['scope' => ($bs['scope'] + ['label' => 'as at ' . $bs['as_at']])]])
<table class="rpt">
    <tbody>
    @foreach (['assets' => 'Assets', 'liabilities' => 'Liabilities', 'equity' => 'Equity'] as $key => $title)
        <tr class="section"><td colspan="3">{{ $title }}</td></tr>
        @foreach ($bs[$key] as $a)
            @if (abs($a['balance']) > 0.00000001 || in_array($a['code'], ['1090', '2000', '2010'], true))
                <tr><td>{{ $a['code'] }}</td>
                    <td>{{ $a['name'] }}@if ($a['code'] === '1090')<br><small class="ctl-pb">{{ $bs['suspense']['caption'] }}</small>@endif</td>
                    <td class="n">{{ Money::format($a['balance']) }}</td></tr>
            @endif
        @endforeach
        @if ($key === 'equity')
            <tr><td></td><td>Current period result<br><small>trading {{ Money::format($bs['result']['trading_usd']) }} + FX {{ Money::format($bs['result']['fx_usd']) }} - appropriation 7000 {{ Money::format($bs['result']['appropriation_usd']) }}. {{ $bs['result']['note'] }}</small></td>
                <td class="n">{{ Money::format($bs['result']['current_usd']) }}</td></tr>
        @endif
    @endforeach
    <tr class="total"><td></td><td>Total assets</td><td class="n">{{ Money::exact($bs['totals']['assets_usd']) }}</td></tr>
    <tr class="total"><td></td><td>Total liabilities + equity + result</td><td class="n">{{ Money::exact($bs['totals']['right_side_usd']) }}</td></tr>
    </tbody>
</table>
<p class="rpt-note">{{ $bs['investor_liability']['note'] }} Capital 2000 {{ Money::format($bs['investor_liability']['capital_2000_usd']) }}; profit 2010 {{ Money::format($bs['investor_liability']['profit_2010_usd']) }}.
    <strong>{{ $bs['balanced'] ? 'A = L + E HOLDS' : 'A = L + E DOES NOT HOLD: ' . Money::exact($bs['totals']['difference_usd']) }}</strong></p>
@include('admin.sections.accounting-reports._partials.controls', ['controls' => $bs['controls']])
