@php
    $app_local = get_default_language_code();
@endphp
@extends('admin.layouts.master')

@push('css')
    <style>
        .fileholder {
            min-height: 194px !important;
        }

        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,.fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view{
            height: 150px !important;
        }
    </style>
@endpush

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("admin.dashboard"),
        ]
    ], 'active' => __("Gold Investment Plan")])
@endsection

@section('content')
    <div class="table-area">
        <div class="table-wrapper">
            <div class="table-header">
                <h5 class="title">{{ __("Plans") }}</h5>
                <div class="table-btn-area">
                    @include('admin.components.link.add-default',[
                        'text'          => __("Create Plan"),
                        'href'          => setRoute('admin.investment.plan.create'),
                        'class'         => "modal-btn",
                        'permission'    => "admin.investment.plan.create",
                    ])
                </div>
            </div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>{{ __("Name") }}</th>
                            <th>{{ __("Title") }}</th>
                            <th>{{ __("Duration") }}</th>
                            <th>{{ __("Profit Return Type") }}</th>
                            <th>{{ __("Min Invest") }}</th>
                            <th>{{ __("Min Invest") }}({{ __("Offer") }})</th>
                            <th>{{ __("Max Invest") }}</th>
                            <th>{{ __("Profit") }}({{ __("Fixed") }})</th>
                            <th>{{ __("Profit") }}({{ __("%") }})</th>
                            <th>{{ __("Status") }}</th>
                            <th>{{ __("Created At") }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($plans ?? [] as $item)
                            <tr data-item="{{ json_encode($item->only(['id'])) }}">
                                <td>
                                    <ul class="user-list">
                                        <li><img src="{{ get_image($item->image ?? null,'site-section') }}" alt="image"></li>
                                    </ul>
                                </td>
                                <td>{{ $item->data->language->$app_local->name ?? null }}</td>
                                <td>{{ $item->data->language->$app_local->title ?? null }}</td>
                                <td>{{ $item->plan_duration }} @if($item->plan_duration > 1) {{ __("Days") }} @else {{ __("Day") }} @endif</td>
                                <td>
                                    @if($item->profit_return_type == global_const()::INVEST_PROFIT_DAILY_BASIS ) {{ __("Daily Basis") }}
                                    @else  {{ __("One Time") }}
                                    @endif
                                </td>
                                <td>{{ get_amount($item->minimum_investment)}} {{ $default_currency->code }}</td>
                                <td>{{ get_amount($item->minimum_investment_offer)}} {{ $default_currency->code }}</td>
                                <td>{{ get_amount($item->maximum_investment)}} {{ $default_currency->code }}</td>
                                <td>{{ get_amount($item->profit)}} {{ $default_currency->code }}</td>
                                <td>{{ get_amount($item->profit_percentage)}}%</td>
                                <td>
                                    @include('admin.components.form.switcher',[
                                        'name'          => 'status',
                                        'value'         => $item->status,
                                        'options'       => [__('Active') => 1,__('Deactive') => 0],
                                        'onload'        => true,
                                        'data_target'   => $item->id,
                                        'permission'    => "admin.investment.plan.status.update",
                                    ])
                                </td>
                                <td>{{ $item->created_at->format("d-m-y h:i:s") }}</td>
                                <td>
                                    @include('admin.components.link.edit-default',[
                                        'href'          => setRoute('admin.investment.plan.edit',$item->id),
                                        'class'         => "edit-modal-button",
                                        'permission'    => "admin.investment.plan.edit",
                                    ])
                                    @include('admin.components.link.delete-default',[
                                        'href'          => "javascript:void(0)",
                                        'class'         => "delete-modal-button",
                                        'permission'    => "admin.investment.plan.delete",
                                    ])
                                </td>
                            </tr>
                        @empty
                            @include('admin.components.alerts.empty',['colspan' => 13])
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ get_paginate($plans) }}
    </div>

@endsection

@push('script')
    <script>

        $(document).ready(function(){
            // Switcher
            switcherAjax("{{ setRoute('admin.investment.plan.status.update') }}");
        })

        $(".delete-modal-button").click(function(){
            var oldData = JSON.parse($(this).parents("tr").attr("data-item"));

            var actionRoute =  "{{ setRoute('admin.investment.plan.delete') }}";
            var target      = oldData.id;
            var message     = `{{ __('Are you sure to delete this plan?') }}`;

            openDeleteModal(actionRoute,target,message);
        });
        function openDeleteModal(URL,target,message,actionBtnText = "{{ __('Remove') }}",method = "DELETE"){
            if(URL == "" || target == "") {
                return false;
            }

            if(message == "") {
                message = "{{ __('Are you sure to delete ?') }}";
            }
            var method = `<input type="hidden" name="_method" value="${method}">`;
            openModalByContent(
                {
                    content: `<div class="card modal-alert border-0">
                                <div class="card-body">
                                    <form method="POST" action="${URL}">
                                        <input type="hidden" name="_token" value="${laravelCsrf()}">
                                        ${method}
                                        <div class="head mb-3">
                                            ${message}
                                            <input type="hidden" name="target" value="${target}">
                                        </div>
                                        <div class="foot d-flex align-items-center justify-content-between">
                                            <button type="button" class="modal-close btn btn--info">{{ __("Close") }}</button>
                                            <button type="submit" class="alert-submit-btn btn btn--danger btn-loading">${actionBtnText}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>`,
                },

            );
            }
    </script>
@endpush
