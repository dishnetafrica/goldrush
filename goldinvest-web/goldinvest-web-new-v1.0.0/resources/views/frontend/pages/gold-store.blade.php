@extends('frontend.layouts.master')
@section('content')
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start plan section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="plan-section pt-150">
    <div class="container">

        <div class="row justify-content-center mb-60-none">
            @forelse ($golds as  $item )
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 text-center mb-60">
                    <div class="plan-item">
                        <div class="icon-area">
                            <img src="{{ get_image($item->image ?? null , 'site-section') }}" alt="gold">
                        </div>
                        <div class="content-area text-center">
                            <h2 class="price">{{ $default_currency->symbol }}{{ get_amount(@$item->price) }} <span>/ 1 {{ @$item->type }}</span></h2>
                            <span>{{ @$item->title->language->$lang->title ??  @$item->title->language->$default->title }}</span>
                        </div>
                        <ul class="plan-list">
                            <li><i class="las la-exclamation"></i> {{ @$item->weight }}</li>
                            <li><i class="las la-exclamation"></i> {{ __("Type") }}- {{ @$item->type }}</li>
                            <li><i class="las la-exclamation"></i> {{ __("Purity") }}- {{ @$item->purity }}</li>
                            <li><i class="las la-exclamation"></i> {{ __("Manufacturer") }}- {{ @$item->manufacturer }}</li>
                            <li><i class="las la-exclamation"></i> {{ __("Country of Origin") }}- {{ @$item->country_of_origin }}</li>
                        </ul>
                        <div class="plan-btn pt-30">
                            <a href="{{setRoute('user.investment.gold.store')}}" class="btn--base">{{ __("Buy Gold") }}</a>
                        </div>
                    </div>
                </div>
            @empty
                @include('user.components.alerts.empty',['colspan' => 7])
            @endforelse
        </div>
        <nav>
            <ul class="pagination justify-content-end">
                {{ get_paginate($golds) }}
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
