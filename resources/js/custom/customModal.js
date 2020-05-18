require('../bootstrap.js');

$(document).ready(function(){
    // MODAL
    $(document).on('hide.bs.modal','#myModal', function () {
        $('#trailer').attr("src", jQuery("#trailer").attr("src"));
    });

    $('#myModal').on('show.bs.modal', function (e) {
        $('.nav-nav').css('padding-right', '17px');
        $('.nav-nav').css('transition', 'all 0s ease');
    });

    $('#myModal').on('hidden.bs.modal', function (e) {
        $('.nav-nav').css('padding-right', '0');
    });
});
