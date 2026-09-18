<table class="head">
    <tr>
        <td style="width:60%">
            @if (!empty($branding['logo']))
                <img src="{{ $branding['logo'] }}" style="max-height:38px; max-width:180px;" alt="">
                <div class="company" style="margin-top:4px;">{{ $branding['name'] }}</div>
            @else
                <div class="company">{{ $branding['name'] }}</div>
            @endif
        </td>
        <td style="width:40%">
            <div class="doctype">{{ $title }}</div>
            <div class="muted" style="text-align:right; margin-top:3px;">
                {{ $number }}<br>
                Issued {{ now()->format('d M Y, H:i') }}
            </div>
        </td>
    </tr>
</table>
