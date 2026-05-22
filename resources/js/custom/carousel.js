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

    if ($('.swiper-trending-day').length) {
        new Swiper('.swiper-trending-day', sharedConfig);
        let weekSwiper = null;

        $('#trend-day, #trend-week').on('click', function () {
            const isWeek = this.id === 'trend-week';
            $('#trend-day, #trend-week').toggleClass('active', false).addClass('text-gray-400');
            $(this).addClass('active').removeClass('text-gray-400');
            $('#trend-label').text(isWeek ? 'This Week' : 'Today');
            $('#trending-day').toggleClass('hidden', isWeek);
            $('#trending-week').toggleClass('hidden', !isWeek);

            if (isWeek && !weekSwiper) {
                weekSwiper = new Swiper('.swiper-trending-week', sharedConfig);
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
