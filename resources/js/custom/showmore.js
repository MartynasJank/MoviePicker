$(document).ready(function () {
    initShowMore('ul.crew-list');
    initShowMore('ul.cast-list');
    initShowMore('ul.production-list');

    function initShowMore(selector) {
        $(selector).each(function () {
            if ($(this).find('li').length > 3) {
                $('li', this).eq(2).nextAll().hide().addClass('toggleable');
                $(this).append('<li class="toggle-more" style="cursor:pointer;color:#c0393a;font-size:0.8rem;padding-top:6px;">Show all</li>');
            }
            $(this).on('click', '.toggle-more', function () {
                const isOpen = $(this).hasClass('less');
                $(this).text(isOpen ? 'Show all' : 'Show less').toggleClass('less', !isOpen);
                $(this).siblings('li.toggleable').slideToggle(200);
            });
        });
    }
});
