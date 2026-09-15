
@extends('frontend.layouts.master')

@section('content')

<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start Privacy
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

<section class="blog-section style-01 ptb-120">
    <div class="container">
        <div class="row justify-content-center mb-30-none">
            <div class="col-xl-12 col-lg-12 mb-30">
                <div class="row justify-content-center mb-30-none">
                    <div class="col-xl-12 mb-30">
                        <div class="blog-item">

                            <div class="blog-content">
                                <h2 class="title mb-30 text-center"><a href="javascript:void(0)">{{ @$useful_link->title->language->$lang->title ?? @$useful_link->title->language->$default->title }}</a></h2>
                                @php
                                echo @$useful_link->content->language->$lang->content ?? @$useful_link->content->language->$default->content
                               @endphp
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End Privacy
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
@endsection

