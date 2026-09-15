@extends('frontend.layouts.master')
@section('content')

<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start service section
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="service-section pt-120">
    @if(isset($service->value))
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-12 text-center">
                <div class="section-header">
                    <span class="section-sub-titel"><i class="{{ @$service->value->section_icon }}"></i> {{ @$service->value->language->$lang->section_title ?? @$service->value->language->$default->section_title }}</span>
                    <h2 class="section-title"> {{ @$service->value->language->$lang->heading ?? @$service->value->language->$default->heading }}</h2>
                    <p> {{ @$service->value->language->$lang->sub_heading ?? @$service->value->language->$default->sub_heading }}</p>
                </div>
            </div>
        </div>
        <div class="row mb-30-none">
            @forelse($service->value->items ?? [] as $key => $item)
            <div class="col-lg-6 col-md-6 mb-30">
                <div class="service-item">
                    <span class="icon"><i class="{{ @$item->icon }}"></i></span>
                    <div class="service-content">
                        <h4 class="title">{{ @$item->language->$lang->title ?? @$item->language->$default->title }}</h4>
                        <p>{{ @$item->language->$lang->description ?? @$item->language->$default->description }}</p>
                        <div class="service-bg bg_img" data-background="{{ get_image(@$item->image, 'site-section') }}"></div>
                    </div>
                </div>
            </div>
            @empty
                @include('admin.components.alerts.empty',['colspan' => 7])
            @endforelse
        </div>
    </div>
    @endif
</section>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End service section
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
