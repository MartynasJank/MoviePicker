import Swiper from 'swiper';
import { Navigation } from 'swiper/modules';

function addWheelControl(swiper, el) {
    let cooldown = false;
    el.addEventListener('wheel', (e) => {
        const delta = Math.abs(e.deltaX) >= 5 ? e.deltaX : e.deltaY;
        if (Math.abs(delta) < 5) return;
        e.preventDefault();
        if (cooldown) return;
        cooldown = true;
        delta > 0 ? swiper.slideNext() : swiper.slidePrev();
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

    if ($('.swiper-trending-day').length) {
        const s = new Swiper('.swiper-trending-day', sharedConfig);
        addWheelControl(s, document.querySelector('.swiper-trending-day'));
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
                addWheelControl(weekSwiper, document.querySelector('.swiper-trending-week'));
            }
        });
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
