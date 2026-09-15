<!-- favicon -->
<link rel="shortcut icon" href="{{ get_fav($basic_settings) }}" type="image/x-icon">
<!-- fontawesome css link -->
<link rel="stylesheet" href="{{ asset('public/frontend/assets/css/fontawesome-all.min.css') }}">
<!-- line-awesome css -->
<link rel="stylesheet" href="{{ asset('public/frontend/assets/css/line-awesome.min.css') }}">
<!-- bootstrap css link -->
<link rel="stylesheet" href="{{ asset('public/frontend/assets/css/bootstrap.min.css') }}">
<!-- swipper css link -->
<link rel="stylesheet" href="{{ asset('public/frontend/assets/css/swiper.min.css') }}">
<!-- animate css link -->
<link rel="stylesheet" href="{{ asset('public/frontend/assets/css/animate.css') }}">
<!-- odometer css -->
<link rel="stylesheet" href="{{ asset('public/frontend/assets/css/odometer.css') }}">
<!-- lightcase css -->
<link rel="stylesheet" href="{{ asset('public/frontend/assets/css/lightcase.css') }}">
<!-- select2 css -->
<link rel="stylesheet" href="{{ asset('public/frontend/assets/css/select2.min.css') }}">
<!-- Popup  -->
<link rel="stylesheet" href="{{ asset('public/backend/library/popup/magnific-popup.css') }}">
<!-- main style css link -->
<link rel="stylesheet" href="{{ asset('public/frontend/assets/css/style.css') }}">

<style>
    :root {
        --primary-color: {{ $basic_settings->base_color }};
        --secondary-color: {{ $basic_settings->secondary_color }};
    }
</style>
