@extends('frontend.layouts.master')
@section('content')
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Blog
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="blog-section pt-120">
    <div class="container">
        <div class="row mb-30-none">
            <div class="col-xl-8 col-lg-7 col-md-12 mb-30">
                <div class="row justify-content-center mb-30-none">
                    <div class="col-xl-12 mb-30">
                        <div class="blog-item-details">
                            <div class="blog-thumb">
                                <img src="{{ get_image(@$announcement->data->image, 'site-section') }}" alt="blog">
                            </div>
                            <div class="blog-content">
                                <h3 class="title">{{ @$announcement->data->language->$lang->title ?? @$announcement->data->language->$default->title }}</h3>
                                @php
                                    echo @$announcement->data->language->$lang->description ?? @$announcement->data->language->$default->description;
                                @endphp
                                <div class="blog-tag-wrapper">
                                    <span>{{ __("Tags") }}:</span>
                                    <ul class="blog-footer-tag">
                                        @forelse ($announcement->data->language->$lang->tags ?? $announcement->data->language->$default->tags as $tag)
                                            <li><a href="javascript:void(0)">{{ @$tag }}</a></li>
                                        @empty
                                            @include('admin.components.alerts.empty',['colspan' => 7])
                                        @endforelse
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-5 col-md-12 mb-30">
                <div class="blog-sidebar">
                    <div class="widget-box mb-30">
                        <h4 class="widget-title">{{ __("Recent Posts") }}</h4>
                        <div class="popular-widget-box">
                            @forelse ($latest_announcements ?? [] as $key => $announcement )
                                <div class="single-popular-item d-flex flex-wrap align-items-center">
                                    <div class="popular-item-thumb">
                                        <a href="{{setRoute('frontend.journal.details',[@$announcement->id,@$announcement->slug])}}"><img src="{{ get_image(@$announcement->data->image, 'site-section') }}" alt="blog"></a>
                                    </div>
                                    <div class="popular-item-content">
                                        <span class="date">{{ (new DateTime(@$announcement->created_at))->format('d M,Y') }}</span>
                                        <h5 class="title"><a href="{{setRoute('frontend.journal.details',[@$announcement->id,@$announcement->slug])}}">{{textsLength(strip_tags(@$announcement->data->language->$lang->description ?? @$announcement->data->language->$default->description ,60))}}</a></h5>
                                    </div>
                                </div>
                            @empty
                                @include('admin.components.alerts.empty',['colspan' => 7])
                            @endforelse

                        </div>
                    </div>
                    <div class="widget-box">
                        <h4 class="widget-title">{{ __("Tags") }}</h4>
                        <div class="tag-widget-box">
                            <ul class="tag-list">
                                @forelse ($all_tags as $tag)
                                    <li><a href="javascript:void(0)">{{ @$tag }}</a></li>
                                @empty
                                    @include('admin.components.alerts.empty',['colspan' => 7])
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Blog
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->


<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start app section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="merchant-app-section ptb-120">
    @include('frontend.sections.app-section')
</section>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End app section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->


<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start brand section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
@include('frontend.sections.brand-section')
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End brand section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
@endsection
