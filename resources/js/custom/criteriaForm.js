import TomSelect from 'tom-select';
import 'jquery-flexdatalist/jquery.flexdatalist.min';

$(document).ready(function () {

    /* â”€â”€ Step wizard â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

    /* â”€â”€ Reset form â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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

    /* â”€â”€ Tom Select: genres â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    if (document.getElementById('with_genres')) {
        window['_ts_with_genres'] = new TomSelect('#with_genres', {
            plugins: ['remove_button'],
            placeholder: 'Select genresâ€¦',
            maxOptions: null,
            onInitialize() { this.control_input.placeholder = 'Search or selectâ€¦'; },
        });
    }
    if (document.getElementById('without_genres')) {
        window['_ts_without_genres'] = new TomSelect('#without_genres', {
            plugins: ['remove_button'],
            placeholder: 'Exclude genresâ€¦',
            maxOptions: null,
        });
    }

    /* â”€â”€ Tom Select: language â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    if (document.getElementById('with_original_language')) {
        window['_ts_with_original_language'] = new TomSelect('#with_original_language', {
            maxOptions: null,
            create: false,
        });
    }

    /* â”€â”€ Tom Select: streaming providers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    if (document.getElementById('with_watch_providers')) {
        window['_ts_with_watch_providers'] = new TomSelect('#with_watch_providers', {
            plugins: ['remove_button'],
            placeholder: 'Select servicesâ€¦',
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

    /* â”€â”€ Flexdatalist: cast â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    $('.cast').flexdatalist({
        minLength: 0, maxShownResults: 4, textProperty: 'name', valueProperty: 'id',
        selectionRequired: true, visibleProperties: ['name'], searchContain: true,
        searchIn: 'name', multiple: true, searchDelay: 800,
    });

    /* â”€â”€ Flexdatalist: crew â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    $('.crew').flexdatalist({
        minLength: 0, maxShownResults: 4, textProperty: 'name', valueProperty: 'id',
        selectionRequired: true, visibleProperties: ['name'], searchContain: true,
        searchIn: 'name', multiple: true, searchDelay: 800,
    });

    /* â”€â”€ Live TMDB lookups â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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
        if (!name) return;
        const params = { q: name };
        if (dept) params.dept = dept;
        $.getJSON('/tmdb/search/people', params)
            .done(function (results) {
                $(target).flexdatalist('data', results);
            });
    }

    /* â”€â”€ Restore session data via /userinput â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    $.getJSON('/userinput', function (data) {
        restorePeople(data['with_cast'], '.cast');
        restorePeople(data['with_crew'], '.crew');
    });

    function restorePeople(ids, target) {
        if (!ids) return;
        const idArr = ids.split(',');
        const resolved = [];
        $.each(idArr, function (i, id) {
            $.getJSON('/tmdb/people/' + id)
                .done(function (d) {
                    resolved.push({ id: id, name: d.name });
                })
                .fail(function () {
                    resolved.push({ id: id, name: 'Unknown' });
                })
                .always(function () {
                    if (resolved.length === idArr.length) {
                        $(target).flexdatalist('data', resolved);
                        $(target).flexdatalist('value', resolved.map(r => r.id).join(','));
                    }
                });
        });
    }

    /* â”€â”€ Validation: remove error border on change â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    $(document).on('change', '.input-dark', function () {
        $(this).removeClass('border-danger');
    });
});
