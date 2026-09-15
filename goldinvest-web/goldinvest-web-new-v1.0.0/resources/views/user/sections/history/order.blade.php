@extends('user.layouts.master')
@section('content')

<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Dashboard
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
        <div class="dashboard-area mt-10">
            <div class="dashboard-header-wrapper">
                <h3 class="title">{{ $page_title }}</h3>
            </div>
            <div class="table-area">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>{{ __("Item") }}</th>
                                <th>{{ __("Quantity") }}</th>
                                <th>{{ __("Total Amount") }}</th>
                                <th>{{ __("Payment Type") }}</th>
                                <th>{{ __("Order Status") }}</th>
                                <th>{{ __("Date") }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as  $item)
                                <tr>
                                    <td>{{ @$item->gold->title->language->$lang->title }}</td>
                                    <td>{{ @$item->quantity }}</td>
                                    <td>{{ get_amount(@$item->total_amount) }} {{ $default_currency->code }}</td>
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
                                    </td>
                                    <td>{{ @$item->created_at }}</td>
                                </tr>
                            @empty
                                @include('user.components.alerts.empty',['colspan' => 6])
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <nav>
                    <ul class="pagination justify-content-end">
                        {{ get_paginate($orders) }}
                    </ul>
                </nav>
            </div>
        </div>


<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Dashboard
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

@endsection
