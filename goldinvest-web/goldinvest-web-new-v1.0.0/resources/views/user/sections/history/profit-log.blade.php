@extends('user.layouts.master')
@section('content')

<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Dashboard
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
        <div class="dashboard-area mt-10">
            <div class="dashboard-header-wrapper">
                <h3 class="title">{{ @$page_title }}</h3>
            </div>
            <div class="table-area">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>{{ __("Plan") }}</th>
                                <th>{{ __("Duration") }}</th>
                                <th>{{ __("Investment") }}</th>
                                <th>{{ __("Profit") }}</th>
                                <th>{{ __("Date") }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($profits as  $item)
                                <tr>
                                    <td>{{ $item->invest->investPlan->data->language->$lang->name }}</td>
                                    <td>{{ $item->invest->investPlan->plan_duration }} @if($item->invest->investPlan->plan_duration > 1) {{ __("Days") }} @else {{ __("Day") }} @endif</td>
                                    <td>{{ get_amount($item->invest->invest_amount) }} {{ $default_currency->symbol }}</td>
                                    <td>{{ get_amount($item->profit_amount) }} {{ $default_currency->symbol }}</td>
                                    <td>{{ $item->created_at }}</td>
                                </tr>
                            @empty
                                @include('user.components.alerts.empty',['colspan' => 5])
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <nav>
                    <ul class="pagination justify-content-end">
                        {{ get_paginate($profits) }}
                    </ul>
                </nav>
            </div>
        </div>


<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Dashboard
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

@endsection
