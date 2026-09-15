<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start testimonial
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="testimonial-section ptb-120">
    @if(isset($testimonial->value))
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-7">
                <div class="section-header text-center">
                    <span class="section-sub-titel"><i class="{{ @$testimonial->value->section_icon }}"></i> {{ @$testimonial->value->language->$lang->heading ?? @$testimonial->value->language->$default->heading }}</span>
                    <h2 class="section-title">{{ @$testimonial->value->language->$lang->sub_heading ?? @$testimonial->value->language->$default->sub_heading }}</h2>
                </div>
            </div>
        </div>
        <div class="testimonial-slider-wrapper">
            <div class="testimonial-slider">
                <div class="swiper-wrapper">
                    @forelse($testimonial->value->items ?? [] as $key => $item)
                    <div class="swiper-slide">
                        <div class="testimonial-item">
                            <div class="testimonial-user-area">
                                <div class="user-area">
                                    <img src="{{ get_image(@$item->image, 'site-section') }}" alt="user">
                                </div>
                                <div class="title-area">
                                    <h5>{{ @$item->name }}</h5>
                                    <span class="testimonial-date"><i class="las la-history"></i> {{ (new DateTime($item->created_at))->format('d-m-Y') }}</span>
                                </div>
                            </div>
                            <h4 class="testimonial-title">{{  @$item->language->$lang->title ?? @$item->language->$default->title }}</h4>
                            <p>{{ @$item->language->$lang->comment ?? @$item->language->$default->comment }}</p>
                            <div class="testimonial-bottom-wrapper">
                                <ul class="testimonial-icon-list">
                                    @for ($i=0; $i<@$item->star; $i++)
                                    <li><i class="las la-star"></i></li>
                                    @endfor
                                </ul>
                            </div>
                            <div class="service-bg bg_img" data-background="{{ get_image(@$item->bg_image, 'site-section') }}"></div>
                        </div>
                    </div>
                    @empty
                        @include('admin.components.alerts.empty',['colspan' => 7])
                    @endforelse
                </div>
                <div class="slider-nav-area">
                    <div class="slider-prev slider-nav">
                        <i class="las la-arrow-left"></i>
                    </div>
                    <div class="slider-next slider-nav">
                        <i class="las la-arrow-right"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</section>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End testimonial
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->





















