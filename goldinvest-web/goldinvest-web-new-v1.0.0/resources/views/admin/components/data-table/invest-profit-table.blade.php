<table class="custom-table transaction-search-table">
    <thead>
        <tr>
            <th></th>
            <th>{{ __("Full Name") }}</th>
            <th>{{ __("Email") }}</th>
            <th>{{ __("Username") }}</th>
            <th>{{ __("Investment Plan") }}</th>
            <th>{{ __("Invest Amount") }}</th>
            <th>{{ __("Profit Amount") }}</th>
            <th>{{ __("Created At") }}</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($profits ?? []  as $key => $item)
            <tr>
                <td>
                    <ul class="user-list">
                        <li><img src="{{ get_image($item->user->image ?? "","user-profile") }}" alt="product"></li>
                    </ul>
                </td>
                <td>{{ $item->user->firstname ?? 'N/A' }} {{ $item->user->lastname ?? '' }}</td>
                <td>{{ $item->user->email ?? 'N/A' }}</td>
                <td>{{ $item->user->username ?? 'N/A' }}</td>
                <td>{{ $item->invest->investPlan->data->language->$lang->name ?? 'N/A' }}</td>
                <td>{{ isset($item->invest->invest_amount) ? get_amount($item->invest->invest_amount) : 'N/A' }} {{ $default_currency->symbol ?? '' }}</td>
                <td>{{ isset($item->profit_amount) ? get_amount($item->profit_amount) : 'N/A' }} {{ $default_currency->symbol ?? '' }}</td>
                <td>{{ $item->created_at ? $item->created_at->format('d-m-y h:i:s A') : 'N/A' }}</td>
            </tr>
        @empty
            @include('admin.components.alerts.empty',['colspan' => 9])
        @endforelse
    </tbody>
</table>
