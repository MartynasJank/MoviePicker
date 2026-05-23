import Swiper from 'swiper';
import { Navigation } from 'swiper/modules';

$(document).ready(function () {

    const sharedConfig = {
        slidesPerView: 1.4,
        centeredSlides: true,
        spaceBetween: 12,
        loop: true,
        modules: [Navigation],
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
        breakpoints: {
            600:  { slidesPerView: 2, spaceBetween: 12, centeredSlides: false },
            1024: { slidesPerView: 4, spaceBetween: 16 },
        },
    };

    if ($('.swiper-trending-movies').length) {
        new Swiper('.swiper-trending-movies', sharedConfig);
        let tvSwiper = null;

        $('#trend-movies, #trend-tv').on('click', function () {
            const isTv = this.id === 'trend-tv';
            $('#trend-movies, #trend-tv').toggleClass('active', false).addClass('text-gray-400');
            $(this).addClass('active').removeClass('text-gray-400');
            $('#trending-movies').toggleClass('hidden', isTv);
            $('#trending-tv').toggleClass('hidden', !isTv);
            if (isTv && !tvSwiper) {
                tvSwiper = new Swiper('.swiper-trending-tv', sharedConfig);
            }
        });
    }

    if ($('.swiper-similar').length) {
        new Swiper('.swiper-similar', sharedConfig);
    }

    if ($('.swiper-multiple').length) {
        new Swiper('.swiper-multiple', sharedConfig);
    }
});
