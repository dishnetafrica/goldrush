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
        'active' => __('Order Logs'),
    ])
@endsection

@section('content')
    <div class="table-area">
        <div class="table-wrapper">
            <div class="table-header">
                <h5 class="title">{{ $page_title }}</h5>
            </div>
            <div class="table-responsive">
                <table class="custom-table order-search-table">
                    <thead>
                        <tr>
                            <th>{{ __("Item") }}</th>
                            <th>{{ __("Quantity") }}</th>
                            <th>{{ __("Total Amount") }}</th>
                            <th>{{ __("Payment Type") }}</th>
                            <th>{{ __("Order Status") }}</th>
                            <th>{{ __("Date") }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders ?? []  as $key => $item)
                            <tr data-item="{{ json_encode($item->only(['id','order_status'])) }}">
                                <td>{{ $item->gold->title->language->$lang->title ?? $item->gold->title->language->$default->title ?? ''}}</td>
                                <td>{{ $item->quantity ?? '' }}</td>
                                <td>{{ get_amount($item->total_amount) }} {{ $default_currency->code ?? '' }}</td>
                                <td>
                                    @if($item->payment_type == global_const()::PAYMENT_TYPE_USER_WALLET) {{ __("User Wallet") }}
                                    @else  {{ __("Cash on Delivery") }}
                                    @endif
                                </td>
                                <td>
                                        @if($item->order_status == 1) {{ __("Accepted") }}
                                        @elseif($item->order_status == 2) {{ __("Ongoing") }}
                                        @elseif($item->order_status == 3) {{ __("Delivered") }}
                                        @elseif($item->order_status == 4) {{ __("Cancelled") }}
                                        @else {{ __("N/A") }}
                                        @endif
                                     <br>
                                    @include('admin.components.link.custom',[
                                        'href'          => "javascript:void(0)",
                                        'class'         => "btn btn--base status-button modal-btn",
                                        'text'          => __("Update Status"),
                                        'permission'    => "admin.order.status",
                                    ])
                                </td>
                                <td>{{ $item->created_at->format('d-m-y h:i:s A') }}</td>
                                <td>
                                    @include('admin.components.link.custom',[
                                        'href'          => setRoute('admin.order.details', $item->id),
                                        'class'         => "btn btn--base modal-btn",
                                        'icon'          => "las la-expand",
                                        'permission'    => "admin.order.details",
                                    ])
                                </td>
                            </tr>
                        @empty
                            @include('admin.components.alerts.empty',['colspan' => 7])
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ get_paginate($orders) }}
        </div>
    </div>
  {{-- State Change Modal --}}
  @include('admin.components.modals.order-status')
@endsection


