import TomSelect from 'tom-select';
import '../jquery.flexdatalist.min';

$(document).ready(function () {
    const tmdb = window.TMDB_API_KEY;

    /* ── Step wizard ─────────────────────────────────────────── */
    let currentStep = 1;
    const totalSteps = 5;

    function showStep(step) {
        currentStep = step;
        $('.step-panel').addClass('hidden');
        $('#step-' + step).removeClass('hidden');

        // Update indicators
        for (let i = 1; i <= totalSteps; i++) {
            const $dot   = $('#step-dot-' + i);
            const $label = $('#step-label-' + i);
            const $line  = $('#step-line-' + i);
            $dot.removeClass('active done');
            $label.removeClass('active done');
            if ($line.length) $line.removeClass('done');
            if (i < step) { $dot.addClass('done');   $label.addClass('done');   if ($line.length) $line.addClass('done'); }
            if (i === step) { $dot.addClass('active'); $label.addClass('active'); }
        }

        $('#btn-prev').toggleClass('hidden', step === 1);
        $('#btn-next').toggleClass('hidden', step === totalSteps);
        $('#btn-find-movie').toggleClass('hidden', step !== totalSteps);
        $('#btn-find-multiple').toggleClass('hidden', step !== totalSteps);
    }

    $('#btn-next').on('click', function () { if (currentStep < totalSteps) showStep(currentStep + 1); });
    $('#btn-prev').on('click', function () { if (currentStep > 1) showStep(currentStep - 1); });

    // Clicking a completed step indicator navigates to it
    $(document).on('click', '.step-dot.done, .step-dot.active', function () {
        const s = parseInt($(this).data('step'));
        if (s) showStep(s);
    });

    showStep(1);

    /* ── Reset form ──────────────────────────────────────────── */
    $('#btn-reset').on('click', function () {
        $('#criteria .bg-input').val('');
        ['#with_genres', '#without_genres', '#with_watch_providers'].forEach(function (sel) {
            const ts = sel.replace('#', '');
            if (window['_ts_' + ts]) window['_ts_' + ts].clear(true);
        });
        if ($('#with_original_language').length) {
            const ts = window['_ts_with_original_language'];
            if (ts) ts.setValue('en', true);
        }
        $('#criteria .flexdatalist-alias').val('');
        $('.fdl-remove').trigger('click');
        setTimeout(function () { $('#criteria .flexdatalist-results').remove(); }, 100);
    });

    /* ── Tom Select: genres ──────────────────────────────────── */
    if (document.getElementById('with_genres')) {
        window['_ts_with_genres'] = new TomSelect('#with_genres', {
            plugins: ['remove_button'],
            placeholder: 'Select genres…',
            maxOptions: null,
            onInitialize() { this.control_input.placeholder = 'Search or select…'; },
        });
    }
    if (document.getElementById('without_genres')) {
        window['_ts_without_genres'] = new TomSelect('#without_genres', {
            plugins: ['remove_button'],
            placeholder: 'Exclude genres…',
            maxOptions: null,
        });
    }

    /* ── Tom Select: language ────────────────────────────────── */
    if (document.getElementById('with_original_language')) {
        window['_ts_with_original_language'] = new TomSelect('#with_original_language', {
            maxOptions: null,
            create: false,
        });
    }

    /* ── Tom Select: streaming providers ────────────────────── */
    if (document.getElementById('with_watch_providers')) {
        window['_ts_with_watch_providers'] = new TomSelect('#with_watch_providers', {
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

    /* ── Flexdatalist: cast ──────────────────────────────────── */
    $('.cast').flexdatalist({
        minLength: 0, maxShownResults: 4, textProperty: 'name', valueProperty: 'id',
        selectionRequired: true, visibleProperties: ['name'], searchContain: true,
        searchIn: 'name', multiple: true, searchDelay: 800,
    });

    /* ── Flexdatalist: crew ──────────────────────────────────── */
    $('.crew').flexdatalist({
        minLength: 0, maxShownResults: 4, textProperty: 'name', valueProperty: 'id',
        selectionRequired: true, visibleProperties: ['name'], searchContain: true,
        searchIn: 'name', multiple: true, searchDelay: 800,
    });

    /* ── Live TMDB lookups ───────────────────────────────────── */
    let castTimer, crewTimer;

    $('#with_cast-flexdatalist').on('keyup', function () {
        clearTimeout(castTimer);
        const val = $(this).val();
        castTimer = setTimeout(function () { fetchPeople(val, 'Acting', '.cast'); }, 500);
    }).on('keydown', function () { clearTimeout(castTimer); });

    $('#with_crew-flexdatalist').on('keyup', function () {
        clearTimeout(crewTimer);
        const val = $(this).val();
        crewTimer = setTimeout(function () { fetchPeople(val, null, '.crew'); }, 500);
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

    /* ── Restore session data via /userinput ─────────────────── */
    $.getJSON('/userinput', function (data) {
        restorePeople(data['with_cast'], '.cast');
        restorePeople(data['with_crew'], '.crew');
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

    /* ── Validation: remove error border on change ───────────── */
    $(document).on('change', '.input-dark', function () {
        $(this).removeClass('border-danger');
    });
});
