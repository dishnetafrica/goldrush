
<!-- jquery -->
<script src="{{ asset('public/frontend/assets/js/jquery-3.5.1.min.js') }}"></script>
<!-- bootstrap js -->
<script src="{{ asset('public/frontend/assets/js/bootstrap.bundle.min.js') }}"></script>
<!-- swipper js -->
<script src="{{ asset('public/frontend/assets/js/swiper.min.js') }}"></script>
<!-- viewport js -->
<script src="{{ asset('public/frontend/assets/js/viewport.jquery.js') }}"></script>
<!-- odometer js -->
<script src="{{ asset('public/frontend/assets/js/odometer.min.js') }}"></script>
<!-- lightcase js -->
<script src="{{ asset('public/frontend/assets/js/lightcase.js') }}"></script>
<!-- smooth scroll js -->
<script src="{{ asset('public/frontend/assets/js/smoothscroll.min.js') }}"></script>
<!-- select2 js -->
<script src="{{ asset('public/frontend/assets/js/select2.min.js') }}"></script>
<!-- jvectormap -->
<script src="{{ asset('public/frontend/assets/js/jquery-jvectormap-1.2.2.min.js') }}"></script>
<!-- jvectormap world -->
<script src="{{ asset('public/frontend/assets/js/jquery-jvectormap-world-mill-en.js') }}"></script>
<!-- apexcharts js -->
<script src="{{ asset('public/frontend/assets/js/apexcharts.min.js') }}"></script>
<!--  Popup -->
<script src="{{ asset('public/backend/library/popup/jquery.magnific-popup.js') }}"></script>
<!-- particles -->
<script src="{{ asset('public/frontend/assets/js/particles.min.js') }}"></script>
<!-- file holder js -->
<script src="https://cdn.appdevs.net/fileholder/v1.0/js/fileholder-script.js" type="module"></script>
<!-- main -->
<script src="{{ asset('public/frontend/assets/js/main.js') }}"></script>


<script>
    // jvectormap JS
    var colors = ["#0071AF"],
        dataColors = $("#world-map-markers").data("colors");
    function hexToRGB(a, e) {
        var t = parseInt(a.slice(1, 3), 16),
            o = parseInt(a.slice(3, 5), 16),
            n = parseInt(a.slice(5, 7), 16);
        return e ? "rgba(" + t + ", " + o + ", " + n + ", " + e + ")" : "rgb(" + t + ", " + o + ", " + n + ")";
    }
    dataColors && (colors = dataColors.split(",")),
    $("#world-map-markers").vectorMap({
        map: "world_mill_en",
        normalizeFunction: "polynomial",
        hoverOpacity: 0.7,
        hoverColor: !1,
        zoomOnScroll: false,
        regionStyle: { initial: { fill: "#d1dbe5" } },
        markerStyle: { initial: { r: 9, fill: colors[0], "fill-opacity": 0.9, stroke: "#fff", "stroke-width": 7, "stroke-opacity": 0.4 }, hover: { stroke: "#fff", "fill-opacity": 1, "stroke-width": 1.5 } },
        backgroundColor: "transparent",
    });
</script>


@include('admin.partials.notify')
