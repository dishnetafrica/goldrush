@extends('user.layouts.master')

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Dashboard")])
@endsection

@section('content')
    <div class="dashboard-area mt-10">
        <div class="dashboard-header-wrapper">
            <h3 class="title">{{ __("Overview") }}</h3>
        </div>
        <div class="dashboard-item-area">
            <div class="row mb-20-none">
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ __('Current Balance') }}</span>
                            <h3 class="title">{{ get_amount(@$wallet->balance) }} <span class="text--base">{{ get_default_currency_code() }}</span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="dash-item-bg bg_img" data-background="{{ asset('public/frontend/assets/images/banner/banner-bg2.webp') }}"></div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ __('Profit Balance') }}</span>
                            <h3 class="title">{{ get_amount(@$wallet->profit_balance) }} <span class="text--base">{{ get_default_currency_code() }}</span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="dash-item-bg bg_img" data-background="{{ asset('public/frontend/assets/images/banner/banner-bg2.webp') }}"></div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ __('Investment Amount') }}</span>
                            <h3 class="title">{{ get_amount($auth_user->investPlans->sum('invest_amount')) }} <span class="text--base">{{ $default_currency?->code ?? "" }}</span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="dash-item-bg bg_img" data-background="{{ asset('public/frontend/assets/images/banner/banner-bg2.webp') }}"></div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ __('Total Profit') }}</span>
                            <h3 class="title">{{ get_amount($auth_user->profit->sum('profit_amount')) }}  <span class="text--base">{{ $default_currency?->code ?? "" }}</span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="dash-item-bg bg_img" data-background="{{ asset('public/frontend/assets/images/banner/banner-bg2.webp') }}"></div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ __('Total Plan Invest') }}</span>
                            <h3 class="title">{{ $auth_user->investPlans->count() }} </h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-ribbon"></i>
                        </div>
                        <div class="dash-item-bg bg_img" data-background="{{ asset('public/frontend/assets/images/banner/banner-bg2.webp') }}"></div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ __('Total Send Money') }}</span>
                            <h3 class="title">{{ get_amount(@$send_money_amount) }}<span class="text--base">{{ $default_currency?->code ?? "" }}</span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div class="dash-item-bg bg_img" data-background="{{ asset('public/frontend/assets/images/banner/banner-bg2.webp') }}"></div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ __("Total Add Money") }}</span>
                            <h3 class="title">{{ get_amount(@$add_money_amount) }} <span class="text--base">{{ get_default_currency_code() }}</span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <div class="dash-item-bg bg_img" data-background="{{ asset('public/frontend/assets/images/banner/banner-bg2.webp') }}"></div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ __("Total Money Out") }}</span>
                            <h3 class="title">{{ get_amount(@$money_out_amount) }} <span class="text--base">{{ get_default_currency_code() }}</span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-arrow-alt-circle-right"></i>
                        </div>
                        <div class="dash-item-bg bg_img" data-background="{{ asset('public/frontend/assets/images/banner/banner-bg2.webp') }}"></div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ __("Total Transaction") }}</span>
                            <h3 class="title">{{ get_amount(@$total_transactions) }} <span class="text--base">{{ get_default_currency_code() }}</span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-arrows-alt-h"></i>
                        </div>
                        <div class="dash-item-bg bg_img" data-background="{{ asset('public/frontend/assets/images/banner/banner-bg2.webp') }}"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="chart-area mt-30">
        <div class="row mb-20-none">
            <div class="col-xxl-7 col-xl-7 col-lg-7 mb-20">
                <div class="chart-wrapper">
                    <div class="dashboard-header-wrapper">
                        <h4 class="title">{{ __("My Invest") }}</h4>
                    </div>
                    <div class="chart-container">
                        <div id="chart1" data-chart_one_data="{{ json_encode($chartData['chart_one_data']) }}" data-month_day="{{ json_encode($chartData['month_day']) }}" class="chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-5 col-xl-5 col-lg-5 mb-20">
                <div class="chart-wrapper">
                    <div class="dashboard-header-wrapper">
                        <h4 class="title">{{ __("Send Money") }}</h4>
                    </div>
                    <div class="chart-container">
                        <div id="chart2"  data-chart_two_data="{{ json_encode($chartData['chart_two_data']) }}" data-month_day="{{ json_encode($chartData['month_day']) }}"  class="chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="dashboard-list-area mt-20">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ __("Recent Transactions") }}</h4>
            <div class="dashboard-btn-wrapper">
                <div class="dashboard-btn">
                    <a href="{{ setRoute('user.history.transaction') }}" class="btn--base">{{ __("View More") }}</a>
                </div>
            </div>
        </div>
            @include('user.components.transaction.log',[
                'logs'      => $transactions,
            ])
    </div>

@endsection
@push('script')
<script>
    var chart1 = $('#chart1');
    var chart_one_data = chart1.data('chart_one_data');
    var month_day = chart1.data('month_day');
    var options = {
          series: [{
          name: `{{ __("Invest") }}`,
          color: "#FC5B3F",
          data: chart_one_data.invest_data
        }, {
          name: `{{ __("Profit") }}`,
          color: "#00E396",
          data: chart_one_data.profit_data
        }],
          chart: {
          height: 350,
          type: 'area',
          toolbar: {
              show: false
          },
        },
        dataLabels: {
          enabled: false
        },
        stroke: {
          curve: 'smooth'
        },
        xaxis: {
          type: 'datetime',
          categories:month_day
        },
        tooltip: {
          x: {
            format: 'dd/MM/yy HH:mm'
          },
        },
        };

    var chart = new ApexCharts(document.querySelector("#chart1"), options);
    chart.render();

    var chart2 = $("#chart2");
    var chart_two_data = chart2.data("chart_two_data");
    var month_day = chart2.data("month_day");
    var options = {
          series: [{
          name: `{{ __("Send Money") }}`,
          color: "#FC5B3F",
          data: chart_two_data.send_money_data
        }],
          chart: {
          height: 350,
          type: 'area',
          toolbar: {
              show: false
          },
        },
        dataLabels: {
          enabled: false
        },
        stroke: {
          curve: 'smooth'
        },
        xaxis: {
          type: 'datetime',
          categories: month_day
        },
        tooltip: {
          x: {
            format: 'dd/MM/yy HH:mm'
          },
        },
    };

    var chart = new ApexCharts(document.querySelector("#chart2"), options);
    chart.render();
</script>
@endpush
