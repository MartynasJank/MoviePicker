import Swiper from 'swiper';
import { Navigation } from 'swiper/modules';

function addWheelControl(swiper, el) {
    let cooldown = false;
    el.addEventListener('wheel', (e) => {
        if (Math.abs(e.deltaX) < 5) return;
        e.preventDefault();
        if (cooldown) return;
        cooldown = true;
        e.deltaX > 0 ? swiper.slideNext() : swiper.slidePrev();
        setTimeout(() => { cooldown = false; }, 600);
    }, { passive: false });
}

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

    if ($('.swiper-trending').length) {
        const s = new Swiper('.swiper-trending', sharedConfig);
        addWheelControl(s, document.querySelector('.swiper-trending'));
    }

    if ($('.swiper-similar').length) {
        const s = new Swiper('.swiper-similar', sharedConfig);
        addWheelControl(s, document.querySelector('.swiper-similar'));
    }

    if ($('.swiper-multiple').length) {
        const s = new Swiper('.swiper-multiple', sharedConfig);
        addWheelControl(s, document.querySelector('.swiper-multiple'));
    }
});
