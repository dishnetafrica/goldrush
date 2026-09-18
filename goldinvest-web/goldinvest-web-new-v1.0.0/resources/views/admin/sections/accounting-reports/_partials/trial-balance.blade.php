@php use App\Investor\Support\Money; $tb = $data; @endphp
@include('admin.sections.accounting-reports._partials.banner', ['data' => $tb])
<table class="rpt">
    <thead><tr><th>Code</th><th>Account</th><th>Type</th><th class="n">Opening</th><th class="n">Debits</th><th class="n">Credits</th><th class="n">Closing</th></tr></thead>
    <tbody>
    @forelse ($tb['rows'] as $r)
        <tr><td>{{ $r['code'] }}</td><td>{{ $r['name'] }}</td><td>{{ $r['type'] }}</td>
            <td class="n">{{ Money::format($r['opening']) }}</td><td class="n">{{ Money::format($r['debits']) }}</td>
            <td class="n">{{ Money::format($r['credits']) }}</td><td class="n">{{ Money::format($r['closing']) }}</td></tr>
    @empty
        <tr><td colspan="7">No account carries a balance or a movement in this scope.</td></tr>
    @endforelse
    <tr class="total"><td colspan="3">Totals</td><td class="n">{{ Money::format($tb['totals']['opening_debits']) }} / {{ Money::format($tb['totals']['opening_credits']) }}</td>
        <td class="n">{{ Money::exact($tb['totals']['debits']) }}</td><td class="n">{{ Money::exact($tb['totals']['credits']) }}</td>
        <td class="n">{{ Money::format($tb['totals']['closing_debits']) }} / {{ Money::format($tb['totals']['closing_credits']) }}</td></tr>
    </tbody>
</table>
<p class="rpt-note">Period difference {{ Money::exact($tb['totals']['difference']) }}; closing difference {{ Money::exact($tb['totals']['closing_difference']) }};
    {{ $tb['journals']['total'] }} journal(s), {{ $tb['journals']['reversed'] }} reversed. <strong>{{ $tb['balanced'] ? 'BALANCED' : 'UNBALANCED' }}</strong></p>
@include('admin.sections.accounting-reports._partials.controls', ['controls' => $tb['controls']])
