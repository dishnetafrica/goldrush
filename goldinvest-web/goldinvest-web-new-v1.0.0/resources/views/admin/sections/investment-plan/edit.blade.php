@php
    $app_local = get_default_language_code();
@endphp

@extends('admin.layouts.master')

@push('css')
    <style>
        .fileholder {
            min-height: 374px !important;
        }

        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,.fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view{
            height: 330px !important;
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
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __($page_title) }}</h6>
        </div>
        <div class="card-body">
            <form class="card-form" action="{{ setRoute('admin.investment.plan.update',$plan->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row justify-content-center mb-10-none">
                    <div class="col-xl-4 col-lg-4 form-group">
                        @include('admin.components.form.input-file',[
                            'label'             => __("Image"),
                            'name'              => "image",
                            'class'             => "file-holder",
                            'old_files_path'    => files_asset_path("site-section"),
                            'old_files'         => old("old_image",$plan->image ?? null),
                        ])
                    </div>
                    <div class="col-xl-8 col-lg-8">
                        <div class="product-tab">
                            <nav>
                                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                                    @foreach ($languages as $item)
                                        <button class="nav-link @if (get_default_language_code() == $item->code) active @endif" id="{{$item->name}}-tab" data-bs-toggle="tab" data-bs-target="#{{$item->name}}" type="button" role="tab" aria-controls="{{ $item->name }}" aria-selected="true">{{ $item->name }}</button>
                                    @endforeach
                                </div>
                            </nav>
                            <div class="tab-content" id="nav-tabContent">
                                @foreach ($languages as $item)
                                    @php
                                        $lang_code = $item->code;
                                    @endphp
                                    <div class="tab-pane @if (get_default_language_code() == $item->code) fade show active @endif" id="{{ $item->name }}" role="tabpanel" aria-labelledby="english-tab">
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'         => __("Plan Name"),
                                                'label_after'   => "*",
                                                'name'          => $item->code . "_name",
                                                'value'         => old($item->code . "_name",$plan->data?->language?->$lang_code?->name ?? null),
                                            ])
                                        </div>
                                        <div class="form-group">
                                            @include('admin.components.form.input',[
                                                'label'         => __("Plan Title"),
                                                'label_after'   => "*",
                                                'name'          => $item->code . "_title",
                                                'value'         => old($item->code . "_title",$plan->data?->language?->$lang_code?->title ?? null),
                                            ])
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="form-group">
                            @include('admin.components.form.input',[
                                'label'         => __("Plan Duration(Day)"),
                                'label_after'   => "*",
                                'type'          => "number",
                                'name'          => "plan_duration",
                                'value'         => old("plan_duration",$plan->plan_duration ?? null)
                            ])
                        </div>
                        <div class="form-group">
                            <label>{{ __("Profit Return Type") }} <span class="text--base">*</span></label>
                            <select class="form--control select2" name="profit_return_type">
                                <option value=""  disabled>{{ __("Choose one") }}</option>
                                <option value="{{ global_const()::INVEST_PROFIT_DAILY_BASIS }}" @if($plan->profit_return_type == global_const()::INVEST_PROFIT_DAILY_BASIS) selected @endif>{{ __("Daily") }}</option>
                                <option value="{{ global_const()::INVEST_PROFIT_ONE_TIME }}" @if($plan->profit_return_type == global_const()::INVEST_PROFIT_ONE_TIME) selected @endif>{{ __("One Time") }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            @include('admin.components.form.input',[
                                'label'         => __("Minimum Investment"),
                                'label_after'   => "*",
                                'type'          => "number",
                                'name'          => "minimum_investment",
                                'value'         => old("minimum_investment", get_amount($plan->minimum_investment) ?? null)
                            ])
                        </div>
                        <div class="form-group">
                            @include('admin.components.form.input',[
                                'label'         => __("Minimum Investment Offer (Optional)"),
                                'name'          => "minimum_investment_offer",
                                'type'          => "number",
                                'value'         => old("minimum_investment_offer", get_amount($plan->minimum_investment_offer) ?? null)
                            ])
                        </div>
                        <div class="form-group">
                            @include('admin.components.form.input',[
                                'label'         => __("Maximum Investment"),
                                'label_after'   => "*",
                                'type'          => "number",
                                'name'          => "maximum_investment",
                                'value'         => old("maximum_investment", get_amount($plan->maximum_investment) ?? null)
                            ])
                        </div>
                        <div class="form-group">
                            @include('admin.components.form.input',[
                                'label'         => __("Profit (Fixed)"),
                                'label_after'   => "*",
                                'type'          => "number",
                                'name'          => "profit",
                                'value'         => old("profit", get_amount($plan->profit) ?? null)
                            ])
                        </div>
                        <div class="form-group">
                            @include('admin.components.form.input',[
                                'label'         => __("Profit (Percentage)"),
                                'label_after'   => "*",
                                'type'          => "number",
                                'name'          => "profit_percentage",
                                'value'         => old("profit_percentage", get_amount($plan->profit_percentage) ?? null)
                            ])
                        </div>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.button.form-btn',[
                            'class'         => "w-100 btn-loading",
                            'text'          => __("Submit"),
                            'permission'    => "admin.investment.plan.update"
                        ])
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('script')

@endpush
