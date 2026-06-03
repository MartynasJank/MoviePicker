import TomSelect from 'tom-select';
import jq from 'jquery';
window.jQuery = window.jQuery || jq;   // flexdatalist expects a global jQuery
import 'jquery-flexdatalist/jquery.flexdatalist.min';

$(document).ready(function () {
    const tmdb = window.TMDB_API_KEY;

    /* -- Step wizard inside the adjust-form modal -------------- */
    let currentStep = 1;
    const totalSteps = 5;

    function showStep(step) {
        currentStep = step;
        $('.modal-step-panel').addClass('hidden');
        $('#modal-step-' + step).removeClass('hidden');

        for (let i = 1; i <= totalSteps; i++) {
            const $dot   = $('#modal-step-dot-' + i);
            const $label = $('#modal-step-label-' + i);
            const $line  = $('#modal-step-line-' + i);
            $dot.removeClass('active done');
            $label.removeClass('active done');
            if ($line.length) $line.removeClass('done');
            if (i < step) { $dot.addClass('done');   $label.addClass('done');   if ($line.length) $line.addClass('done'); }
            if (i === step) { $dot.addClass('active'); $label.addClass('active'); }
        }

        $('#modal-btn-prev').toggleClass('hidden', step === 1);
        $('#modal-btn-next').toggleClass('hidden', step === totalSteps);
        $('#modal-btn-find-movie').toggleClass('hidden', step !== totalSteps);
        $('#modal-btn-find-multiple').toggleClass('hidden', step !== totalSteps);
    }

    $('#modal-btn-next').on('click', function () { if (currentStep < totalSteps) showStep(currentStep + 1); });
    $('#modal-btn-prev').on('click', function () { if (currentStep > 1) showStep(currentStep - 1); });

    if ($('#modal-step-1').length) showStep(1);

    /* -- Tom Select: genres ------------------------------------ */
    if (document.getElementById('modal-with_genres')) {
        window._ts_modal_with_genres = new TomSelect('#modal-with_genres', {
            plugins: ['remove_button'], placeholder: 'Select genres...', maxOptions: null,
        });
    }
    if (document.getElementById('modal-without_genres')) {
        window._ts_modal_without_genres = new TomSelect('#modal-without_genres', {
            plugins: ['remove_button'], placeholder: 'Exclude genres...', maxOptions: null,
        });
    }

    /* -- Tom Select: language ---------------------------------- */
    if (document.getElementById('modal-with_original_language')) {
        window._ts_modal_lang = new TomSelect('#modal-with_original_language', {
            maxOptions: null, create: false,
        });
    }

    /* -- Tom Select: streaming providers ---------------------- */
    if (document.getElementById('modal-with_watch_providers')) {
        window._ts_modal_providers = new TomSelect('#modal-with_watch_providers', {
            plugins: ['remove_button'],
            placeholder: 'Select services...',
            maxOptions: null,
            render: {
                option: function (data, escape) {
                    const logo = data.$option ? data.$option.dataset.logo : '';
                    return logo
                        ? '<div class="option-with-logo"><img src="' + escape(logo) + '"><span>' + escape(data.text) + '</span></div>'
                        : '<div>' + escape(data.text) + '</div>';
                },
                item: function (data, escape) {
                    const logo = data.$option ? data.$option.dataset.logo : '';
                    return logo
                        ? '<div class="option-with-logo" style="display:flex;align-items:center;gap:6px"><img src="' + escape(logo) + '" style="height:16px;width:auto"><span>' + escape(data.text) + '</span></div>'
                        : '<div>' + escape(data.text) + '</div>';
                },
            },
        });
    }

    /* -- Restore session data ---------------------------------- */
    const isTvPage = window.location.pathname.startsWith('/tv/');
    $.getJSON('/userinput', isTvPage ? { type: 'tv' } : {}, function (data) {

        if (data['with_genres'] && window._ts_modal_with_genres) {
            window._ts_modal_with_genres.setValue(data['with_genres'].split(','), true);
        }
        if (data['without_genres'] && window._ts_modal_without_genres) {
            window._ts_modal_without_genres.setValue(data['without_genres'].split(','), true);
        }
        if (data['with_original_language'] && window._ts_modal_lang) {
            window._ts_modal_lang.setValue(data['with_original_language'], true);
        }
        if (data['with_watch_providers'] && window._ts_modal_providers) {
            window._ts_modal_providers.setValue(data['with_watch_providers'].split(','), true);
        }
        if (data['primary_release_date_gte']) $('#modal-primary_release_date_gte').val(data['primary_release_date_gte']);
        if (data['primary_release_date_lte']) $('#modal-primary_release_date_lte').val(data['primary_release_date_lte']);
        if (data['vote_average_gte'])         $('#modal-vote_average_gte').val(data['vote_average_gte']);
        if (data['vote_average_lte'])         $('#modal-vote_average_lte').val(data['vote_average_lte']);
        if (data['vote_count_gte'])           $('#modal-vote_count_gte').val(data['vote_count_gte']);
    });

    /* -- Auto-dismiss errors ----------------------------------- */
    setTimeout(function () {
        $('.alert-msg').fadeOut(400, function () { $(this).remove(); });
    }, 4000);
});
