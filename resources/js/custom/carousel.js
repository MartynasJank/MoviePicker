import Swiper from 'swiper';
import { Autoplay, Mousewheel } from 'swiper/modules';

$(document).ready(function () {

    const sharedConfig = {
        modules: [Mousewheel],
        slidesPerView: 1.4,
        centeredSlides: true,
        spaceBetween: 12,
        loop: true,
        mousewheel: { forceToAxis: true },
        breakpoints: {
            600:  { slidesPerView: 2, spaceBetween: 12, centeredSlides: false },
            1024: { slidesPerView: 4, spaceBetween: 16 },
        },
    };

    if ($('.swiper-trending').length) {
        new Swiper('.swiper-trending', {
            ...sharedConfig,
            modules: [Autoplay, Mousewheel],
            autoplay: { delay: 7000, disableOnInteraction: false, pauseOnMouseEnter: true },
        });
    }

    if ($('.swiper-similar').length) {
        new Swiper('.swiper-similar', {
            ...sharedConfig,
            modules: [Autoplay, Mousewheel],
            autoplay: { delay: 7000, disableOnInteraction: false, pauseOnMouseEnter: true },
        });
    }

    if ($('.swiper-multiple').length) {
        new Swiper('.swiper-multiple', sharedConfig);
    }
});
