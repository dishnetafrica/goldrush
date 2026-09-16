@extends('user.layouts.master')
@section('content')
<div class="dashboard-area mt-10">
    <div class="dashboard-header-wrapper">
        <h3 class="title">{{ $page_title }}</h3>
    </div>
    <p class="text-muted">{{ __("Statements and receipts issued to your account. Each document is stored exactly as it was issued and cannot be altered afterwards.") }}</p>
    <div class="table-responsive mt-10">
        <table class="table">
            <thead>
            <tr>
                <th>{{ __("Number") }}</th>
                <th>{{ __("Document") }}</th>
                <th>{{ __("Reference") }}</th>
                <th>{{ __("Issued") }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($documents as $document)
                <tr>
                    <td>{{ $document->document_number }}</td>
                    <td>{{ $document->title }}</td>
                    <td>{{ $document->event_reference }}</td>
                    <td>{{ $document->generated_at->format('d M Y, H:i') }}</td>
                    <td class="text-end">
                        <a href="{{ setRoute('user.documents.download', $document->id) }}"
                           class="btn btn-sm btn--base">{{ __("Download") }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center">{{ __("No documents yet.") }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ get_paginate($documents) }}
</div>
@endsection
