require('../../node_modules/bootstrap4-toggle/js/bootstrap4-toggle.min.js');

$(document).ready(function(){
    // Nav bar
    var open = false
    $('.navTrigger').click(function () {
        if(open){
            $(this).removeClass('active');
            $("#mainListDiv").removeClass("show_list");
            $("#mainListDiv").fadeOut(500);
            open = false;
        } else if(!open){
            $(this).addClass('active');
            $("#mainListDiv").addClass("show_list");
            $("#mainListDiv").fadeIn(500);
            open = true;
        }
    });

    $(window).resize(function(){
        if(($(window).width() + 17) > 768){
            $('.navTrigger').removeClass('active')
            $("#mainListDiv").removeClass('show_list');
            $('.navTrigger').removeClass('active');
            $("#mainListDiv").show();
            open = false
        }
    });

    $("#theme-switcher").change(function ()
    {
        if(this.checked){
            setCookie('theme', 'dark', 30);
            $('body').attr('class', 'dark-theme');
        }
        if(!this.checked){
            setCookie('theme', 'light', 30);
            $('body').attr('class', 'light-theme');
        }
    });

    // Cookies to detect if person chose dark or light theme
    function setCookie(cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays*24*60*60*1000));
        var expires = "expires="+ d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    }

    function deleteCookie(cname){
        document.cookie = cname+'=; Max-Age=-99999999;';
    }

    function loadingAnimation(){
            $('.overlay').fadeIn();
            $('.loading-text').fadeIn();
            $('.loader').fadeIn();
    }

    $('#criteria').submit(function () {
        window.addEventListener("beforeunload", loadingAnimation);
        $('.loading-text').text('Getting the best result for you!');
        setTimeout(function () {
            window.removeEventListener("beforeunload", loadingAnimation);
        }, 500);
    });

    $('#movie-search').submit(function () {
        window.addEventListener("beforeunload", loadingAnimation);
        $('.loading-text').text('Fetching the movie!');
        setTimeout(function () {
            window.removeEventListener("beforeunload", loadingAnimation);
        }, 500);
    });

    $('.long-single').click(function () {
        window.addEventListener("beforeunload", loadingAnimation);
        $('.loading-text').text('Looking for a perfect movie!');
        setTimeout(function () {
            window.removeEventListener("beforeunload", loadingAnimation);
        }, 100);
    });

    $('.long-movie').click(function () {
        window.addEventListener("beforeunload", loadingAnimation);
        var movieName = $(this).data('name');
        $('.loading-text').text('Loading ' + movieName + '!');
        setTimeout(function () {
            window.removeEventListener("beforeunload", loadingAnimation);
        }, 100);
    });
});

