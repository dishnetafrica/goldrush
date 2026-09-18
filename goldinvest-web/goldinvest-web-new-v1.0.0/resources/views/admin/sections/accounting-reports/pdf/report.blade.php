<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{ $title }}</title>
@include('admin.sections.accounting-reports.pdf._styles')
</head>
<body>
<div class="footer">{{ $branding['name'] ?? '' }} - {{ $title }} - {{ $scope->status() }} - generated {{ now()->format('d M Y H:i') }} by {{ $generated_by }}</div>
<h1>{{ $title }}</h1>
<table class="hdr">
    <tr><td class="k">Scope</td><td>{{ $scope->label() }}</td><td class="k">Status</td><td><strong>{{ $scope->isFinal() ? 'FINAL' : 'INTERIM' }}</strong> - {{ $scope->status() }}</td></tr>
    <tr><td class="k">Generated</td><td>{{ now()->format('d M Y H:i:s') }} by {{ $generated_by }}</td><td class="k">Controls</td>
        <td>{{ collect($data['controls'] ?? [])->every(fn ($c) => $c['passed']) ? 'all reconciled' : collect($data['controls'] ?? [])->filter(fn ($c) => ! $c['passed'])->count() . ' not reconciled' }}</td></tr>
</table>
<p class="rpt-note">Every figure is read from posted journal lines and recorded results. This document is a reporting snapshot, not an accounting record.</p>
@include('admin.sections.accounting-reports._partials.' . ($partial === 'controls' ? 'controls-report' : $partial), ['data' => $data])
</body></html>
