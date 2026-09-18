@php $final = str_starts_with($data['scope']['status'] ?? '', 'FINAL'); @endphp
<div class="rpt-banner {{ $final ? 'final' : 'interim' }}">
    <strong>{{ $final ? 'FINAL' : 'INTERIM' }}</strong> - {{ $data['scope']['status'] ?? '' }}<br>
    Scope: {{ $data['scope']['label'] ?? ($data['as_at'] ?? '') }}
    @if (! empty($data['empty']))
        <br><strong>The ledger holds no journals in this scope.</strong> Every figure is zero because nothing has been posted, not because nothing happened.
    @endif
</div>
