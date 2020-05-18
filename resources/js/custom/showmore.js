$(document).ready(function(){
    showMore('ul.crew-list')
    showMore('ul.cast-list')
    showMore('ul.production-list');

    // Show more controls
    function toggleShow(){
        var opened = $(this).hasClass('less');
        $(this).text(opened ? 'Show All' : 'Hide').toggleClass('less', !opened);
        $(this).siblings('li.toggleable').slideToggle();
    }

    function showMore(eleString){
        $(eleString).each(function(){
            if( $(this).find('li').length > 3){
                $('li', this).eq(2).nextAll().hide().addClass('toggleable');
                $(this).append('<li class="more">Show all</li>');
            }
            $(this).on('click','.more', toggleShow);
        });
    }
});
