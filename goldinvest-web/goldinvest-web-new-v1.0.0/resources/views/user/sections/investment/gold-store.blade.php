@extends('user.layouts.master')
@section('content')

<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Dashboard
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

        <div class="dashboard-area mt-10">
            <div class="dashboard-header-wrapper">
                <h3 class="title">{{ $page_title }}</h3>
            </div>
            <div class="row justify-content-center mb-60-none mt-60">
                @forelse ($golds ?? [] as  $item )
                    <div class="col-xxl-4 col-xl-6 col-md-6 text-center mb-60">
                        <div class="plan-item">
                            <div class="icon-area">
                                <img src="{{ get_image($item->image ?? null , 'site-section') }}" alt="gold">
                            </div>
                            <div class="content-area text-center">
                                <h2 class="price">{{ $default_currency->symbol }}{{ get_amount(@$item->price) }} <span>/ 1 {{ @$item->type }}</span></h2>
                                <span>{{ @$item->title->language->$lang->title }}</span>
                            </div>
                            <ul class="plan-list">
                                <li><i class="las la-exclamation"></i> {{ @$item->weight }}</li>
                                <li><i class="las la-exclamation"></i> {{ __("Type") }}- {{ @$item->type }}</li>
                                <li><i class="las la-exclamation"></i> {{ __("Purity") }}- {{ @$item->purity }}</li>
                                <li><i class="las la-exclamation"></i> {{ __("Manufacturer") }}- {{ @$item->manufacturer }}</li>
                                <li><i class="las la-exclamation"></i> {{ __("Country of Origin") }}- {{ @$item->country_of_origin }}</li>
                            </ul>

                            <div class="plan-btn pt-30">
                                <a href="{{ setRoute('user.investment.checkout', $item->slug) }}" class="btn--base">{{ __("Buy Gold") }}</a>
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


<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Dashboard
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
@endsection
