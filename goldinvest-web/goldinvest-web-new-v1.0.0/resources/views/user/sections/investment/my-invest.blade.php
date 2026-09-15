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
                                <th>{{ __("Plan") }}</th>
                                <th>{{ __("Duration") }}</th>
                                <th>{{ __("Invest Amount") }}</th>
                                <th>{{ __("Profit (Percent)") }}</th>
                                <th>{{ __("Profit (Fixed)") }}</th>
                                <th>{{ __("Current Balance") }}</th>
                                <th>{{ __("Profit Return Type") }}</th>
                                <th>{{ __("Status") }}</th>
                                <th>{{ __("Purchase At") }}</th>
                                <th>{{ __("Expire At") }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invests as  $item)
                                <tr>
                                    <td>{{ @$item->investPlan->data->language->$lang->name }}</td>
                                    <td>{{ @$item->investPlan->plan_duration}} @if($item->plan_duration > 1) {{ __("Days") }} @else {{ __("Day") }} @endif</td>
                                    <td>{{ get_amount($item->invest_amount) }} {{ $default_currency->symbol }}</td>
                                    <td>{{ get_amount($item->investPlan->profit_percentage) }}</td>
                                    <td>{{ get_amount($item->investPlan->profit) }} {{ $default_currency->symbol }}</td>
                                    @foreach ($item->user->wallets as $wallet)
                                       <td>{{ $wallet->balance }} {{ $default_currency->symbol }}</td>
                                    @endforeach
                                    <td>{{ @$item->investPlan->profit_return_type}}</td>
                                    <td>@if ($item->status == 1)<span class="badge badge--success">{{ __("Completed") }} @elseif ($item->status == 2)<span class="badge badge--warning"> {{ __("Running") }} @else <span class="badge badge--danger"> {{ __("Cancel") }} @endif</span></td>
                                    <td>{{ @$item->created_at }}</td>
                                    <td>{{ @$item->exp_at }}</td>
                                </tr>
                            @empty
                                @include('user.components.alerts.empty',['colspan' => 10])
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <nav>
                    <ul class="pagination justify-content-end">
                        {{ get_paginate($invests) }}
                    </ul>
                </nav>
            </div>
        </div>


<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Dashboard
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

@endsection
