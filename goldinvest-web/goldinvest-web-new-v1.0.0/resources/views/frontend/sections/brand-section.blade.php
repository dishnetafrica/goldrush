<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start brand section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
@if(isset($brand->value))
<div class="brand-section bg_img" data-background="{{ get_image(@$brand->value->image, 'site-section') }}">
    <div class="brand-slider">
        <div class="swiper-wrapper">
            @forelse ($brand->value->items ?? [] as $key => $item )
            <div class="swiper-slide">
                <div class="brand-item">
                    <img src="{{ get_image(@$item->image, 'site-section') }}" alt="brand">
                </div>
            </div>
            @empty
                @include('admin.components.alerts.empty',['colspan' => 7])
            @endforelse
        </div>
    </div>
</div>
@endif
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End brand section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
