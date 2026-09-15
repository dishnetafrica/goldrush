@isset ($transaction)
<div class="dashboard-list-wrapper">
    <div class="dashboard-list-item-wrapper">
        <div class="dashboard-list-item sent">
            <div class="dashboard-list-left">
                <div class="dashboard-list-user-wrapper">
                    <div class="dashboard-list-user-icon">
                        <i class="las la-arrow-right"></i>
                    </div>
                    <div class="dashboard-list-user-content">
                        @if ($transaction->user_id == auth()->user()->id)
                        <h4 class="title">{{ __("Send Money to") }} <span class="text--warning">{{ $transaction->receiver_info->fullname }}</span></h4>
                        @elseif ($transaction->receiver_id == auth()->user()->id)
                        <h4 class="title">{{ __("Received Money From") }} <span class="text--warning">{{ $transaction->user->fullname }}</span></h4>
                        @endif
                        <span class="{{ $transaction->string_status->class }}">
                            {{ __($transaction->string_status->value) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="dashboard-list-right">
                <h4 class="main-money text--base">{{ get_amount($transaction->request_amount) }}{{ $transaction->request_currency }}</h4>
                <h6 class="exchange-money">{{ get_amount($transaction->receive_amount) }}{{ @$item->payment_currency }}</h6>
            </div>
        </div>
        <div class="preview-list-wrapper">
            <div class="preview-list-item">
                <div class="preview-list-left">
                    <div class="preview-list-user-wrapper">
                        <div class="preview-list-user-icon">
                            <i class="las la-exchange-alt"></i>
                        </div>
                        <div class="preview-list-user-content">
                            <span>{{ __("Exchange Rate") }}</span>
                        </div>
                    </div>
                </div>
                <div class="preview-list-right">
                    <span>{{ get_amount(1,$transaction->request_currency) . " = " . get_amount($transaction->exchange_rate,$transaction->payment_currency) }}</span>
                </div>
            </div>
            <div class="preview-list-item">
                <div class="preview-list-left">
                    <div class="preview-list-user-wrapper">
                        <div class="preview-list-user-icon">
                            <i class="las la-wallet"></i>
                        </div>
                        <div class="preview-list-user-content">
                            <span>{{__("Amount")}}</span>
                        </div>
                    </div>
                </div>
                <div class="preview-list-right">
                    @if ($transaction->user_id == auth()->user()->id)
                    <span class="text--danger">{{ get_amount($transaction->request_amount) }}{{ $transaction->request_currency }}</span>
                    @else
                    <span class="text--danger">{{ get_amount($transaction->receive_amount) }}{{ $transaction->payment_currency }}</span>
                    @endif
                </div>
            </div>
            <div class="preview-list-item">
                <div class="preview-list-left">
                    <div class="preview-list-user-wrapper">
                        <div class="preview-list-user-icon">
                            <i class="las la-battery-half"></i>
                        </div>
                        <div class="preview-list-user-content">
                            <span>{{ __("Fees & Charge") }}</span>
                        </div>
                    </div>
                </div>
                <div class="preview-list-right">
                    <span>{{ get_amount($transaction->total_charge) }}{{ $transaction->request_currency }}</span>
                </div>
            </div>
            <div class="preview-list-item">
                <div class="preview-list-left">
                    <div class="preview-list-user-wrapper">
                        <div class="preview-list-user-icon">
                            <i class="las la-receipt"></i>
                        </div>
                        <div class="preview-list-user-content">
                            <span>{{__("Total Amount")}}</span>
                        </div>
                    </div>
                </div>
                <div class="preview-list-right">
                    <span class="text--warning">{{ get_amount($transaction->total_payable) }}{{ $transaction->payment_currency }}</span>
                </div>
            </div>
            <div class="preview-list-item">
                <div class="preview-list-left">
                    <div class="preview-list-user-wrapper">
                        <div class="preview-list-user-icon">
                            <i class="las la-smoking"></i>
                        </div>
                        <div class="preview-list-user-content">
                            <span>{{ __("Status") }}</span>
                        </div>
                    </div>
                </div>
                <div class="preview-list-right">
                    <span class="{{ $transaction->string_status->class }}">
                        {{ __($transaction->string_status->value) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endisset
