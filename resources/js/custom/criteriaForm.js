import TomSelect from 'tom-select';
import 'jquery-flexdatalist/jquery.flexdatalist.min';

$(document).ready(function () {

    /* ── Reset ──────────────────────────────────────────────────── */
    function resetForm(formId, prefix) {
        const $form = $(formId);
        $form.find('.bg-input').val('');
        [prefix + 'with_genres', prefix + 'without_genres', prefix + 'with_watch_providers'].forEach(function (key) {
            const ts = window['_ts_' + key];
            if (ts) ts.clear(true);
        });
        const langTs = window['_ts_' + prefix + 'with_original_language'];
        if (langTs) langTs.setValue('en', true);
        $form.find('.flexdatalist-alias').val('');
        $form.find('.fdl-remove').trigger('click');
        setTimeout(function () { $form.find('.flexdatalist-results').remove(); }, 100);
    }

    $('#btn-reset, #btn-reset-mobile').on('click', function () { resetForm('#criteria', ''); });
    $('#modal-btn-reset').on('click', function () { resetForm('#modal-criteria', 'modal-'); });

    /* ── Tom Select helper ──────────────────────────────────────── */
    function initTs(id, opts) {
        const el = document.getElementById(id);
        if (el) window['_ts_' + id] = new TomSelect('#' + id, opts);
    }

    const genreOpts = { plugins: ['remove_button'], maxOptions: null };
    const logoRender = {
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
    };

    initTs('with_genres',                { ...genreOpts, placeholder: 'Select genres…' });
    initTs('without_genres',             { ...genreOpts, placeholder: 'Exclude genres…' });
    initTs('modal-with_genres',          { ...genreOpts, placeholder: 'Select genres…' });
    initTs('modal-without_genres',       { ...genreOpts, placeholder: 'Exclude genres…' });

    initTs('with_original_language',       { maxOptions: null, create: false });
    initTs('modal-with_original_language', { maxOptions: null, create: false });

    initTs('with_watch_providers',       { plugins: ['remove_button'], placeholder: 'Select services…', maxOptions: null, render: logoRender });
    initTs('modal-with_watch_providers', { plugins: ['remove_button'], placeholder: 'Select services…', maxOptions: null, render: logoRender });

    /* ── Flexdatalist ───────────────────────────────────────────── */
    const fdlOpts = {
        minLength: 0, maxShownResults: 4, textProperty: 'name', valueProperty: 'id',
        selectionRequired: true, visibleProperties: ['name'], searchContain: true,
        searchIn: 'name', multiple: true, searchDelay: 800,
    };
    if ($('.cast').length)       $('.cast').flexdatalist(fdlOpts);
    if ($('.crew').length)       $('.crew').flexdatalist(fdlOpts);
    if ($('.modal-cast').length) $('.modal-cast').flexdatalist(fdlOpts);
    if ($('.modal-crew').length) $('.modal-crew').flexdatalist(fdlOpts);

    /* ── Live TMDB lookups ──────────────────────────────────────── */
    let castTimer, crewTimer, mCastTimer, mCrewTimer;

    function fetchPeople(name, dept, target) {
        if (!name) return;
        const params = { q: name };
        if (dept) params.dept = dept;
        $.getJSON('/tmdb/search/people', params).done(function (results) {
            $(target).flexdatalist('data', results);
        });
    }

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

    $('#modal-with_cast-flexdatalist').on('keyup', function () {
        clearTimeout(mCastTimer);
        const val = $(this).val();
        mCastTimer = setTimeout(function () { fetchPeople(val, 'Acting', '.modal-cast'); }, 500);
    }).on('keydown', function () { clearTimeout(mCastTimer); });

    $('#modal-with_crew-flexdatalist').on('keyup', function () {
        clearTimeout(mCrewTimer);
        const val = $(this).val();
        mCrewTimer = setTimeout(function () { fetchPeople(val, null, '.modal-crew'); }, 500);
    }).on('keydown', function () { clearTimeout(mCrewTimer); });

    /* ── Restore session data (criteria page only) ──────────────── */
    if ($('#criteria').length) {
        $.getJSON('/userinput', function (data) {
            restorePeople(data['with_cast'], '.cast');
            restorePeople(data['with_crew'], '.crew');
        });
    }

    function restorePeople(ids, target) {
        if (!ids) return;
        const idArr = ids.split(',');
        const resolved = [];
        $.each(idArr, function (i, id) {
            $.getJSON('/tmdb/people/' + id)
                .done(function (d) { resolved.push({ id: id, name: d.name }); })
                .fail(function () { resolved.push({ id: id, name: 'Unknown' }); })
                .always(function () {
                    if (resolved.length === idArr.length) {
                        $(target).flexdatalist('data', resolved);
                        $(target).flexdatalist('value', resolved.map(r => r.id).join(','));
                    }
                });
        });
    }

    /* ── Validation: remove error border on change ──────────────── */
    $(document).on('change', '.input-dark', function () {
        $(this).removeClass('border-danger');
    });
});
