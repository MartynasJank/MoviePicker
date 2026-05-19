import TomSelect from 'tom-select';

$(document).ready(function () {

    /* ── Tom Select helper ──────────────────────────────────────── */
    function initTs(id, opts) {
        const el = document.getElementById(id);

        if (!el) return;

        // Prevent double initialization
        if (el.tomselect) {
            return el.tomselect;
        }

        const ts = new TomSelect(el, opts);

        window['_ts_' + id] = ts;

        return ts;
    }

    function makePeopleTs(id, dept) {
        const el = document.getElementById(id);
        if (!el) return;
        const ts = new TomSelect('#' + id, {
            plugins: ['remove_button'],
            placeholder: dept ? 'Search actors…' : 'Search directors, writers…',
            maxOptions: null,
            create: false,
        });
        let timer;
        ts.on('type', function (query) {
            clearTimeout(timer);
            if (!query) return;
            timer = setTimeout(function () {
                const params = { q: query };
                if (dept) params.dept = dept;
                ts.loading++;
                ts.wrapper.classList.add(ts.settings.loadingClass);
                ts.clearOptions();
                ts.refreshOptions(false);
                $.getJSON('/tmdb/search/people', params)
                    .done(function (data) {
                        data.forEach(function (p) { ts.addOption({ value: String(p.id), text: p.name }); });
                    })
                    .always(function () {
                        ts.loading = Math.max(ts.loading - 1, 0);
                        if (!ts.loading) ts.wrapper.classList.remove(ts.settings.loadingClass);
                        ts.refreshOptions(true);
                    });
            }, 400);
        });
        window['_ts_' + id] = ts;
    }

    /* ── Genres ─────────────────────────────────────────────────── */
    const genreOpts = { plugins: ['remove_button'], maxOptions: null };
    initTs('with_genres',          { ...genreOpts, placeholder: 'Select genres…' });
    initTs('without_genres',       { ...genreOpts, placeholder: 'Exclude genres…' });
    initTs('modal-with_genres',    { ...genreOpts, placeholder: 'Select genres…' });
    initTs('modal-without_genres', { ...genreOpts, placeholder: 'Exclude genres…' });

    /* ── Language ───────────────────────────────────────────────── */
    initTs('with_original_language',       { maxOptions: null, create: false });
    initTs('modal-with_original_language', { maxOptions: null, create: false });

    /* ── Streaming providers ─────────────────────────────────────── */
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
    initTs('with_watch_providers',       { plugins: ['remove_button'], placeholder: 'Select services…', maxOptions: null, render: logoRender });
    initTs('modal-with_watch_providers', { plugins: ['remove_button'], placeholder: 'Select services…', maxOptions: null, render: logoRender });

    /* ── People ─────────────────────────────────────────────────── */
    makePeopleTs('with_cast', 'Acting');
    makePeopleTs('with_crew', null);

    $(document).on('click', '[data-modal-open="modal-form"]', function () {
        if (!window['_ts_modal-with_cast']) {
            makePeopleTs('modal-with_cast', 'Acting');
            makePeopleTs('modal-with_crew', null);
            $.getJSON('/userinput', function (data) {
                restorePeople(data['with_cast'], 'modal-with_cast');
                restorePeople(data['with_crew'], 'modal-with_crew');
            });
        }
    });

    /* ── Reset ──────────────────────────────────────────────────── */
    function resetForm(formId, prefix) {
        const $form = $(formId);
        $form.find('.bg-input').val('');
        ['with_genres', 'without_genres', 'with_watch_providers', 'with_cast', 'with_crew'].forEach(function (key) {
            const ts = window['_ts_' + prefix + key];
            if (ts) ts.clear(true);
        });
        const langTs = window['_ts_' + prefix + 'with_original_language'];
        if (langTs) langTs.setValue('en', true);
    }

    $('#btn-reset, #btn-reset-mobile').on('click', function () { resetForm('#criteria', ''); });
    $('#modal-btn-reset').on('click', function () { resetForm('#modal-criteria', 'modal-'); });

    /* ── Restore session data (criteria page only) ──────────────── */
    if ($('#criteria').length) {
        $.getJSON('/userinput', function (data) {
            restorePeople(data['with_cast'], 'with_cast');
            restorePeople(data['with_crew'], 'with_crew');
        });
    }

    function restorePeople(ids, tsId) {
        if (!ids) return;
        const ts = window['_ts_' + tsId];
        if (!ts) return;
        const idArr = Array.isArray(ids) ? ids : ids.split(',');
        idArr.forEach(function (id) {
            $.getJSON('/tmdb/people/' + id)
                .done(function (d) {
                    ts.addOption({ value: String(d.id), text: d.name });
                    ts.addItem(String(d.id), true);
                });
        });
    }

    /* ── Validation: remove error border on change ──────────────── */
    $(document).on('change', '.input-dark', function () {
        $(this).removeClass('border-danger');
    });
});
