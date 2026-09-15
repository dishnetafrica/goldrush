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
                @forelse ($plans as  $item )
                    <div class="col-xxl-4 col-xl-6 col-md-6 text-center mb-60">
                        <div class="plan-item">
                            <div class="icon-area">
                                <img src="{{ get_image($item->image ?? null , 'site-section') }}" alt="plan">
                            </div>
                            <div class="content-area text-center">
                                <h4 class="title">{{ @$item->data->language->$lang->name }}</h4>
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
                                <button type="button" class="btn--base purchase-plan-btn" data-item='{{ json_encode($item->only(['data','plan_duration','min_invest_requirement','profit','profit_percentage','maximum_investment'])) }}' data-bs-toggle="modal" data-bs-target="#planModal" data-target="{{ $item->slug }}" data-invest-required="{{ $item->min_invest_requirement }}" >{{ __("Invest Gold") }}</button>
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


<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Dashboard
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->


<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Plan Modal
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<div class="modal fade" id="planModal" tabindex="-1" aria-labelledby="planModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="planModalLabel">{{ __("Purchase Plan") }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="las la-times-circle"></i></button>
        </div>
        <form class="modal-form" method="POST" action="javascript:void(0)" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <ul class="plan-modal-list">
                    <li>{{ __("Plan") }} <span class="plan-name"></span></li>
                    <li>{{ __("Duration") }} <span class="plan-duration"></span></li>
                    <li>{{ __("Maximum Invest Amount") }} <span class="plan-max-invest"></span></li>
                    <li>{{ __("Fixed Profit") }} <span class="plan-fixed-profit"></span></li>
                    <li>{{ __("Percent Profit") }} <span class="plan-percent-profit"></span></li>
                </ul>
                <div class="col-12 form-group">
                    <label for="invest_amount">{{ __("Invest Amount") }} <span class="text--base">*</span></label>
                    <input type="number" class="form-control form--control" id="invest_amount"  name="invest_amount" value="{{ old('invest_amount') }}">
                </div>
                <div class="custom-check-group">
                    <input type="checkbox" id="level-1" name="agree">
                    <label for="level-1">{{ __("I have agreed with") }} <a href="{{ setRoute('frontend.useful.links',$useful_link->slug) }}"class="text--base">{{ __("Terms and Condition") }}</a></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn--base bg-danger" data-bs-dismiss="modal">{{ __("Cancel") }}</button>
                <button type="submit" class="btn--base">{{ __("Purchase") }}</button>
            </div>
        </form>
      </div>
    </div>
  </div>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Plan Modal
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

@endsection
@push('script')
    <script>

        $(document).ready(function() {
            openModalWhenError('planModal','#planModal');
        });

        let defaultCurrency = "{{ get_default_currency_code() }}";


        $(".purchase-plan-btn").click(function() {
            let actionURL = "{{ setRoute('user.investment.purchase') }}";
            let slug = $(this).data('target');
            actionURL = actionURL + `/${slug}`;
            $("#planModal").find("form").first().attr("action",actionURL).find("input[name=invest_amount]").val($(this).data("invest-required"));

            let details = $(this).data('item');
            let lang = "{{ selectedLang() }}";

            $("#planModal").find(".plan-name").text(details?.data?.language[lang].name);
            $("#planModal").find(".plan-duration").text(details?.plan_duration + " Days");
            $("#planModal").find(".plan-max-invest").text(parseFloat(details?.maximum_investment ?? 0).toString() + " " + defaultCurrency);
            $("#planModal").find(".plan-fixed-profit").text(parseFloat(details?.profit ?? 0).toString() + " " + defaultCurrency);
            $("#planModal").find(".plan-percent-profit").text(parseFloat(details?.profit_percentage ?? 0).toString() + "%");

            // openModalBySelector("#planModal");
        });
    </script>
@endpush
