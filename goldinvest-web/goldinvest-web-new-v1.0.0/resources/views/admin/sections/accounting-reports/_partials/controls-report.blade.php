@php $c = $data; @endphp
@include('admin.sections.accounting-reports._partials.banner', ['data' => $c])
<p class="rpt-note"><strong>{{ count($c['exceptions']) }} control exception(s)</strong>, {{ count($c['pre_backfill']) }} pre-backfill difference(s) (D2/D3 open). A pre-backfill difference is shown, labelled and never suppressed.</p>
@foreach ($c['groups'] as $group => $controls)
    <h4>{{ $group }}</h4>
    @include('admin.sections.accounting-reports._partials.controls', ['controls' => $controls])
@endforeach
