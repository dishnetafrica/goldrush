@php use App\Investor\Support\Money; $g = $data; @endphp
@include('admin.sections.accounting-reports._partials.banner', ['data' => $g + ['scope' => $g['scope'] + ['label' => ($g['scope']['lot'] ?? null) ? 'deal ' . $g['scope']['lot'] : 'all deals', 'status' => 'per deal; final only where the result is recorded']]])
<table class="rpt">
    <thead><tr><th>Deal</th><th class="n">Refined g</th><th class="n">Sold g</th><th class="n">Left g</th><th class="n">Purchase</th><th class="n">Capitalised</th><th class="n">Basis</th>
        <th class="n">COGS</th><th class="n">Inventory</th><th class="n">GL inv.</th><th class="n">Revenue</th><th class="n">Expenses</th><th class="n">Result</th><th>Stage</th><th class="n">Recorded</th><th>Terms</th></tr></thead>
    <tbody>
    @forelse ($g['lots'] as $l)
        <tr><td>{{ $l['lot_code'] }}<br><small>{{ $l['purchase_date'] }}</small></td>
            <td class="n">{{ number_format($l['physical']['refined_grams'], 4) }}</td><td class="n">{{ number_format($l['physical']['sold_grams'], 4) }}</td><td class="n">{{ number_format($l['physical']['remaining_grams'], 4) }}</td>
            <td class="n">{{ Money::format($l['cost']['purchase_usd']) }}</td><td class="n">{{ Money::format($l['cost']['capitalised_usd']) }}</td><td class="n">{{ Money::format($l['cost']['basis_usd']) }}</td>
            <td class="n">{{ Money::format($l['cost']['cogs_usd']) }}</td><td class="n">{{ Money::format($l['cost']['inventory_usd']) }}</td><td class="n">{{ Money::format($l['cost']['gl_inventory_usd']) }}</td>
            <td class="n">{{ Money::format($l['result']['revenue_usd']) }}</td><td class="n">{{ Money::format($l['result']['expenses_usd']) }}</td><td class="n">{{ Money::format($l['result']['net_usd']) }}</td>
            <td>{{ $l['result']['stage'] }}@if ($l['result']['qualification'])<br><small>{{ $l['result']['qualification'] }}</small>@endif</td>
            <td class="n">{{ $l['recorded'] ? Money::format($l['recorded']['net_profit_usd']) : '-' }}@if ($l['recorded'])<br><small>{{ $l['recorded']['result_reference'] }} in {{ $l['recorded']['period'] }}</small>@endif</td>
            <td><small>{{ $l['terms']['investor_share_pct'] !== null ? number_format($l['terms']['investor_share_pct'], 2) . '% investors' : 'no share recorded' }}, {{ $l['terms']['expense_policy'] }}, capital {{ Money::format($l['terms']['capital_usd']) }}</small></td></tr>
    @empty
        <tr><td colspan="16">No deal has been posted to the ledger.</td></tr>
    @endforelse
    <tr class="total"><td colspan="3">Totals (current deals only)</td><td class="n">{{ number_format($g['totals']['remaining_grams'], 4) }}</td><td colspan="4"></td>
        <td class="n">{{ Money::format($g['totals']['inventory_usd']) }}</td><td colspan="3"></td><td class="n">{{ Money::format($g['totals']['realized_usd']) }}</td><td colspan="3"></td></tr>
    </tbody>
</table>
<p class="rpt-note">Capitalised processing appears once, inside the cost basis; the expenses column is 6000-6899 only. Inventory is at cost; no market valuation exists (G4). Gold is the company's inventory; no investor owns a lot.</p>
@if (! empty($g['historical']))
<div class="rpt-hist">
    <h4>{{ $g['historical_label'] }}</h4>
    <table class="rpt">
        <thead><tr><th>Deal</th><th>Closed</th><th class="n">Attributed result</th><th class="n">To investors</th><th class="n">To company</th><th>GL</th></tr></thead>
        <tbody>
        @foreach ($g['historical'] as $h)
            <tr><td>{{ $h['lot_code'] }}</td><td>{{ $h['recorded']['closed_at'] }}</td><td class="n">{{ Money::format($h['recorded']['net_profit_usd']) }}</td>
                <td class="n">{{ Money::format($h['recorded']['investor_profit_usd']) }}</td><td class="n">{{ Money::format($h['recorded']['company_profit_usd']) }}</td><td>none</td></tr>
        @endforeach
        </tbody>
    </table>
    <small>{{ $g['historical'][0]['note'] }}</small>
</div>
@endif
@include('admin.sections.accounting-reports._partials.controls', ['controls' => $g['controls']])
