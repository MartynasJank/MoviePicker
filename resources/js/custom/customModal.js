import TomSelect from 'tom-select';
import '../jquery.flexdatalist.min';

$(document).ready(function () {
    const tmdb = window.TMDB_API_KEY;

    /* ── Step wizard inside the adjust-form modal ────────────── */
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

    /* ── Tom Select: genres ──────────────────────────────────── */
    if (document.getElementById('modal-with_genres')) {
        window._ts_modal_with_genres = new TomSelect('#modal-with_genres', {
            plugins: ['remove_button'], placeholder: 'Select genres…', maxOptions: null,
        });
    }
    if (document.getElementById('modal-without_genres')) {
        window._ts_modal_without_genres = new TomSelect('#modal-without_genres', {
            plugins: ['remove_button'], placeholder: 'Exclude genres…', maxOptions: null,
        });
    }

    /* ── Tom Select: language ────────────────────────────────── */
    if (document.getElementById('modal-with_original_language')) {
        window._ts_modal_lang = new TomSelect('#modal-with_original_language', {
            maxOptions: null, create: false,
        });
    }

    /* ── Tom Select: streaming providers ────────────────────── */
    if (document.getElementById('modal-with_watch_providers')) {
        window._ts_modal_providers = new TomSelect('#modal-with_watch_providers', {
            plugins: ['remove_button'],
            placeholder: 'Select services…',
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

    /* ── Flexdatalist: cast & crew in modal ──────────────────── */
    if ($('.modal-cast').length) {
        $('.modal-cast').flexdatalist({
            minLength: 0, maxShownResults: 4, textProperty: 'name', valueProperty: 'id',
            selectionRequired: true, visibleProperties: ['name'], searchContain: true,
            searchIn: 'name', multiple: true, searchDelay: 800,
        });
    }
    if ($('.modal-crew').length) {
        $('.modal-crew').flexdatalist({
            minLength: 0, maxShownResults: 4, textProperty: 'name', valueProperty: 'id',
            selectionRequired: true, visibleProperties: ['name'], searchContain: true,
            searchIn: 'name', multiple: true, searchDelay: 800,
        });
    }

    let castTimer, crewTimer;
    $('#modal-with_cast-flexdatalist').on('keyup', function () {
        clearTimeout(castTimer);
        const val = $(this).val();
        castTimer = setTimeout(function () { fetchPeople(val, 'Acting', '.modal-cast'); }, 500);
    }).on('keydown', function () { clearTimeout(castTimer); });

    $('#modal-with_crew-flexdatalist').on('keyup', function () {
        clearTimeout(crewTimer);
        const val = $(this).val();
        crewTimer = setTimeout(function () { fetchPeople(val, null, '.modal-crew'); }, 500);
    }).on('keydown', function () { clearTimeout(crewTimer); });

    function fetchPeople(name, dept, target) {
        if (!name || !tmdb) return;
        $.getJSON('https://api.themoviedb.org/3/search/person?api_key=' + tmdb + '&language=en-US&query=' + encodeURIComponent(name) + '&page=1&include_adult=false')
            .done(function (data) {
                const results = [];
                $.each(data.results, function (i, item) {
                    if (!dept || item.known_for_department === dept) results.push(item);
                    if (results.length >= 4) return false;
                });
                $(target).flexdatalist('data', results);
            });
    }

    /* ── Restore session data ────────────────────────────────── */
    $.getJSON('/userinput', function (data) {
        restorePeople(data['with_cast'], '.modal-cast');
        restorePeople(data['with_crew'], '.modal-crew');

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

    function restorePeople(ids, target) {
        if (!ids || !tmdb) return;
        const idArr = ids.split(',');
        const resolved = [];
        $.each(idArr, function (i, id) {
            $.getJSON('https://api.themoviedb.org/3/person/' + id + '?api_key=' + tmdb + '&language=en-US')
                .done(function (d) {
                    resolved.push({ id: id, name: d.name });
                    if (resolved.length === idArr.length) {
                        $(target).flexdatalist('data', resolved);
                        $(target).flexdatalist('value', resolved.map(r => r.id).join(','));
                    }
                });
        });
    }

    /* ── Auto-dismiss errors ─────────────────────────────────── */
    setTimeout(function () {
        $('.alert-msg').fadeOut(400, function () { $(this).remove(); });
    }, 4000);
});
