@extends('frontend.layouts.master')
@section('content')

<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start about section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="about-section pt-120">
    @if(isset($about->value))
    <div class="container">
        <div class="row mb-30-none align-items-center">
            <div class="col-xl-6 col-lg-6 col-md-12 mb-30">
                <div class="about-content-wrapper">
                    <div class="about-content-area">
                        <div class="section-header">
                            <span class="section-sub-titel"><i class="{{ @$about->value->section_icon }}"></i> {{ @$about->value->language->$lang->section_title ?? @$about->value->language->$default->section_title }}</span>
                            <h2 class="section-title">{{ @$about->value->language->$lang->heading ?? @$about->value->language->$default->heading }}</h2>
                            <p>{{ @$about->value->language->$lang->description ?? @$about->value->language->$default->description }}</p>
                            <ul class="about-list">
                                @forelse($about->value->items ?? [] as $key => $item)
                                <li><span><i class="{{ @$item->icon }}"></i></span> {{ @$item->language->$lang->title ?? @$item->language->$default->title }}</li>
                                @empty
                                    @include('admin.components.alerts.empty',['colspan' => 7])
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 col-md-12 mb-30">
                <div class="about-thumb2 text-md-center">
                    <img src="{{ get_image(@$about->value->image, 'site-section') }}" alt="about">
                </div>
            </div>
        </div>
    </div>
    @endif
</section>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End about section
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
