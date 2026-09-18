@extends('admin.layouts.master')
@php use App\Investor\Support\Money; @endphp
@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        ['name' => __("Dashboard"), 'url' => setRoute("admin.dashboard")],
    ], 'active' => __("Investor Ledgers")])
@endsection
@section('content')
<div class="dashboard-area">
    <div class="dashboard-header-wrapper">
        <h4 class="title">{{ __("Investor Ledgers") }}</h4>
        <form method="GET" class="d-flex" style="gap:8px;">
            <input type="text" name="search" value="{{ $search }}" class="form--control"
                   placeholder="{{ __('Search username or email') }}">
            <button type="submit" class="btn btn--base">{{ __("Search") }}</button>
        </form>
    </div>
    <div class="table-responsive mt-20">
        <table class="custom-table">
            <thead>
            <tr>
                <th>{{ __("Investor") }}</th>
                <th class="text-end">{{ __("Available") }}</th>
                <th class="text-end">{{ __("Profit") }}</th>
                <th class="text-end">{{ __("Committed") }}</th>
                <th class="text-end">{{ __("Total position") }}</th>
                <th>{{ __("Reconciliation") }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                @php $s = $status[$user->id]; @endphp
                <tr>
                    <td>{{ $user->username }}<br><small class="text-muted">{{ $user->email }}</small></td>
                    <td class="text-end">{{ Money::format($s['position']['available']) }}</td>
                    <td class="text-end">{{ Money::format($s['position']['profit']) }}</td>
                    <td class="text-end">{{ Money::format($s['position']['committed']) }}</td>
                    <td class="text-end"><strong>{{ Money::format($s['total']) }}</strong></td>
                    <td>
                        <span class="badge {{ $s['passed'] ? 'badge--success' : 'badge--danger' }}">
                            {{ $s['passed'] ? __('PASS') : __('FAIL') }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ setRoute('admin.investor.ledger.show', $user->id) }}"
                           class="btn btn-sm btn--base">{{ __("Open") }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">{{ __("No investor ledgers yet.") }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ get_paginate($users) }}
</div>
@endsection
