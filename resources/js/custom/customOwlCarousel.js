require('../../../node_modules/owl.carousel');

$(document).ready(function(){
    $('.owl-similar').owlCarousel({
        loop: true,
        margin: 10,
        dots: false,
        nav: false,
        responsiveClass: true,
        autoplay: true,
        autoplayTimeout: 7000,
        autoplayHoverPause: true,
        responsive: {
            0: {
                items: 1,
                margin: 10,
                stagePadding: 50
            },
            600: {
                items: 2,
                margin: 10,
                stagePadding: 50
            },
            1000: {
                items: 4
            }
        }
    });

    // Owl tredning carousel in home page
    $('.owl-trending').owlCarousel({
        loop: true,
        margin: 10,
        dots: false,
        nav: false,
        responsiveClass: true,
        autoHeight: false,
        lazyLoad: false,
        autoplay: true,
        autoplayTimeout: 7000,
        autoplayHoverPause: true,
        responsive: {
            0: {
                items: 1,
                margin: 10,
                stagePadding: 50
            },
            600: {
                items: 2,
                margin: 10,
                stagePadding: 50
            },
            1000: {
                items: 4
            }
        }
    });

    // Owl multiple carousel in multiple page
    $('.owl-multiple').owlCarousel({
        loop: false,
        margin: 15,
        dots: false,
        nav: false,
        responsiveClass: true,
        autoHeight: false,
        lazyLoad: false,
        autoplay: false,
        responsive: {
            0: {
                loop: true,
                items: 1,
                margin: 10,
                stagePadding: 20
            },
            600: {
                loop: true,
                items: 2,
                margin: 10,
                stagePadding: 20
            },
            1000: {
                items: 4,
                stagePadding: 50,
            }
        }
    });
});
