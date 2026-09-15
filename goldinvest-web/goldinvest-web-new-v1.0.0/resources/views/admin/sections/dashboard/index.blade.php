@extends('admin.layouts.master')

@push('css')

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
    ], 'active' => __("Dashboard")])
@endsection

@section('content')
    <div class="dashboard-area">
        <div class="dashboard-item-area">
            <div class="row">
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("Total Users") }}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ @$users }}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--success">{{ __("Active") }} {{ @$active_users }}</span>
                                    <span class="badge badge--info">{{ __("Unverified") }} {{ @$unverified_users }}</span>
                                    <span class="badge badge--warning">{{ __("Banned") }} {{ @$banned_users }}</span>
                                </div>
                            </div>
                            <div class="right">
                                <div class="chart" id="chart6" data-percent="{{ ($users != 0) ? intval(($active_users/$users)*100) : '0' }}"> <span>
                                    @if($users != 0)
                                        {{ intval(($active_users/$users)*100) }}%
                                    @else
                                        0%
                                    @endif
                                </span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("Add Money Balance") }}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ get_amount($add_money_amount) }} {{ get_default_currency_code() }}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--info">{{ __("Total") }} {{ get_amount($add_money_amount) }} {{ get_default_currency_code() }}</span>
                                    <span class="badge badge--warning">{{ __("Pending") }} {{ get_amount($add_money_pending) }} {{ get_default_currency_code() }}</span>
                                </div>
                            </div>
                            <div class="right">
                                <div class="chart" id="chart7" data-percent="{{ ($add_money_amount != 0) ? intval(($add_money_pending/$add_money_amount)*100) : '0' }}"><span> @if($add_money_amount != 0)
                                    {{ intval(($add_money_pending/$add_money_amount)*100) }}%
                                @else
                                    0%
                                @endif</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("Money Out Balance") }}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ get_amount($money_out_amount) }} {{ get_default_currency_code() }}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--info">{{ __("Total") }} {{ get_amount($money_out_amount) }} {{ get_default_currency_code() }}</span>
                                    <span class="badge badge--warning">{{ __("Pending") }} {{ get_amount($money_out_pending) }} {{ get_default_currency_code() }}</span>
                                </div>
                            </div>
                            <div class="right">
                                <div class="chart" id="chart8" data-percent="{{ ($money_out_amount != 0) ? intval(($money_out_pending/$money_out_amount)*100) : '0' }}"><span>@if($money_out_amount != 0)
                                    {{ intval(($money_out_pending/$money_out_amount)*100) }}%
                                @else
                                    0%
                                @endif</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("Add Money Request") }}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ @$add_money_request }}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--info">{{ __("Success") }} {{ $add_money_success_request }}</span>
                                    <span class="badge badge--warning">{{ __("Pending") }}  {{ $add_money_pending_request }}</span>
                                </div>
                            </div>
                            <div class="right">
                                <div class="chart" id="chart9" data-percent="{{ ($add_money_request != 0) ? intval(($add_money_pending_request/$add_money_request)*100) : '0' }}">
                                    <span>
                                        @if($add_money_request != 0)
                                            {{ intval(($add_money_pending_request/$add_money_request)*100) }}%
                                        @else
                                            0%
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("Money Out Request") }}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ @$money_out_request }}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--success">{{ __("Success") }} {{ $money_out_success_request }}</span>
                                    <span class="badge badge--warning">{{ __("Pending") }}  {{ $money_out_pending_request }}</span>
                                </div>
                            </div>
                            <div class="right">
                                <div class="chart" id="chart10" data-percent="{{ ($money_out_request != 0) ? intval(($money_out_pending_request/$money_out_request)*100) : '0' }}"><span> @if($money_out_request != 0)
                                    {{ intval(($money_out_pending_request/$money_out_request)*100) }}%
                                @else
                                    0%
                                @endif</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("User Active Tickets") }}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ @$active_tickets }}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--warning">{{ __("Pending") }} {{ @$pending_tickets }}</span>
                                    <span class="badge badge--success">{{ __("Solved") }} {{ @$solved_tickets }}</span>
                                </div>
                            </div>
                            <div class="right">
                                <div class="chart" id="chart11" data-percent="{{ ($active_tickets != 0) ? intval(($pending_tickets/$active_tickets)*100) : '0' }}"><span>@if($active_tickets != 0)
                                    {{ intval(($pending_tickets/$active_tickets)*100) }}%
                                @else
                                    0%
                                @endif</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("Total User Invest") }}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ @$total_invest }}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--warning">{{ __("Ongoing") }} {{ @$total_invest_running }}</span>
                                    <span class="badge badge--success">{{ __("Completed") }} {{ @$total_invest_completed }}</span>
                                </div>
                            </div>
                            <div class="right">
                                <div class="chart" id="chart12" data-percent="{{ ($total_invest != 0) ? intval(($total_invest_running/$total_invest)*100) : '0' }}"><span>@if($total_invest != 0)
                                    {{ intval(($total_invest_running/$total_invest)*100) }}%
                                @else
                                    0%
                                @endif</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("Total Gold Orders") }}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ @$total_order }}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--warning">{{ __("Ongoing") }} {{ @$total_order_ongoing }}</span>
                                    <span class="badge badge--success">{{ __("Delivered") }} {{ @$total_order_delivered }}</span>
                                </div>
                            </div>
                            <div class="right">
                                <div class="chart" id="chart13" data-percent="{{ ($total_order != 0) ? intval(($total_order_ongoing/$total_order)*100) : '0' }}"><span>@if($total_order != 0)
                                    {{ intval(($total_order_ongoing/$total_order)*100) }}%
                                @else
                                    0%
                                @endif</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("Total Profit") }}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{ get_amount($total_profit) }} {{ get_default_currency_code() }}</h2>
                                </div>
                                <div class="user-badge">
                                </div>
                            </div>
                            <div class="right">
                                <div class="chart" id="chart14" data-percent="100"><span>100%</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxxl-4 col-xxl-3 col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-15">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <div class="left">
                                <h6 class="title">{{ __("Admin Profits") }}</h6>
                                <div class="user-info">
                                    <h2 class="user-count">{{  get_default_currency_symbol() }}{{get_amount(totalAdminProfits(),get_default_currency_code()) }}</h2>
                                </div>
                                <div class="user-badge">
                                    <span class="badge badge--success">{{__("LIVE TIME SUPERADMIN PROFITS BALANCE")}}</span>
                                </div>
                            </div>
                            <div class="right">
                                <div class="chart" id="chart17" data-percent="100"><span>100%</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="chart-area mt-15">
        <div class="row mb-15-none">
            <div class="col-xxl-6 col-xl-6 col-lg-6 mb-15">
                <div class="chart-wrapper">
                    <div class="chart-area-header">
                        <h5 class="title">{{ __('Monthly Add Money Chart') }}</h5>
                    </div>
                    <div class="chart-container">
                        <div id="chart1"  data-chart_one_data="{{ json_encode($chartData['chart_one_data']) }}" data-month_day="{{ json_encode($chartData['month_day']) }}" class="sales-chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-6 col-xl-6 col-lg-6 mb-15">
                <div class="chart-wrapper">
                    <div class="chart-area-header">
                        <h5 class="title">{{ __("Invest & Profit Chart") }}</h5>
                    </div>
                    <div class="chart-container">
                        <div id="chart2" data-chart_two_data="{{ json_encode($chartData['chart_two_data']) }}" data-month_day="{{ json_encode($chartData['month_day']) }}" class="revenue-chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-6 col-xl-6 col-lg-6 mb-15">
                <div class="chart-wrapper">
                    <div class="chart-area-header">
                        <h5 class="title">{{ __('Monthly Money Out Chart') }}</h5>
                    </div>
                    <div class="chart-container">
                        <div id="chart3"  data-chart_three_data="{{ json_encode($chartData['chart_three_data']) }}" data-month_day="{{ json_encode($chartData['month_day']) }}"  class="order-chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-xxxl-6 col-xxl-3 col-xl-6 col-lg-6 mb-15">
                <div class="chart-wrapper">
                    <div class="chart-area-header">
                        <h5 class="title">{{ __("User Analytics") }}</h5>
                    </div>
                    <div class="chart-container">
                        <div id="chart4" data-chart_four_data="{{ json_encode($chartData['chart_four_data']) }}"  class="balance-chart"></div>
                    </div>
                    <div class="chart-area-footer">
                        <div class="chart-btn">
                            <a href="{{ setRoute('admin.users.index') }}" class="btn--base w-100">{{ __('View User') }}</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxxl-12 col-xxl-3 col-xl-12 col-lg-12 mb-15">
                <div class="chart-wrapper">
                    <div class="chart-area-header">
                        <h5 class="title">{{ __("New User") }}</h5>
                    </div>
                    <div class="chart-container">
                        <div id="chart5" data-chart_five_data="{{ json_encode($chartData['chart_five_data']) }}" class="growth-chart"></div>
                    </div>
                    <div class="chart-area-footer">
                        <div class="chart-btn">
                            <a href="{{ setRoute('admin.users.index') }}" class="btn--base w-100">{{ __('View User') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="table-area mt-15">
        <div class="table-wrapper">
            <div class="table-header">
                <h5 class="title">{{ __("Latest Add Money") }}</h5>
            </div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>{{ __("Full Name") }}</th>
                            <th>{{ __("Email") }}</th>
                            <th>{{ __("Username") }}</th>
                            <th>{{ __("Phone") }}</th>
                            <th>{{ __("Amount") }}</th>
                            <th>{{ __("Gateway") }}</th>
                            <th>{{ __("Status") }}</th>
                            <th>{{ __("Time") }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions  as $key => $item)
                            <tr>
                                <td>
                                    <ul class="user-list">
                                        <li><img src="{{ get_image($item->user->image ?? "","user-profile") }}" alt="user"></li>
                                    </ul>
                                </td>
                                <td>{{ $item->user->firstname }} {{ $item->user->lastname }}</td>
                                <td>{{ $item->user->email }}</td>
                                <td>{{ $item->user->username }}</td>
                                <td>{{ $item->user->full_mobile ?? '' }}</td>
                                <td>{{ get_amount($item->receive_amount,$item->creator_wallet->currency->code) }}</td>
                                <td><span class="text--info">{{ $item->gateway_currency->gateway->name }}</span></td>
                                <td>
                                    <span class="{{ $item->stringStatus->class }}">{{ $item->stringStatus->value }}</span>
                                </td>
                                <td>{{ $item->created_at->format('d-m-y h:i:s A') }}</td>
                                <td>
                                    @if ($item->status == 1)
                                        <button type="button" class="btn btn--base bg--success"><i
                                                class="las la-check-circle"></i></button>
                                    @elseif($item->status == 4)
                                    <button type="button" class="btn btn--base bg--danger"><i
                                        class="las la-times-circle"></i></button>
                                    @endif
                                    @include('admin.components.link.custom',[
                                        'href'          => setRoute('admin.add.money.details', $item->id),
                                        'class'         => "btn btn--base modal-btn",
                                        'icon'          => "las la-expand",
                                        'permission'    => "admin.add.money.details",
                                    ])
                                </td>
                            </tr>
                        @empty
                            <div class="alert alert-primary">{{ __('No data found!') }}
                            </div>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    // apex-chart
    var chart1 = $('#chart1');
    var chart_one_data = chart1.data('chart_one_data');
    var month_day = chart1.data('month_day');
    var options = {
    series: [{
    name: `{{ __("Pending") }}`,
    color: "#5A5278",
    data: chart_one_data.pending_data
    }, {
    name: `{{ __("Completed") }}`,
    color: "#6F6593",
    data: chart_one_data.success_data
    }, {
    name: `{{ __("Canceled") }}`,
    color: "#8075AA",
    data:  chart_one_data.canceled_data
    }, {
    name: `{{ __("Hold") }}`,
    color: "#A192D9",
    data: chart_one_data.hold_data
    }],
    chart: {
    type: 'bar',
    height: 350,
    stacked: true,
    toolbar: {
        show: false
    },
    zoom: {
        enabled: true
    }
    },
    responsive: [{
    breakpoint: 480,
    options: {
        legend: {
        position: 'bottom',
        offsetX: -10,
        offsetY: 0
        }
    }
    }],
    plotOptions: {
    bar: {
        horizontal: false,
        borderRadius: 10
    },
    },
    xaxis: {
    type: 'datetime',
    categories: month_day,
    },
    legend: {
    position: 'bottom',
    offsetX: 40
    },
    fill: {
    opacity: 1
    }
    };

    var chart = new ApexCharts(document.querySelector("#chart1"), options);
    chart.render();

    var chart2 = $('#chart2');
    var chart_two_data = chart2.data('chart_two_data');
    var month_day = chart2.data('month_day');
    var options = {
          series: [{
          name: `{{ __("Invest") }}`,
          color: "#5A5278",
          data: chart_two_data.invest_data
        }, {
          name: `{{ __("Profit") }}`,
          color: "#6F6593",
          data: chart_two_data.profit_data
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


    var chart = new ApexCharts(document.querySelector("#chart2"), options);
    chart.render();
    var chart3 = $('#chart3');
    var chart_three_data = chart3.data('chart_three_data');
    var month_day = chart3.data('month_day');
    var options = {
    series: [{
    name: `{{ __("Pending") }}`,
    color: "#5A5278",
    data: chart_three_data.pending_data
    }, {
    name: `{{ __("Completed") }}`,
    color: "#6F6593",
    data: chart_three_data.success_data
    }, {
    name: `{{ __("Canceled") }}`,
    color: "#8075AA",
    data: chart_three_data.canceled_data
    },{
        name: `{{ __("Hold") }}`,
        color: "#A192D9",
        data: chart_three_data.hold_data
    }],
    chart: {
    type: 'bar',
    height: 350,
    stacked: true,
    toolbar: {
        show: false
    },
    zoom: {
        enabled: true
    }
    },
    responsive: [{
    breakpoint: 480,
    options: {
        legend: {
        position: 'bottom',
        offsetX: -10,
        offsetY: 0
        }
    }
    }],
    plotOptions: {
    bar: {
        horizontal: false,
        borderRadius: 10
    },
    },
    xaxis: {
    type: 'datetime',
    categories: month_day,
    },
    legend: {
    position: 'bottom',
    offsetX: 40
    },
    fill: {
    opacity: 1
    }
    };

    var chart = new ApexCharts(document.querySelector("#chart3"), options);
    chart.render();

    var chart4 = $('#chart4');
    var chart_four_data = chart4.data('chart_four_data');
    var options = {
    series: chart_four_data,
    chart: {
    width: 350,
    type: 'pie'
    },
    colors: ['#5A5278', '#6F6593', '#8075AA', '#A192D9'],
    labels: [`{{ __("Active") }}`, `{{ __("Banned") }}`, `{{ __("Unverified") }}`, `{{ __("All") }}`],
    responsive: [{
    breakpoint: 1480,
    options: {
        chart: {
        width: 280
        },
        legend: {
        position: 'bottom'
        }
    },
    breakpoint: 1199,
    options: {
        chart: {
        width: 380
        },
        legend: {
        position: 'bottom'
        }
    },
    breakpoint: 575,
    options: {
        chart: {
        width: 280
        },
        legend: {
        position: 'bottom'
        }
    }
    }],
    legend: {
    position: 'bottom'
    },
    };

    var chart = new ApexCharts(document.querySelector("#chart4"), options);
    chart.render();

    var chart5 = $('#chart5');
    var chart_five_data = chart5.data('chart_five_data');
    var options = {
    series: chart_five_data,
    chart: {
    width: 350,
    type: 'donut',
    },
    colors: ['#5A5278', '#6F6593', '#8075AA', '#A192D9'],
    labels: [`{{ __("Today") }}`, `{{ __("1 week") }}`, `{{ __("1 month") }}`, `{{ __("1 year") }}`],
    legend: {
        position: 'bottom'
    },
    responsive: [{
    breakpoint: 1600,
    options: {
        chart: {
        width: 100,
        },
        legend: {
        position: 'bottom'
        }
    },
    breakpoint: 1199,
    options: {
        chart: {
        width: 380
        },
        legend: {
        position: 'bottom'
        }
    },
    breakpoint: 575,
    options: {
        chart: {
        width: 280
        },
        legend: {
        position: 'bottom'
        }
    }
    }]
    };

    var chart = new ApexCharts(document.querySelector("#chart5"), options);
    chart.render();
</script>
@endpush
