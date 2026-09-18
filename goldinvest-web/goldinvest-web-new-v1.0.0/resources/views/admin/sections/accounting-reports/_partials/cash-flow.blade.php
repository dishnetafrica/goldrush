@php use App\Investor\Support\Money; $cf = $data; @endphp
@include('admin.sections.accounting-reports._partials.banner', ['data' => $cf])
<table class="rpt">
    <tbody>
    <tr class="total"><td colspan="2">Opening cash and bank</td><td class="n">{{ Money::format($cf['opening_usd']) }}</td></tr>
    @foreach (['operating' => 'Operating', 'financing' => 'Financing', 'suspense' => 'Suspense (unidentified)', 'other' => 'Other'] as $section => $title)
        @php $rows = array_filter($cf['classes'], fn ($c) => $c['section'] === $section); @endphp
        @if ($section !== 'other' || array_sum(array_column($rows, 'amount')) != 0)
            <tr class="section"><td colspan="3">{{ $title }}</td></tr>
            @foreach ($rows as $c)
                <tr><td></td><td>{{ $c['label'] }}@if (! empty($c['caption']))<br><small>{{ $c['caption'] }}</small>@endif</td><td class="n">{{ Money::format($c['amount']) }}</td></tr>
            @endforeach
            <tr class="total"><td></td><td>Net {{ strtolower($title) }}</td><td class="n">{{ Money::format($cf['sections'][$section]) }}</td></tr>
        @endif
    @endforeach
    <tr class="total"><td colspan="2">Net movement</td><td class="n">{{ Money::format($cf['net_movement_usd']) }}</td></tr>
    <tr><td colspan="2"><em>Memo: transfers between company accounts - excluded from every class, shown once</em>
        @foreach ($cf['transfers'] as $t)<br><small>{{ $t['date'] }} {{ $t['journal'] }} {{ $t['memo'] }}</small>@endforeach</td>
        <td class="n"><em>{{ Money::format($cf['transfers_total_usd']) }}</em></td></tr>
    <tr class="total"><td colspan="2">Closing cash and bank</td><td class="n">{{ Money::format($cf['closing_usd']) }}</td></tr>
    </tbody>
</table>
@include('admin.sections.accounting-reports._partials.controls', ['controls' => $cf['controls']])
