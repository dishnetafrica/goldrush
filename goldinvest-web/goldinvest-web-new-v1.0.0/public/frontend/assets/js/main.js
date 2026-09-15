(function ($) {
    "user strict";

    // preloader
    $(window).on('load', function () {
        $('.preloader').fadeOut(1000);
        var img = $('.bg_img');
        img.css('background-image', function () {
            var bg = ('url(' + $(this).data('background') + ')');
            return bg;
        });
    });


    //Create Background Image
    (function background() {
        let img = $('.bg_img');
        img.css('background-image', function () {
            var bg = ('url(' + $(this).data('background') + ')');
            return bg;
        });
    })();


    // header-fixed
    var fixed_top = $(".header-section");
    $(window).on("scroll", function () {
        if ($(window).scrollTop() > 100) {
            fixed_top.addClass("animated fadeInDown header-fixed");
        }
        else {
            fixed_top.removeClass("animated fadeInDown header-fixed");
        }
    });

    // navbar-click
    $(".navbar li a").on("click", function () {
        var element = $(this).parent("li");
        if (element.hasClass("show")) {
            element.removeClass("show");
            element.children("ul").slideUp(500);
        }
        else {
            element.siblings("li").removeClass('show');
            element.addClass("show");
            element.siblings("li").find("ul").slideUp(500);
            element.children('ul').slideDown(500);
        }
    });

    //Odometer
    if ($(".statistics-item").length) {
        $(".statistics-item").each(function () {
            $(this).isInViewport(function (status) {
                if (status === "entered") {
                    for (var i = 0; i < document.querySelectorAll(".odometer").length; i++) {
                        var el = document.querySelectorAll('.odometer')[i];
                        el.innerHTML = el.getAttribute("data-odometer-final");
                    }
                }
            });
        });
    }

    // lightcase
    $(window).on('load', function () {
        $("a[data-rel^=lightcase]").lightcase();
    })

    // scroll-to-top
    var ScrollTop = $(".scrollToTop");
    $(window).on('scroll', function () {
        if ($(this).scrollTop() < 100) {
            ScrollTop.removeClass("active");
        } else {
            ScrollTop.addClass("active");
        }
    });

    // faq
    $('.faq-wrapper .faq-title').on('click', function (e) {
        var element = $(this).parent('.faq-item');
        if (element.hasClass('open')) {
            element.removeClass('open');
            element.find('.faq-content').removeClass('open');
            element.find('.faq-content').slideUp(300, "swing");
        } else {
            element.addClass('open');
            element.children('.faq-content').slideDown(300, "swing");
            element.siblings('.faq-item').children('.faq-content').slideUp(300, "swing");
            element.siblings('.faq-item').removeClass('open');
            element.siblings('.faq-item').find('.faq-title').removeClass('open');
            element.siblings('.taq-item').find('.faq-content').slideUp(300, "swing");
        }
    });


    // slider
    var swiper = new Swiper('.testimonial-slider', {
        slidesPerView: 2,
        spaceBetween: 30,
        loop: true,
        centeredSlides: true,
        navigation: {
            nextEl: '.slider-next',
            prevEl: '.slider-prev',
        },
        autoplay: {
            speeds: 1000,
            delay: 2000,
        },
        speed: 1000,
        breakpoints: {
            991: {
                slidesPerView: 2,
            },
            767: {
                slidesPerView: 1,
            },
            575: {
                slidesPerView: 1,
            },
        }
    });

    // brand slider
    var swiper = new Swiper('.brand-slider', {
        slidesPerView: 8,
        spaceBetween: 0,
        loop: true,
        autoplay: {
            speeds: 1000,
            delay: 2000,
        },
        speed: 1000,
        breakpoints: {
            1399: {
                slidesPerView: 4,
            },
            1199: {
                slidesPerView: 3,
            },
            991: {
                slidesPerView: 3,
            },
            767: {
                slidesPerView: 2,
            },
        }
    });

    // sidebar
    $(".sidebar-menu-item > a").on("click", function () {
        var element = $(this).parent("li");
        if (element.hasClass("active")) {
            element.removeClass("active");
            element.children("ul").slideUp(500);
        }
        else {
            element.siblings("li").removeClass('active');
            element.addClass("active");
            element.siblings("li").find("ul").slideUp(500);
            element.children('ul').slideDown(500);
        }
    });

    //sidebar Menu
    $(document).on('click', '.sidebar-collapse-icon', function () {
        $('.page-container').toggleClass('show');
    });

    // sidebar sub
    $(".has-sub > a").on("click", function () {
        var element = $(this).parent("li");
        if (element.hasClass("active")) {
            element.removeClass("active");
            element.children("ul").slideUp(500);
        }
        else {
            element.siblings("li").removeClass('active');
            element.addClass("active");
            element.siblings("li").find("ul").slideUp(500);
            element.children('ul').slideDown(500);
        }
    });

    // Mobile Menu
    $('.sidebar-mobile-menu').on('click', function () {
        $('.sidebar-main-menu').slideToggle();
    });

    // img-up
    $('.imgUp').click(function () {
        upload();
    });
    function upload() {
        $(".upload").change(function () {
            readURL(this);
        });
    }
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader(); reader.onload = function (e) {
                var preview = $(input).parents('.profile-thumb-area').find('.image-preview');
                $(preview).css('background-image', 'url(' + e.target.result + ')');
                $(preview).hide();
                $(preview).fadeIn(650);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    $(document).ready(function () {
        var AFFIX_TOP_LIMIT = 300;
        var AFFIX_OFFSET = 110;

        var $menu = $("#menu"),
            $btn = $("#menu-toggle");

        $("#menu-toggle").on("click", function () {
            $menu.toggleClass("open");
            return false;
        });


        $(".docs-nav").each(function () {
            var $affixNav = $(this),
                $container = $affixNav.parent(),
                affixNavfixed = false,
                originalClassName = this.className,
                current = null,
                $links = $affixNav.find("a");

            function getClosestHeader(top) {
                var last = $links.first();

                if (top < AFFIX_TOP_LIMIT) {
                    return last;
                }

                for (var i = 0; i < $links.length; i++) {
                    var $link = $links.eq(i),
                        href = $link.attr("href");

                    if (href.charAt(0) === "#" && href.length > 1) {
                        var $anchor = $(href).first();

                        if ($anchor.length > 0) {
                            var offset = $anchor.offset();

                            if (top < offset.top - AFFIX_OFFSET) {
                                return last;
                            }

                            last = $link;
                        }
                    }
                }
                return last;
            }


            $(window).on("scroll", function (evt) {
                var top = window.scrollY,
                    height = $affixNav.outerHeight(),
                    max_bottom = $container.offset().top + $container.outerHeight(),
                    bottom = top + height + AFFIX_OFFSET;

                if (affixNavfixed) {
                    if (top <= AFFIX_TOP_LIMIT) {
                        $affixNav.removeClass("fixed");
                        $affixNav.css("top", 0);
                        affixNavfixed = false;
                    } else if (bottom > max_bottom) {
                        $affixNav.css("top", (max_bottom - height) - top);
                    } else {
                        $affixNav.css("top", AFFIX_OFFSET);
                    }
                } else if (top > AFFIX_TOP_LIMIT) {
                    $affixNav.addClass("fixed");
                    affixNavfixed = true;
                }

                var $current = getClosestHeader(top);

                if (current !== $current) {
                    $affixNav.find(".active").removeClass("active");
                    $current.addClass("active");
                    current = $current;
                }
            });
        });
    });


    //Profile Upload
    function proPicURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                var preview = $(input).parents('.preview-thumb').find('.profilePicPreview');
                $(preview).css('background-image', 'url(' + e.target.result + ')');
                $(preview).addClass('has-image');
                $(preview).hide();
                $(preview).fadeIn(650);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    $(".profilePicUpload").on('change', function () {
        proPicURL(this);
    });

    $(".remove-image").on('click', function () {
        $(".profilePicPreview").css('background-image', 'none');
        $(".profilePicPreview").removeClass('has-image');
    });

    // notification
    $(".notify-btn-area").click(function () {
        $(".notification-wrapper").slideToggle();
    });



    $('.header-mobile-search-btn').on('click', function (e) {
        e.preventDefault();
        if ($('.header-mobile-search-form-area').hasClass('active')) {
            $('.header-mobile-search-form-area').removeClass('active');
            $('.body-overlay').removeClass('active');
        } else {
            $('.header-mobile-search-form-area').addClass('active');
            $('.body-overlay').addClass('active');
            $('.header-section').addClass('active');
        }
    });
    $('#body-overlay').on('click', function (e) {
        e.preventDefault();
        $('.header-mobile-search-form-area').removeClass('active');
        $('.body-overlay').removeClass('active');
    });

    // active menu JS
    function splitSlash(data) {
        return data.split('/').pop();
    }
    function splitQuestion(data) {
        return data.split('?').shift().trim();
    }
    var pageNavLis = $('.sidebar-menu a');
    var dividePath = splitSlash(window.location.href);
    var divideGetData = splitQuestion(dividePath);
    var currentPageUrl = divideGetData;

    // find current sidevar element
    $.each(pageNavLis, function (index, item) {
        var anchoreTag = $(item);
        var anchoreTagHref = $(item).attr('href');
        var index = anchoreTagHref.indexOf('/');
        var getUri = "";
        if (index != -1) {
            // split with /
            getUri = splitSlash(anchoreTagHref);
            getUri = splitQuestion(getUri);
        } else {
            getUri = splitQuestion(anchoreTagHref);
        }
        if (getUri == currentPageUrl) {
            var thisElementParent = anchoreTag.parents('.sidebar-menu-item');
            (anchoreTag.hasClass('nav-link') == true) ? anchoreTag.addClass('active') : thisElementParent.addClass('active');
            (anchoreTag.parents('.sidebar-dropdown')) ? anchoreTag.parents('.sidebar-dropdown').addClass('active') : '';
            (thisElementParent.find('.sidebar-submenu')) ? thisElementParent.find('.sidebar-submenu').slideDown("slow") : '';
            return false;
        }
    });

    //sidebar Menu
    $('.sidebar-menu-bar').on('click', function (e) {
        e.preventDefault();
        if ($('.sidebar, .navbar-wrapper, .body-wrapper').hasClass('active')) {
            $('.sidebar, .navbar-wrapper, .body-wrapper').removeClass('active');
            $('.body-overlay').removeClass('active');
        } else {
            $('.sidebar, .navbar-wrapper, .body-wrapper').addClass('active');
            $('.body-overlay').addClass('active');
        }
    });
    $('#body-overlay').on('click', function (e) {
        e.preventDefault();
        $('.sidebar, .navbar-wrapper, .body-wrapper').removeClass('active');
        $('.body-overlay').removeClass('active');
    });

    // dashboard-list
    $('.dashboard-list-item').on('click', function (e) {
        if (e.target.classList.contains("oder-action") || e.target.parentElement.classList.contains("oder-action")) {
            return false;
        }
        var element = $(this).parent('.dashboard-list-item-wrapper');
        if (element.hasClass('show')) {
            element.removeClass('show');
            element.find('.preview-list-wrapper').removeClass('show');
            element.find('.preview-list-wrapper').slideUp(300, "swing");
        } else {
            element.addClass('show');
            element.children('.preview-list-wrapper').slideDown(300, "swing");
            element.siblings('.dashboard-list-item-wrapper').children('.preview-list-wrapper').slideUp(300, "swing");
            element.siblings('.dashboard-list-item-wrapper').removeClass('show');
            element.siblings('.dashboard-list-item-wrapper').find('.dashboard-list-item').removeClass('show');
            element.siblings('.dashboard-list-item-wrapper').find('.preview-list-wrapper').slideUp(300, "swing");
        }
    });

    //sidebar Menu
    $(document).on('click', '.push-icon', function () {
        $('.push-wrapper').toggleClass('active');
        $('.body-overlay').addClass('show');
    });
    $('#body-overlay').on('click', function (e) {
        e.preventDefault();
        $('.push-wrapper').removeClass('active');
        $('.body-overlay').removeClass('show');
    });


    //info-btn
    $(document).on('click', '.info-btn', function () {
        $('.support-profile-wrapper').addClass('active');
    });
    $(document).on('click', '.chat-cross-btn', function () {
        $('.support-profile-wrapper').removeClass('active');
    });


    // custom Select
    $('.custom-select').on('click', function (e) {
        e.preventDefault();
        $(".custom-select-wrapper").removeClass("active");
        if ($(this).siblings(".custom-select-wrapper").hasClass('active')) {
            $(this).siblings(".custom-select-wrapper").removeClass('active');
            $('.body-overlay').removeClass('active');
        } else {
            $(this).siblings(".custom-select-wrapper").addClass('active');
            $('.body-overlay').addClass('active');
        }
    });
    $('#body-overlay').on('click', function (e) {
        e.preventDefault();
        $('.custom-select-wrapper').removeClass('active');
        $('.body-overlay').removeClass('active');
    });

    $('.custom-option').on('click', function () {
        $(this).parent().find(".custom-option").removeClass("active");
        $(this).addClass('active');
        var flag = $(this).find("img").attr("src");
        var currencyCode = $(this).find(".custom-currency").text();
        $(this).parents(".custom-select-wrapper").siblings(".custom-select").find(".custom-select-inner").find(".custom-currency").text(currencyCode);
        $(this).parents(".custom-select-wrapper").siblings(".custom-select").find(".custom-select-inner").find("img").attr("src", flag);
        $(this).parents(".custom-select-wrapper").removeClass("active");
        $('.body-overlay').removeClass('active');
    });

    $('.custom-option').on('click', function () {
        $(this).parent().find(".custom-option").removeClass("active");
        $(this).addClass('active');
        var method = $(this).find(".title").text();
        $(this).parents(".custom-select-wrapper").siblings(".custom-select").find(".custom-select-inner").find(".method").text(method);
        $(this).parents(".custom-select-wrapper").removeClass("active");
        $('.body-overlay').removeClass('active');
    });

    // start select2
    $(document).ready(function () {
        $('.select2').select2();
    });
    $('#modalSelect2').select2({
        dropdownParent: $('#myModal')
    });
    // end select2


    // product + - start here
    $(function () {
        var CartPlusMinus = $('.product-plus-minus');
        CartPlusMinus.prepend('<div class="dec qtybutton">-</div>');
        CartPlusMinus.append('<div class="inc qtybutton">+</div>');
        $(".qtybutton").on("click", function () {
            var $button = $(this);
            var oldValue = $button.parent().find("input").val();
            if ($button.text() === "+") {
                var newVal = parseFloat(oldValue) + 1;
            } else {
                // Don't allow decrementing below zero
                if (oldValue > 0) {
                    var newVal = parseFloat(oldValue) - 1;
                } else {
                    newVal = 1;
                }
            }
            $button.parent().find("input").val(newVal);
        });
    });

})(jQuery);
function openAlertModal(URL, target, message, actionBtnText = "Remove", method = "DELETE") {
    if (URL == "" || target == "") {
        return false;
    }

    if (message == "") {
        message = "Are you sure to delete ?";
    }
    var method = `<input type="hidden" name="_method" value="${method}">`;
    openModalByContent(
        {
            content: `<div class="card modal-alert border-0">
                        <div class="card-body">
                            <form method="POST" action="${URL}">
                                <input type="hidden" name="_token" value="${laravelCsrf()}">
                                ${method}
                                <div class="head mb-3">
                                    ${message}
                                    <input type="hidden" name="target" value="${target}">
                                </div>
                                <div class="foot d-flex align-items-center justify-content-between">
                                    <button type="button" class="modal-close btn--base btn-for-modal">Close</button>
                                    <button type="submit" class="alert-submit-btn btn--base btn--danger btn-loading btn-for-modal">${actionBtnText}</button>
                                </div>
                            </form>
                        </div>
                    </div>`,
        },

    );
}
function openModalByContent(data = {
    content: "",
    animation: "mfp-move-horizontal",
    size: "medium",
}) {
    $.magnificPopup.open({
        removalDelay: 500,
        items: {
            src: `<div class="white-popup mfp-with-anim ${data.size ?? "medium"}">${data.content}</div>`, // can be a HTML string, jQuery object, or CSS selector
        },
        callbacks: {
            beforeOpen: function () {
                this.st.mainClass = data.animation ?? "mfp-move-horizontal";
            },
            open: function () {
                var modalCloseBtn = this.contentContainer.find(".modal-close");
                $(modalCloseBtn).click(function () {
                    $.magnificPopup.close();
                });
            },
        },
        midClick: true,
    });
}
function laravelCsrf() {
    return $("head meta[name=csrf-token]").attr("content");
}
/**
 * Function For Get All Country list by AJAX Request
 * @param {HTML DOM} targetElement
 * @param {Error Place Element} errorElement
 * @returns
 */
var allCountries = "";
function getAllCountries(hitUrl, targetElement = $(".country-select"), errorElement = $(".country-select").siblings(".select2")) {
    if (targetElement.length == 0) {
        return false;
    }
    var CSRF = $("meta[name=csrf-token]").attr("content");
    var data = {
        _token: CSRF,
    };
    $.post(hitUrl, data, function () {
        // success
        $(errorElement).removeClass("is-invalid");
        $(targetElement).siblings(".invalid-feedback").remove();
    }).done(function (response) {
        // Place States to States Field
        var options = "<option selected disabled>Select Country</option>";
        var selected_old_data = "";
        if ($(targetElement).attr("data-old") != null) {
            selected_old_data = $(targetElement).attr("data-old");
        }
        $.each(response, function (index, item) {
            options += `<option value="${item.name}" data-id="${item.id}" data-mobile-code="${item.mobile_code}" data-currency-name="${item.currency_name}" data-currency-code="${item.currency_code}" data-currency-symbol="${item.currency_symbol}" ${selected_old_data == item.name ? "selected" : ""}>${item.name}</option>`;
        });

        allCountries = response;

        $(targetElement).html(options);
    }).fail(function (response) {
        var faildMessage = "Something went wrong! Please try again.";
        var faildElement = `<span class="invalid-feedback" role="alert">
                              <strong>${faildMessage}</strong>
                          </span>`;
        $(errorElement).addClass("is-invalid");
        if ($(targetElement).siblings(".invalid-feedback").length != 0) {
            $(targetElement).siblings(".invalid-feedback").text(faildMessage);
        } else {
            errorElement.after(faildElement);
        }
    });
}
// getAllCountries();


/**
 * Function for reload the all countries that already loaded by using getAllCountries() function.
 * @param {string} targetElement
 * @param {string} errorElement
 * @returns
 */
function reloadAllCountries(targetElement, errorElement = $(".country-select").siblings(".select2")) {
    if (allCountries == "" || allCountries == null) {
        // alert();
        return false;
    }
    var options = "<option selected disabled>Select Country</option>";
    var selected_old_data = "";
    if ($(targetElement).attr("data-old") != null) {
        selected_old_data = $(targetElement).attr("data-old");
    }
    $.each(allCountries, function (index, item) {
        options += `<option value="${item.name}" data-id="${item.id}" data-currency-name="${item.currency_name}" data-currency-code="${item.currency_code}" data-currency-symbol="${item.currency_symbol}" ${selected_old_data == item.name ? "selected" : ""}>${item.name}</option>`;
    });
    $(targetElement).html(options);
}
function placePhoneCode(code) {
    if (code != undefined) {
        code = code.replace("+", "");
        code = "+" + code;
        $("input.phone-code").val(code);
        $("div.phone-code").html(code);
    }
}
/**
 * Function for open delete modal with method DELETE
 * @param {string} URL
 * @param {string} target
 * @param {string} message
 * @returns
 */
function openDeleteModal(URL, target, message, actionBtnText = "Remove", method = "DELETE") {
    if (URL == "" || target == "") {
        return false;
    }

    if (message == "") {
        message = "Are you sure to delete ?";
    }
    var method = `<input type="hidden" name="_method" value="${method}">`;
    openModalByContent(
        {
            content: `<div class="card modal-alert border-0">
                        <div class="card-body">
                            <form method="POST" action="${URL}">
                                <input type="hidden" name="_token" value="${laravelCsrf()}">
                                ${method}
                                <div class="head mb-3">
                                    ${message}
                                    <input type="hidden" name="target" value="${target}">
                                </div>
                                <div class="foot d-flex align-items-center justify-content-between">
                                    <button type="button" class="modal-close btn btn--info">Close</button>
                                    <button type="submit" class="alert-submit-btn btn btn--danger btn-loading">${actionBtnText}</button>
                                </div>
                            </form>
                        </div>
                    </div>`,
        },

    );
}

/**
 * Function For Open Modal with CSS Selector Ex: "#modal-popup"
 * @param {String} selector
 * @param {String} animation
 */
function openModalBySelector(selector, animation = "mfp-move-horizontal") {
    $(selector).addClass("white-popup mfp-with-anim");
    if (animation == null) {
        animation = "mfp-zoom-in"
    }
    $.magnificPopup.open({
        removalDelay: 500,
        items: {
            src: $(selector), // can be a HTML string, jQuery object, or CSS selector
            type: 'inline',
        },

        callbacks: {
            beforeOpen: function () {
                this.st.mainClass = animation;
            },
            elementParse: function (item) {
                var modalCloseBtn = $(selector).find(".modal-close");
                $(modalCloseBtn).click(function () {
                    $.magnificPopup.close();
                });
            },
        },
    });
    $.magnificPopup.instance._onFocusIn = function (e) {
        // Do nothing if target element is select2 input
        if ($(e.target).hasClass('select2-search__field')) {
            return true;
        }
        // Else call parent method
        $.magnificPopup.proto._onFocusIn.call(this, e);
    }
}
function copyToClipBoard(copyId) {
    var copyText = document.getElementById(copyId);
    console.log(copyText.value);
    navigator.clipboard.writeText(copyText.value);
    throwMessage('success', ['Text successfully copied.']);
}

// Internal Search Section
var timeOut;
function itemSearch(inputElement, tableElement, URL, minTextLength = 3) {
    $(inputElement).bind("keyup", function () {
        clearTimeout(timeOut);
        timeOut = setTimeout(executeItemSearch, 500, $(this), tableElement, URL, minTextLength);
    });
}

function executeItemSearch(inputElement, tableElement, URL, minTextLength) {
    $(tableElement).parent().find(".search-result-table").remove();
    var searchText = inputElement.val();
    if (searchText.length > minTextLength) {
        // console.log(searchText);
        $(tableElement).addClass("d-none");
        makeSearchItemXmlRequest(searchText, tableElement, URL);
    } else {
        $(tableElement).removeClass("d-none");
    }
}

function makeSearchItemXmlRequest(searchText, tableElement, URL) {
    var data = {
        _token: laravelCsrf(),
        text: searchText,
    };
    $.post(URL, data, function (response) {
        //response
    }).done(function (response) {
        itemSearchResult(response, tableElement);
        // if($(tableElement).siblings(".search-result-table").length > 0) {
        //     $(tableElement).parent().find(".search-result-table").html(response);
        // }else{
        //     $(tableElement).after(`<div class="search-result-table"></div>`);
        //     $(tableElement).parent().find(".search-result-table").html(response);
        // }
    }).fail(function (response) {
        throwMessage('error', ["Something went worng! Please try again."]);
    });
}

function itemSearchResult(response, tableElement) {
    if (response == "") {
        throwMessage('error', ["No data found!"]);
    }
    if ($(tableElement).siblings(".search-result-table").length > 0) {
        $(tableElement).parent().find(".search-result-table").html(response);
    } else {
        $(tableElement).after(`<div class="search-result-table"></div>`);
        $(tableElement).parent().find(".search-result-table").html(response);
    }
}
function sideBarSearch(){
    var menuLinks = $(".sidebar-menu a");
    var filterMenuItem = [];
    $.each(menuLinks,function(index,item) {
        if($(item).attr("href") != "javascript:void(0)") {
          filterMenuItem.push(item);
        }
    });

    $(".sidebar-search-input").keyup(function(){
      sideBarSearchWithInput($(this),filterMenuItem);
    });

    $(".sidebar-search-result").hide();
    $(".sidebar-search-input").focus(function () {
        $(".sidebar-search-result").show();
        $(".body-overlay").addClass("open");
    });
    $(".body-overlay").click(function () {
        $(this).removeClass("open");
        $(".sidebar-search-result").hide();
    });
  }
  sideBarSearch();


function sideBarSearchWithInput(input, navItems) {
    var inputValue = input.val().toLowerCase();
    var searchResult = [];
    $.each(navItems, function (index, item) {
        var title = $(item).find("span").text().toLowerCase();
        var result = title.match(inputValue)
        if (result != null) {
            searchResult.push(item);
        }
    });
    $(".sidebar-search-result").html("");
    $.each(searchResult, function (index, item) {
        var link = $(item).attr("href");
        var title = $(item).find("span").text();
        var iconClass = $(item).find("i").attr("class");
        var singleItem = `<div class="single-item">
              <a href="${link}">
                <i class="${iconClass}"></i>
                <span style="position:inherit">${title}</span>
              </a>
      </div>`
        $(".sidebar-search-result").append(singleItem);
    });
}
$(document).on("keyup",".number-input",function(){
    var pattern = /^[0-9]*\.?[0-9]*$/;
    var value = $(this).val();
    var test = pattern.test(value);
    if(test == false) {
      var rightValue = value;
      if(value.length > 0) {
        for (let index = 0; index < value.length; index++){
          if(!$.isNumeric(rightValue)) {
            rightValue = rightValue.slice(0, -1);
          }
        }
      }
      $(this).val(rightValue);
    }
  });
