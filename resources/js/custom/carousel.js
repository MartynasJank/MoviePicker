import Swiper from 'swiper';
import { Navigation, Autoplay } from 'swiper/modules';

$(document).ready(function () {

    const isMobile = () => window.innerWidth < 600;

    if ($('.swiper-trending').length) {
        new Swiper('.swiper-trending', {
            modules: [Navigation, Autoplay],
            slidesPerView: 1.15,
            spaceBetween: 12,
            loop: true,
            autoplay: { delay: 7000, disableOnInteraction: false, pauseOnMouseEnter: true },
            navigation: {
                prevEl: '.swiper-trending .swiper-button-prev',
                nextEl: '.swiper-trending .swiper-button-next',
            },
            breakpoints: {
                600:  { slidesPerView: 2, spaceBetween: 12 },
                1024: { slidesPerView: 4, spaceBetween: 16 },
            },
        });
    }

    if ($('.swiper-similar').length) {
        new Swiper('.swiper-similar', {
            modules: [Navigation, Autoplay],
            slidesPerView: 1.15,
            spaceBetween: 12,
            loop: true,
            autoplay: { delay: 7000, disableOnInteraction: false, pauseOnMouseEnter: true },
            navigation: {
                prevEl: '.swiper-similar .swiper-button-prev',
                nextEl: '.swiper-similar .swiper-button-next',
            },
            breakpoints: {
                600:  { slidesPerView: 2, spaceBetween: 12 },
                1024: { slidesPerView: 4, spaceBetween: 16 },
            },
        });
    }

    if ($('.swiper-multiple').length) {
        new Swiper('.swiper-multiple', {
            modules: [Navigation],
            slidesPerView: 1.15,
            spaceBetween: 12,
            loop: true,
            navigation: {
                prevEl: '.swiper-multiple .swiper-button-prev',
                nextEl: '.swiper-multiple .swiper-button-next',
            },
            breakpoints: {
                600:  { slidesPerView: 2, spaceBetween: 12 },
                1024: { slidesPerView: 4, spaceBetween: 16 },
            },
        });
    }
});
