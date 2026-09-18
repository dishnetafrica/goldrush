@extends('admin.layouts.master')
@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        ['name' => __("Dashboard"), 'url' => setRoute("admin.dashboard")],
        ['name' => __("Accounting Reports"), 'url' => setRoute("admin.accounting.report.dashboard")],
    ], 'active' => __($title)])
@endsection
@section('content')
@include('admin.sections.accounting-reports._partials._styles')
<div class="dashboard-area">
    <div class="dashboard-header-wrapper">
        <h4 class="title">{{ __($title) }}</h4>
        <div>
            @foreach (['trial-balance' => 'Trial Balance', 'profit-loss' => 'P&L', 'balance-sheet' => 'Balance Sheet', 'cash-flow' => 'Cash Flow', 'gold-trading' => 'Gold', 'investor-liability' => 'Investors', 'controls' => 'Controls'] as $r => $l)
                <a href="{{ setRoute('admin.accounting.report.' . $r) }}" class="btn btn-sm {{ $partial === $r ? 'btn--base' : 'btn--secondary' }}">{{ __($l) }}</a>
            @endforeach
        </div>
    </div>

    <div class="custom-card mt-10"><div class="card-body">
        <form method="GET" action="{{ $periodCode ? setRoute($routeName, $periodCode) : setRoute($routeName) }}" class="row g-2 align-items-end">
            @if (! $periodCode)
            <div class="col-md-2">
                <label class="form-label">{{ __("Period") }}</label>
                <select name="period" class="form--control">
                    <option value="">{{ __("(date range / to date)") }}</option>
                    @foreach ($periods as $p)
                        <option value="{{ $p->code }}" @selected(($input['period'] ?? '') === $p->code)>{{ $p->code }} - {{ $p->status }}{{ $p->close_reference ? ' ' . $p->close_reference : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label">{{ __("From") }}</label><input type="date" name="from" value="{{ $input['from'] ?? '' }}" class="form--control"></div>
            <div class="col-md-2"><label class="form-label">{{ __("To") }}</label><input type="date" name="to" value="{{ $input['to'] ?? '' }}" class="form--control"></div>
            @endif
            @if ($partial === 'balance-sheet')
                <div class="col-md-2"><label class="form-label">{{ __("As at") }}</label><input type="date" name="as_at" value="{{ $input['as_at'] ?? '' }}" class="form--control"></div>
            @endif
            @if ($partial === 'profit-loss')
                <div class="col-md-2"><label class="form-label">{{ __("Source period") }}</label><input type="text" name="source_period" value="{{ $input['source_period'] ?? '' }}" class="form--control" placeholder="e.g. 2026-09"></div>
            @endif
            @if ($partial === 'gold-trading')
                <div class="col-md-2"><label class="form-label">{{ __("Deal") }}</label><input type="text" name="lot" value="{{ $input['lot'] ?? '' }}" class="form--control" placeholder="lot code"></div>
            @endif
            @if ($partial === 'investor-liability')
                <div class="col-md-2"><label class="form-label">{{ __("Investor id") }}</label><input type="text" name="user" value="{{ $input['user'] ?? '' }}" class="form--control"></div>
            @endif
            @if ($partial === 'trial-balance')
                <div class="col-md-2"><label class="form-label">&nbsp;</label><label><input type="checkbox" name="show_zero" value="1" @checked(! empty($input['show_zero']))> {{ __("Show zero accounts") }}</label></div>
            @endif
            <div class="col-md-2"><button type="submit" class="btn btn--base w-100">{{ __("Run") }}</button></div>
            <div class="col-md-2">
                @if ($canExport)
                    @php $exportQuery = array_filter($input) + ($periodCode ? ['period' => $periodCode] : []); @endphp
                    <a class="btn btn--secondary" href="{{ setRoute('admin.accounting.report.export', array_merge([$partial], $exportQuery, ['format' => 'pdf'])) }}">{{ __("PDF") }}</a>
                    <a class="btn btn--secondary" href="{{ setRoute('admin.accounting.report.export', array_merge([$partial], $exportQuery, ['format' => 'csv'])) }}">{{ __("CSV") }}</a>
                @else
                    <small class="text-muted">{{ __("Export needs the export grant") }}</small>
                @endif
            </div>
        </form>
        <p class="text-muted mt-2 mb-0" style="font-size:12px;">{{ __("Every figure on this page is read from posted journal lines and recorded results. Nothing here posts, recomputes or corrects anything.") }}</p>
    </div></div>

    <div class="custom-card mt-10"><div class="card-body table-responsive">
        @include('admin.sections.accounting-reports._partials.' . ($partial === 'controls' ? 'controls-report' : $partial), ['data' => $data])

        @if ($partial === 'period-close')
            <h4>{{ __("Allocation and Distribution") }}</h4>
            @include('admin.sections.accounting-reports._partials.distribution', ['data' => $data])
            <h4>{{ __("Close pack") }}</h4>
            @foreach ($packs as $pk)
                <p>{{ $pk->reference }} for {{ $pk->close_reference }} - {{ number_format($pk->file_bytes / 1024, 1) }} KB, sha256 <code>{{ $pk->file_hash }}</code>, generated {{ $pk->generated_at->format('d M Y H:i') }} by {{ $pk->generated_by }}
                    <a class="btn btn-sm btn--base" href="{{ setRoute('admin.accounting.report.pack.download', $pk->id) }}">{{ __("Download") }}</a></p>
            @endforeach
            @if ($data['period']['is_closed'] && $canExport)
                <form method="POST" action="{{ setRoute('admin.accounting.report.pack.store', $data['period']['code']) }}">@csrf
                    <button class="btn btn--base" type="submit">{{ __("Generate close pack (once per close; immutable, hashed)") }}</button></form>
            @elseif (! $data['period']['is_closed'])
                <p class="text-muted">{{ __("A close pack is generated once the period is closed.") }}</p>
            @else
                <p class="text-muted">{{ __("Generating the close pack needs the export grant.") }}</p>
            @endif
        @endif
    </div></div>
</div>
@endsection
