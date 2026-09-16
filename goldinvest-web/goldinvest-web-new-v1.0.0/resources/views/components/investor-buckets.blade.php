@php use App\Investor\Support\Money; @endphp
<div class="row mb-20 investor-buckets">
    @if (! $verified)
        <div class="col-12">
            <div class="alert alert-warning mb-0">
                {{ __("Your account summary is being checked and is temporarily unavailable. Your money is unaffected. Please contact support if this persists.") }}
            </div>
        </div>
    @else
        <div class="col-xl-3 col-lg-6 col-md-6 mb-10">
            <div class="dashboard-card">
                <span class="card-title">{{ __("Available Balance") }}</span>
                <h4 class="amount">{{ Money::format($position['available']) }} <small>USD</small></h4>
                <span class="card-note">{{ __("Free to withdraw or commit") }}</span>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 mb-10">
            <div class="dashboard-card">
                <span class="card-title">{{ __("Profit Balance") }}</span>
                <h4 class="amount">{{ Money::format($position['profit']) }} <small>USD</small></h4>
                <span class="card-note">{{ __("Your share of completed deals") }}</span>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 mb-10">
            <div class="dashboard-card">
                <span class="card-title">{{ __("Capital in Active Deals") }}</span>
                <h4 class="amount">{{ Money::format($position['committed']) }} <small>USD</small></h4>
                <span class="card-note">
                    {{ __("Working in trading, not withdrawable") }}
                </span>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 mb-10">
            <div class="dashboard-card">
                <span class="card-title">{{ __("Total Investor Position") }}</span>
                <h4 class="amount">{{ Money::format($total) }} <small>USD</small></h4>
                <span class="card-note">
                    {{ __("Available to withdraw now") }}:
                    {{ Money::format($position['available'] + $position['profit']) }}
                </span>
            </div>
        </div>
        <div class="col-12">
            <p class="text-muted mb-0" style="font-size:13px;">
                {{ __("Your position with the company is a USD account balance. The company trades gold using pooled investor capital; no gold is held in your name.") }}
                <a href="{{ setRoute('user.statements.index') }}">{{ __("View statements") }}</a>
            </p>
        </div>
    @endif
</div>
