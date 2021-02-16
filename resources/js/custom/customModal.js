require('../bootstrap');
require('../../../node_modules/bootstrap-select/dist/js/bootstrap-select');
require('../../../node_modules/smartwizard/dist/js/jquery.smartWizard');
require('../jquery.flexdatalist.min');

$(document).ready(function(){

    var animation = true;
    var tmdb = process.env.MIX_TMDB_API_KEY;

    $(document).on('hide.bs.modal','#myModal', function () {
        $('#trailer').attr("src", jQuery("#trailer").attr("src"));
    });

    $('.modal').on('show.bs.modal', function (e) {
        if ($(document).height() > $(window).height() ) {
            $('.nav-nav').css('padding-right', '17px');
        }
        $('.nav-nav').css('transition', 'all 0s ease');
    });

    $('.modal').on('hidden.bs.modal', function (e) {
        $('.nav-nav').css('padding-right', '0');
    });

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
        // $('.content').append('<button type="button" class="btn btn-secondary btn-left form-reset">Reset Form</button>');
        $('.sw-btn-group-extra').append('<button type="submit" class="btn btn-secondary btn-left mr-auto" style="margin-left: 8px" formaction="/multiple?a=true">Find Multiple</button>');
    } else {
        var smallScreen = true;
        $('.btn-toolbar').prepend('<button type="button" class="btn btn-secondary btn-left form-reset mr-auto d-none d-lg-block d-xl-block">Reset Form</button>');
        $('.sw-btn-group-extra').append('<button type="submit" class="btn btn-secondary btn-left mr-auto" style="margin-left: 8px" formaction="/multiple?a=true">Find Multiple</button>');
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
            $('#criteria .flexdatalist-alias').val('');
            $('.fdl-remove').click();
            $('#criteria .flexdatalist-alias').blur();
            $('.selectpicker').selectpicker('deselectAll');
            $('#criteria .bg-input').val('');
            $('#with_original_language').val('en').trigger('change');
            $('#criteria .flexdatalist-results').remove();
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

    $('.movie-search').flexdatalist({
        minLength: 1,
        maxShownResults: 3,
        textProperty: '{title}',
        valueProperty: 'id',
        selectionRequired: true,
        visibleProperties: ["item-backdrop_path", "meta"],
        searchContain: true,
        searchByWord: true,
        searchIn: 'title',
        multiple: false,
        cacheLifetime: 5,
        searchDelay: 1000
    }).on("show:flexdatalist.results",function(ev,result){
        $.each(result,function(key,value){
            console.log(value);
            if(value.backdrop_path != null) {
                result[key]['item-backdrop_path'] = '<img src="https://image.tmdb.org/t/p/w92' + value.backdrop_path + '">';
            }
            if(value.title != null || value.release_date != null) {
                result[key]['meta'] = '<div class="movie-meta"><span class="item">'+value.title_highlight+'</span><span class="item">'+value.release_date+'</span></div>';
            }
        });
    }).on('select:flexdatalist', function () {
        $('.submit-search').submit();
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
    var $input3 = $('#movie_search-flexdatalist');

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

    $input3.on('keyup', function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(done($input3, movie, tmdb), doneTypingInterval);
    });

    //on keydown, clear the countdown
    $input3.on('keydown', function () {
        clearTimeout(typingTimer);
    });

    // $('#with_cast-flexdatalist').parent().parent().click(function(){
    //     if(animation){
    //         $("body,html").animate({
    //                 scrollTop: $(this).offset().top - $(this).height()*3
    //             },
    //             200
    //         );
    //     }
    // });
    //
    // $('#with_crew-flexdatalist').parent().parent().click(function(){
    //     if(animation){
    //         $("body,html").animate({
    //                 scrollTop: $(this).offset().top - $(this).height()*3
    //             },
    //             200
    //         );
    //     }
    // });

    $.getJSON('/userinput', function(data) {
        if (data['with_cast']){
            s_array = data['with_cast'].split(',');
            $user_array = [];
            $.each(s_array, function (i, id) {
                $.getJSON("https://api.themoviedb.org/3/person/"+id+"?api_key="+tmdb+"&language=en-US", function (data) {

                }).done(function (data) {
                    $user_array.push({
                        id: id,
                        name: data.name
                    });
                    if(s_array.length == $user_array.length) {
                        s_id = [];
                        $.each($user_array, function (i, entry) {
                            s_id.push(entry.id);
                        });
                        $('.cast').flexdatalist('data', $user_array);
                        $('.cast').flexdatalist('value', s_id.toString())
                    }
                })
            });
        }

        if (data['with_crew']){
            c_array = data['with_crew'].split(',');
            $crew_array = [];
            $.each(c_array, function (i, id) {
                $.getJSON("https://api.themoviedb.org/3/person/"+id+"?api_key="+tmdb+"&language=en-US", function (data) {

                }).done(function (data) {
                    $crew_array.push({
                        id: id,
                        name: data.name
                    });
                    if(c_array.length == $crew_array.length) {
                        c_id = [];
                        $.each($user_array, function (i, entry) {
                            c_id.push(entry.id);
                        });
                        $('.cast').flexdatalist('data', $crew_array);
                        $('.cast').flexdatalist('value', c_id.toString())
                    }
                })
            });
        }
    });

    // FUNCTIONS
    // Action after user stops typing
    function done($input, fn, tmdb){
        val = $input.val();
        fn(val, tmdb);
    }

    $.getJSON('/userinput', function(data) {
        if (data['with_cast']){
            s_array = data['with_cast'].split(',');
            $user_array = [];
            $.each(s_array, function (i, id) {
                $.getJSON("https://api.themoviedb.org/3/person/"+id+"?api_key="+tmdb+"&language=en-US", function (data) {

                }).done(function (data) {
                    $user_array.push({
                        id: id,
                        name: data.name
                    });
                    if(s_array.length == $user_array.length) {
                        s_id = [];
                        $.each($user_array, function (i, entry) {
                            s_id.push(entry.id);
                        });
                        $('.cast').flexdatalist('data', $user_array);
                        $('.cast').flexdatalist('value', s_id.toString())
                    }
                })
            });
        }

        if (data['with_crew']){
            c_array = data['with_crew'].split(',');
            $crew_array = [];
            $.each(c_array, function (i, id) {
                $.getJSON("https://api.themoviedb.org/3/person/"+id+"?api_key="+tmdb+"&language=en-US", function (data) {

                }).done(function (data) {
                    $crew_array.push({
                        id: id,
                        name: data.name
                    });
                    if(c_array.length == $crew_array.length) {
                        c_id = [];
                        $.each($user_array, function (i, entry) {
                            c_id.push(entry.id);
                        });
                        $('.cast').flexdatalist('data', $crew_array);
                        $('.cast').flexdatalist('value', c_id.toString())
                    }
                })
            });
        }
    });

    movie('avengers', tmdb);
    // Get actors for autocpomplete
    function movie(name, tmdb){
        if(name.length > 0){
            var jqxhr = $.getJSON( "https://api.themoviedb.org/3/search/movie?api_key="+tmdb+"&language=en-US&query="+name+"&page=1&include_adult=false", function(data) {
            })
                .done(function(data) {
                    results = [];
                    $.each(data.results, function(i, item){
                        results.push(item);
                        if(i === 2){
                            return false;
                        }
                    });
                    $('.movie-search').flexdatalist('data', results);
                })
                .fail(function() {
                })
                .always(function() {
                });
        }
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
        if($(window).width() < 768 && smallScreen){
            $('.form-reset').remove();
            $('.content').append('<button type="button" class="btn btn-secondary btn-left form-reset d-none d-lg-block d-xl-block">Reset Form</button>');
            formReset();
            smallScreen = false;
        } else if($(window).width() > 768 && !smallScreen){
            $('.form-reset').remove();
            $('.btn-toolbar').prepend('<button type="button" class="btn btn-secondary btn-left form-reset mr-auto d-none d-lg-block d-xl-block">Reset Form</button>');
            formReset();
            smallScreen = true;
        }
    }
});
