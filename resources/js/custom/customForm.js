require('../bootstrap');
require('../../../node_modules/bootstrap-select/dist/js/bootstrap-select');
require('../../../node_modules/smartwizard/dist/js/jquery.smartWizard');
require('../jquery.flexdatalist.min');
$(document).ready(function(){
    var animation = true;
    var tmdb = '';

    $('#smartwizard').smartWizard({
        selected: 0,
        cycleSteps: true,
        autoAdjustHeight: false,
        showStepURLhash: false,
        anchorSettings : {
            enableAllAnchors: true,
            markDoneStep: false,
            markAllPreviousStepsAsDone: false,
            enableAnchorOnDoneStep: false,
        },
        toolbarSettings : {
            toolbarButtonPosition: 'right',
            toolbarExtraButtons: [
                $('<button></button>').text('Find a Movie').addClass('btn btn-secondary'),
            ],
        }
    });
    $('#smartwizard').show();

    // DELETE SELECT ALL BUTTON BECAUSE I DON'T NEED IT AND SELECTPICKER DOESN'T LET YOU CUSTOMIZE IT
    $('.selectpicker').on('loaded.bs.select', function(){
        $('.bs-select-all').remove();
    });

    if($(window).width() < 768){
        var smallScreen = false;
        $('.content').append('<button type="button" class="btn btn-secondary btn-left form-reset">Reset Form</button>');
    } else {
        var smallScreen = true;
        $('.btn-toolbar').prepend('<button type="button" class="btn btn-secondary btn-left form-reset mr-auto">Reset Form</button>');
    }
    formReset();

    var resizeId;
    $(window).resize(function(){
        clearTimeout(resizeId);
        resizeId = setTimeout(doneResizing, 200);
    });

    // DELETE SELECT ALL BUTTON BECAUSE I DON'T NEED IT AND SELECTPICKER DOESN'T LET YOU CUSTOMIZE IT
    $('.selectpicker').on('loaded.bs.select', function(){
        $('.bs-select-all').remove();
    });

    // Deletes error mesesages after 5s
    setTimeout(function(){
       $("div.alert").remove();
    }, 1000 );

    // Removes red border from input if input was changed
    $('.movie-input').change(function()
    {
        if( $(this).hasClass('border-danger') ) {
            $(this).removeClass('border-danger');
        }
    });

    function formReset(){
        $('.form-reset').click(function(){
            animation = false;
            $('.flexdatalist-alias').val('');
            $('.fdl-remove').click();
            $('.flexdatalist-alias').blur();
            $('.selectpicker').selectpicker('deselectAll');
            $('.bg-input').val('');
            $('#with_original_language').val('').trigger('change');
            $('.flexdatalist-results').remove();
            animation = true;
        });
    }

    // Custom select for cast in form
    $('.cast').flexdatalist({
        minLength: 0,
        maxShownResults: 3,
        textProperty: 'name',
        valueProperty: 'id',
        selectionRequired: true,
        visibleProperties: ["name"],
        searchContain: true,
        searchIn: 'name',
        multiple: true,
        searchDelay: 1000,
    });

    // Custom select for crew in form
    $('.crew').flexdatalist({
        minLength: 0,
        maxShownResults: 3,
        textProperty: 'name',
        valueProperty: 'id',
        selectionRequired: true,
        visibleProperties: ["name"],
        searchContain: true,
        searchIn: 'name',
        multiple: true,
        searchDelay: 1000
    });

    // Delete autocomplete data on select
    $('input.flexdatalist').on('select:flexdatalist', function(event, set, options) {
        $('.cast').flexdatalist('data', []);
        $('.crew').flexdatalist('data', []);
    });

    var typingTimer;
    var doneTypingInterval = 500;
    var $input1 = $('#with_cast-flexdatalist');
    var $input2 = $('#with_crew-flexdatalist');

    // Find when user stops typing
    //on keyup, start the countdown
    $input1.on('keyup', function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(done($input1, actor, tmdb), doneTypingInterval);
    });

    //on keydown, clear the countdown
    $input1.on('keydown', function () {
        clearTimeout(typingTimer);
    });


    $input2.on('keyup', function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(done($input2, crew, tmdb), doneTypingInterval);
    });

    //on keydown, clear the countdown
    $input2.on('keydown', function () {
        clearTimeout(typingTimer);
    });

    $('#with_cast-flexdatalist').parent().parent().click(function(){
        if(animation){
            $("body,html").animate({
                scrollTop: $(this).offset().top - $(this).height()*3
                },
                200
            );
        }
    });

    $('#with_crew-flexdatalist').parent().parent().click(function(){
        if(animation){
            $("body,html").animate({
                scrollTop: $(this).offset().top - $(this).height()*3
                },
                200
            );
        }
    });

    // FUNCTIONS
    // Action after user stops typing
    function done($input, fn, tmdb){
        val = $input.val();
        fn(val, tmdb);
    }
    // Get actors for autocpomplete
    function actor(name, tmdb){
        if(name.length > 0){
            var jqxhr = $.getJSON( "https://api.themoviedb.org/3/search/person?api_key="+tmdb+"&language=en-US&query="+name+"&page=1&include_adult=false", function(data) {
            })
            .done(function(data) {
                results = [];
                $.each(data.results, function(i, item){
                    if(item.known_for_department === 'Acting'){
                        results.push(item);
                    }
                    if(i === 2){
                        return false;
                    }
                });
                $('.cast').flexdatalist('data', results);
            })
            .fail(function() {
            })
            .always(function() {
            });
        }
    }

    // Get crew members for autocpomplete
    function crew(name, tmdb){
        if(name.length > 0){
            var jqxhr = $.getJSON( "https://api.themoviedb.org/3/search/person?api_key="+tmdb+"&language=en-US&query="+name+"&page=1&include_adult=false", function() {
            })
            .done(function(data) {
                results = [];
                $.each(data.results, function(i, item){
                    if(item.known_for_department !== 'Acting'){
                        results.push(item);
                    }
                    if(i === 3){
                        return false;
                    }
                });
                $('.crew').flexdatalist('data', results);
            })
            .fail(function() {
            })
            .always(function() {
            });
        }
    }

    function doneResizing(){
        console.log('resize');
        if($(window).width() < 768 && smallScreen){
            $('.form-reset').remove();
            $('.content').append('<button type="button" class="btn btn-secondary btn-left form-reset">Reset Form</button>');
            formReset();
            smallScreen = false;
        } else if($(window).width() > 768 && !smallScreen){
            $('.form-reset').remove();
            $('.btn-toolbar').prepend('<button type="button" class="btn btn-secondary btn-left form-reset mr-auto">Reset Form</button>');
            formReset();
            smallScreen = true;
        }
    }
});
