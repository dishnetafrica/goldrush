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
                        <h4 class="title">{{ __("Capital Return") }}</h4>
                        <span class="{{ $transaction->string_status->class }}">
                            {{ __($transaction->string_status->value) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="preview-list-wrapper">
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
                    <span class="text--danger">{{ get_amount($transaction->request_amount) }}{{ $transaction->request_currency }}</span>
                </div>
            </div>
            <div class="preview-list-item">
                <div class="preview-list-left">
                    <div class="preview-list-user-wrapper">
                        <div class="preview-list-user-icon">
                            <i class="las la-receipt"></i>
                        </div>
                        <div class="preview-list-user-content">
                            <span>{{__("Receive Amount")}}</span>
                        </div>
                    </div>
                </div>
                <div class="preview-list-right">
                    <span class="text--warning">{{ get_amount($transaction->receive_amount) }}{{ $transaction->request_currency }}</span>
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
