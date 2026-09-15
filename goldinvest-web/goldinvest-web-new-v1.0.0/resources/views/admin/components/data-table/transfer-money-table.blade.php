<table class="custom-table transaction-search-table">
    <thead>
        <tr>
            <th></th>
            <th>{{ __("TRX ID") }}</th>
            <th>{{ __("Full Name") }}</th>
            <th>{{ __("Email") }}</th>
            <th>{{ __("Username") }}</th>
            <th>{{ __("Phone") }}</th>
            <th>{{ __("Amount") }}</th>
            <th>{{ __("RECEIVER") }} ({{ __("Mail") }})</th>
            <th>{{ __("Status") }}</th>
            <th>{{ __("Time") }}</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($transactions ?? []  as $key => $item)
            <tr>
                <td>
                    <ul class="user-list">
                        <li><img src="{{ get_image($item->user->image ?? "","user-profile") }}" alt="product"></li>
                    </ul>
                </td>
                <td>{{ $item->trx_id ?? 'N/A' }}</td>
                <td>{{ ($item->user->firstname ?? '') . ' ' . ($item->user->lastname ?? '') }}</td>
                <td>{{ $item->user->email ?? 'N/A' }}</td>
                <td>{{ $item->user->username ?? 'N/A' }}</td>
                <td>{{ $item->user->full_mobile ?? 'N/A' }}</td>
                <td>{{ $item->receive_amount && $item->creator_wallet->currency ? get_amount($item->receive_amount, $item->creator_wallet->currency->code) : 'N/A' }}</td>
                <td><span class="text--info">{{ $item->receiver_info->email ?? 'N/A' }}</span></td>
                <td>
                    <span class="{{ $item->stringStatus->class ?? '' }}">{{ __($item->stringStatus->value) ?? 'N/A' }}</span>
                </td>
                <td>{{ $item->created_at->format('d-m-y h:i:s A') }}</td>
                <td>
                    @if ($item->status == 1)
                        <button type="button" class="btn btn--base bg--success"><i
                                class="las la-check-circle"></i></button>
                    @endif
                    @include('admin.components.link.custom',[
                        'href'          => setRoute('admin.transfer.money.details', $item->id),
                        'class'         => "btn btn--base modal-btn",
                        'icon'          => "las la-expand",
                        'permission'    => "admin.transfer.money.details",
                    ])
                </td>
            </tr>
        @empty
            @include('admin.components.alerts.empty',['colspan' => 11])
        @endforelse
    </tbody>
</table>
