@extends('frontend.layouts.master')
@section('content')

<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start plan section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="plan-section pt-150">
    <div class="container">
        <div class="row justify-content-center mb-60-none">
            @forelse ($plans as  $item )
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 text-center mb-60">
                    <div class="plan-item">
                        <div class="icon-area">
                            <img src="{{ get_image($item->image ?? null , 'site-section') }}" alt="">
                        </div>
                        <div class="content-area text-center">
                            <h4 class="title">{{ @$item->data->language->$lang->name ?? @$item->data->language->$default->name }}</h4>
                            @if ($item->minimum_investment_offer > 0)
                            <h2 class="price">{{ __("From") }} / <del>{{ $default_currency->symbol }} {{ get_amount($item->minimum_investment) ?? "" }} </del> <span>{{ $default_currency->symbol }} {{ get_amount(@$item->minimum_investment_offer)}}</span>
                            </h2>
                        @else
                            <h2 class="price">{{ __("From") }} /<span class="minimum-amount">{{ $default_currency->symbol }} {{ get_amount(@$item->minimum_investment)}}</span>
                            </h2>
                        @endif
                            <span>{{ __("Profit Return Type") }} {{ @$item->profit_return_type}}</span>
                        </div>
                        <ul class="plan-list">
                            <li><i class="las la-check"></i> {{ __("Plan Duration") }} {{ @$item->plan_duration}} @if($item->plan_duration > 1) {{ __("Days") }} @else {{ __("Day") }} @endif</li>
                            <li><i class="las la-check"></i> {{ __("Maximum Investment") }} {{ $default_currency->symbol }} {{ get_amount(@$item->maximum_investment)}}</li>
                            <li><i class="las la-check"></i> {{ __("Fixed Profit") }}  {{ $default_currency->symbol }} {{ get_amount(@$item->profit)}}</li>
                            <li><i class="las la-check"></i> {{ __("Percentage Profit") }} {{ get_amount(@$item->profit_percentage)}} %</li>
                            <li><i class="las la-check"></i> {{ __("Just Click To Try This") }}</li>
                        </ul>
                        <div class="plan-btn pt-30">
                            <a href="{{ setRoute('user.investment.plan') }}" class="btn--base">{{ __("Invest Gold") }}</a>
                        </div>
                    </div>
                </div>
            @empty
                @include('user.components.alerts.empty',['colspan' => 7])
            @endforelse
        </div>
        <nav>
            <ul class="pagination justify-content-end">
                {{ get_paginate($plans) }}
            </ul>
        </nav>
    </div>
</section>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End plan section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->


<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start app section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="merchant-app-section ptb-120">
    @include('frontend.sections.app-section')
</section>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End app section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->


<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start brand section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
@include('frontend.sections.brand-section')
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End brand section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

@endsection
