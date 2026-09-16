@isset($logs)
    @forelse ($logs ?? [] as $item)
        @if ($item->type == payment_gateway_const()::TYPETRANSFERMONEY)
            @include('user.components.transaction.money-transfer',[
                'transaction'   => $item,
            ])
        @elseif ($item->type == payment_gateway_const()::TYPEADDMONEY)
            @include('user.components.transaction.add-money',[
                'transaction'   => $item,
            ])
        @elseif ($item->type == payment_gateway_const()::TYPEWITHDRAW)
            @include('user.components.transaction.withdraw',[
                'transaction'   => $item,
            ])
        @elseif ($item->type == payment_gateway_const()::TYPECAPITALRETURN)
            @include('user.components.transaction.capital-return',[
                'transaction'   => $item,
            ])
        @elseif ($item->type == payment_gateway_const()::TYPEADDSUBTRACTBALANCE)
            @include('user.components.transaction.balance-update',[
                'transaction'   => $item,
            ])
        @elseif ($item->type == payment_gateway_const()::TYPEBONUS)
            @include('user.components.transaction.bonus',[
                'transaction'   => $item,
            ])
        @elseif ($item->type == payment_gateway_const()::TYPEREFERBONUS)
            @include('user.components.transaction.refer-bonus',[
                'transaction'   => $item,
            ])
        @elseif ($item->type == \App\GoldTrading\Models\ProfitDistribution::TRX_TYPE)
            @include('user.components.transaction.gold-profit',[
                'transaction'   => $item,
            ])
        @elseif ($item->type == \App\GoldTrading\Models\CapitalAllocation::TRX_LOCK)
            @include('user.components.transaction.gold-capital-lock',[
                'transaction'   => $item,
            ])
        @elseif ($item->type == \App\GoldTrading\Models\CapitalAllocation::TRX_RELEASE)
            @include('user.components.transaction.gold-capital-return',[
                'transaction'   => $item,
            ])
        @elseif ($item->type == \App\Investor\Services\TransactionLedgerMapper::TRX_BUCKET_CORRECTION)
            @include('user.components.transaction.gold-bucket-correction',[
                'transaction'   => $item,
            ])
        @endif
    @empty
        <div class="alert alert-primary text-center">{{ __("No Transactions Found!") }}</div>
    @endforelse
@endisset
