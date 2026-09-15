@extends('admin.layouts.master')

@push('css')
@endpush

@section('page-title')
    @include('admin.components.page-title', ['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('admin.dashboard'),
            ],
        ],
        'active' => __('User Care'),
    ])
@endsection

@section('content')
    <div class="table-area">
        <div class="table-wrapper">
            <div class="table-header">
                <h5 class="title">{{ $page_title }}</h5>
            </div>
            <div class="table-responsive">
                <div class="table-area">
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>{{ __("Username") }}</th>
                                        <th>{{ __("Email") }}</th>
                                        <th>{{ __("Joined Date") }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($refer_users ?? [] as $key => $item)
                                        <tr>
                                            <td>
                                                <ul class="user-list">
                                                    <li><img src="{{ $item->user->userImage ?? ''}}" alt="user"></li>
                                                </ul>
                                            </td>
                                            <td>{{ $item->user->username ?? 'N/A' }}</td>
                                            <td>{{ $item->user->email ?? 'N/A' }}</td>
                                            <td>{{ $item->created_at ? $item->created_at->format("Y-m-d H:i") : 'N/A' }}</td>
                                            <td>
                                                <a href="{{ setRoute('admin.users.details',$item->user->username) }}" class="btn btn--base"><i class="las la-info-circle"></i></a>
                                            </td>
                                        </tr>
                                    @empty
                                        @include('admin.components.alerts.empty',['colspan' => 7])
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{ get_paginate($refer_users) }}
    </div>
@endsection

@push('script')

@endpush
