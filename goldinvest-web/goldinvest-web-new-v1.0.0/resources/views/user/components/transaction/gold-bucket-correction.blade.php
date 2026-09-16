@isset ($transaction)
    <div class="dashboard-list-wrapper">
        <div class="dashboard-list-item-wrapper">
            <div class="dashboard-list-item received">
                <div class="dashboard-list-left">
                    <div class="dashboard-list-user-wrapper">
                        <div class="dashboard-list-user-icon">
                            <i class="las la-exchange-alt"></i>
                        </div>
                        <div class="dashboard-list-user-content">
                            <h4 class="title">{{ __("Balance Reclassified") }}</h4>
                            <span class="{{ $transaction->string_status->class }}">
                                {{ __($transaction->string_status->value) }}
                            </span>
                        </div>
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
                            <span>{{ __("Moved to Profit Balance") }}</span>
                        </div>
                    </div>
                </div>
                <div class="preview-list-right">
                    <span class="text--info">{{ get_amount($transaction->request_amount) }} {{ $transaction->request_currency }}</span>
                </div>
            </div>
            <div class="preview-list-item">
                <div class="preview-list-left">
                    <div class="preview-list-user-wrapper">
                        <div class="preview-list-user-icon">
                            <i class="las la-file-invoice"></i>
                        </div>
                        <div class="preview-list-user-content">
                            <span>{{ __("From Deal") }}</span>
                        </div>
                    </div>
                </div>
                <div class="preview-list-right">
                    <span>{{ $transaction->details->lot_code ?? '-' }}</span>
                </div>
            </div>
            <div class="preview-list-item">
                <div class="preview-list-left">
                    <div class="preview-list-user-wrapper">
                        <div class="preview-list-user-icon">
                            <i class="las la-receipt"></i>
                        </div>
                        <div class="preview-list-user-content">
                            <span>{{ __("Transaction ID") }}</span>
                        </div>
                    </div>
                </div>
                <div class="preview-list-right">
                    <span>{{ $transaction->trx_id }}</span>
                </div>
            </div>
            <div class="preview-list-item">
                <div class="preview-list-left">
                    <div class="preview-list-user-wrapper">
                        <div class="preview-list-user-icon">
                            <i class="las la-calendar"></i>
                        </div>
                        <div class="preview-list-user-content">
                            <span>{{ __("Date") }}</span>
                        </div>
                    </div>
                </div>
                <div class="preview-list-right">
                    <span>{{ $transaction->created_at->format('d M Y, h:i A') }}</span>
                </div>
            </div>
        </div>
    </div>
@endisset
