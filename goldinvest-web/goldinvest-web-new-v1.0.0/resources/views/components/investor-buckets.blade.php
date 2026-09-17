@php use App\Investor\Support\Money; @endphp
@if (! $verified)
    <div class="alert alert-warning">
        {{ __("Your account summary is being checked and is temporarily unavailable. Your money is unaffected. Please contact support if this persists.") }}
    </div>
@else
    <div class="dashboard-item-area">
        <div class="row mb-20-none">
            @foreach ([
                ['label' => __('Available Balance'), 'value' => $position['available'], 'icon' => 'fas fa-wallet'],
                ['label' => __('Profit Balance'), 'value' => $position['profit'], 'icon' => 'fas fa-chart-line'],
                ['label' => __('Capital in Active Deals'), 'value' => $position['committed'], 'icon' => 'fas fa-coins'],
                ['label' => __('Total Investor Position'), 'value' => $total, 'icon' => 'fas fa-university'],
            ] as $card)
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-6 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ $card['label'] }}</span>
                            <h3 class="title">
                                {{ Money::format($card['value']) }}
                                <span class="text--base">{{ get_default_currency_code() }}</span>
                            </h3>
                        </div>
                        <div class="dashboard-icon"><i class="{{ $card['icon'] }}"></i></div>
                        <div class="dash-item-bg bg_img"
                             data-background="{{ asset('public/frontend/assets/images/banner/banner-bg2.webp') }}"></div>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="mb-20" style="font-size:13px; opacity:.75;">
            {{ __("Available to withdraw now") }}:
            <strong>{{ Money::format($position['available'] + $position['profit']) }} {{ get_default_currency_code() }}</strong>.
            {{ __("Capital in active deals is owed to you but is working in a trading deal until it closes.") }}
            {{ __("Your position with the company is a USD account balance; no gold is held in your name.") }}
            <a href="{{ setRoute('user.statements.index') }}">{{ __("View statements") }}</a>
        </p>
    </div>
@endif
