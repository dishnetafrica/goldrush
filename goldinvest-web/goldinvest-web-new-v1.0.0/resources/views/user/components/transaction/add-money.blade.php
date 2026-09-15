@isset ($transaction)
<div class="dashboard-list-wrapper">
    <div class="dashboard-list-item-wrapper">
        <div class="dashboard-list-item sent">
            <div class="dashboard-list-left">
                <div class="dashboard-list-user-wrapper">
                    <div class="dashboard-list-user-icon">
                        <i class="las la-plus"></i>
                    </div>
                    <div class="dashboard-list-user-content">
                        <h4 class="title">{{ __("Add Money via") }} <span class="text--warning">{{ $transaction->gateway_currency->gateway->name  }}</span></h4>
                        <span class="{{ $transaction->string_status->class }}">
                            {{ __($transaction->string_status->value) }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="dashboard-list-right">
                <h4 class="main-money text--base">{{ get_amount($transaction->request_amount) }}{{ $transaction->request_currency }}</h4>
                <h6 class="exchange-money">{{ get_amount($transaction->total_payable) }}{{ $transaction->payment_currency }}</h6>
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
                            <span>{{ __("Amount") }}</span>
                        </div>
                    </div>
                </div>
                <div class="preview-list-right">
                    <span class="text--danger">{{ get_amount($transaction->request_amount) }}{{ $transaction->request_currency }}</span>
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
                    <span>{{ get_amount($transaction->total_charge) }}{{ $transaction->payment_currency }}</span>
                </div>
            </div>
            <div class="preview-list-item">
                <div class="preview-list-left">
                    <div class="preview-list-user-wrapper">
                        <div class="preview-list-user-icon">
                            <i class="lab la-get-pocket"></i>
                        </div>
                        <div class="preview-list-user-content">
                            <span>{{ __("Will Get") }}</span>
                        </div>
                    </div>
                </div>
                <div class="preview-list-right">
                    <span>{{ get_amount($transaction->receive_amount) }}{{ $transaction->request_currency }}</span>
                </div>
            </div>
            <div class="preview-list-item">
                <div class="preview-list-left">
                    <div class="preview-list-user-wrapper">
                        <div class="preview-list-user-icon">
                            <i class="las la-receipt"></i>
                        </div>
                        <div class="preview-list-user-content">
                            <span>{{ __("Total Amount") }}</span>
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
                            <span>{{__("Status")}}</span>
                        </div>
                    </div>
                </div>
                <div class="preview-list-right">
                    <span class="{{ $transaction->string_status->class }}">
                        {{ __($transaction->string_status->value) }}
                    </span>
                </div>
            </div>
            @if ($transaction->gateway_currency->gateway->isTatum($transaction->gateway_currency->gateway) && $transaction->status == payment_gateway_const()::STATUSWAITING)
                <div class="col-12">
                    <form action="{{ setRoute('user.add.money.payment.crypto.confirm', $transaction->trx_id) }}" method="POST">
                        @csrf
                        @php
                            $input_fields = $transaction->details->payment_info->requirements ?? [];
                        @endphp
                        @foreach ($input_fields as $input)
                            <div class="">
                                <h4 class="mb-0">{{ $input->label }}</h4>
                                <input type="text" class="form-control" name="{{ $input->name }}" placeholder="{{ $input->placeholder ?? "" }}">
                            </div>
                        @endforeach
                        <div class="text-end">
                            <button type="submit" class="btn--base my-2">{{ __("Process") }}</button>
                        </div>
                    </form>
                </div>
            @endif
            @if ($transaction->status == payment_gateway_const()::STATUSREJECTED)
                <div class="col-12">
                    <div class="d-flex justify-content-between">
                        <h4>{{ __("Reject Reason :") }}</h4>
                        <h4>{{ $transaction->reject_reason }}</h4>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endisset
