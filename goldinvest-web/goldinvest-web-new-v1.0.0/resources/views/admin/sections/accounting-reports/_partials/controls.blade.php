@php use App\Investor\Support\Money; use App\Accounting\Reports\Control; @endphp
@if (! empty($controls))
<table class="rpt">
    <thead><tr><th>Control</th><th class="n">Left</th><th class="n">Right</th><th class="n">Difference</th><th>Status</th></tr></thead>
    <tbody>
    @foreach ($controls as $c)
        <tr>
            <td>{{ $c['name'] }}@if ($c['right_label'] === '')<br><small>{{ $c['left_label'] }}</small>@endif</td>
            <td class="n">{{ $c['right_label'] === '' ? '' : Money::exact($c['left']) }}</td>
            <td class="n">{{ $c['right_label'] === '' ? '' : Money::exact($c['right']) }}</td>
            <td class="n">{{ $c['right_label'] === '' ? '' : Money::exact($c['difference']) }}</td>
            <td class="{{ $c['status'] === Control::RECONCILED ? 'ctl-ok' : ($c['status'] === Control::PRE_BACKFILL ? 'ctl-pb' : 'ctl-ex') }}">
                {{ $c['status'] }}@if ($c['caption'])<br><small style="font-weight:normal;color:#333;">{{ $c['caption'] }}</small>@endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@endif
