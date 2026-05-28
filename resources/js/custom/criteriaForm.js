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
        const placeholder = dept === false ? 'Search cast or crew…' : (dept ? 'Search actors…' : 'Search directors, writers…');
        const ts = new TomSelect('#' + id, {
            plugins: ['remove_button'],
            placeholder: placeholder,
            maxOptions: null,
            create: false,
            render: {
                option: function (data, escape) {
                    const img = data.profile
                        ? `<img src="https://image.tmdb.org/t/p/w45${escape(data.profile)}" class="w-7 h-7 rounded-full object-cover flex-shrink-0">`
                        : `<div class="w-7 h-7 rounded-full bg-white/10 flex-shrink-0"></div>`;
                    return `<div class="flex items-center gap-2 py-0.5">${img}<span>${escape(data.text)}</span></div>`;
                },
            },
        });
        let timer;
        ts.on('type', function (query) {
            clearTimeout(timer);
            if (!query) return;
            if (!ts.loading) {
                ts.loading++;
                ts.wrapper.classList.add(ts.settings.loadingClass);
                ts.clearOptions();
                ts.refreshOptions(false);
            }
            timer = setTimeout(function () {
                const params = { q: query };
                if (dept === 'Acting') params.dept = dept;
                else if (dept !== false) params.exclude_dept = 'Acting';
                $.getJSON('/tmdb/search/people', params)
                    .done(function (data) {
                        data.forEach(function (p) {
                            ts.addOption({ value: String(p.id), text: p.name, profile: p.profile_path || '' });
                        });
                    })
                    .always(function () {
                        ts.loading = Math.max(ts.loading - 1, 0);
                        if (!ts.loading) ts.wrapper.classList.remove(ts.settings.loadingClass);
                        ts.refreshOptions(true);
                    });
            }, 400);
        });
        // TomSelect v2 doesn't create <option> elements for dynamic options,
        // so form submission would miss them. Explicitly sync the underlying <select>.
        ts.on('item_add', function (value) {
            const sel = document.getElementById(id);
            if (!sel) return;
            let opt = sel.querySelector('option[value="' + value + '"]');
            if (!opt) {
                opt = document.createElement('option');
                opt.value = value;
                sel.appendChild(opt);
            }
            opt.selected = true;
        });
        ts.on('item_remove', function (value) {
            const sel = document.getElementById(id);
            if (!sel) return;
            const opt = sel.querySelector('option[value="' + value + '"]');
            if (opt) opt.remove();
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

    /* ── Origin Country ─────────────────────────────────────────── */
    initTs('with_origin_country',       { maxOptions: null, create: false });
    initTs('modal-with_origin_country', { maxOptions: null, create: false });

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

    /* ── Sub-Genre (Keywords) ───────────────────────────────────── */
    (function () {
        const el = document.getElementById('with_keywords');
        if (!el) return;
        const ts = new TomSelect('#with_keywords', {
            plugins: ['remove_button'],
            placeholder: 'Search sub-genres…',
            maxOptions: null,
            create: false,
        });
        let timer;
        ts.on('type', function (query) {
            clearTimeout(timer);
            if (!query) return;
            if (!ts.loading) {
                ts.loading++;
                ts.wrapper.classList.add(ts.settings.loadingClass);
                ts.clearOptions();
                ts.refreshOptions(false);
            }
            timer = setTimeout(function () {
                $.getJSON('/tmdb/search/keywords', { q: query })
                    .done(function (data) {
                        data.forEach(function (kw) {
                            ts.addOption({ value: String(kw.id), text: kw.name });
                        });
                    })
                    .always(function () {
                        ts.loading = Math.max(ts.loading - 1, 0);
                        if (!ts.loading) ts.wrapper.classList.remove(ts.settings.loadingClass);
                        ts.refreshOptions(true);
                    });
            }, 300);
        });
        ts.on('item_add', function (value) {
            let opt = el.querySelector('option[value="' + value + '"]');
            if (!opt) { opt = document.createElement('option'); opt.value = value; el.appendChild(opt); }
            opt.selected = true;
            const name = ts.options[value] && ts.options[value].text;
            if (name) {
                const form = el.closest('form');
                if (form) {
                    let ni = form.querySelector('input[name="with_keywords_names[]"][data-kw-id="' + value + '"]');
                    if (!ni) { ni = document.createElement('input'); ni.type = 'hidden'; ni.name = 'with_keywords_names[]'; ni.dataset.kwId = value; form.appendChild(ni); }
                    ni.value = name;
                }
            }
        });
        ts.on('item_remove', function (value) {
            const opt = el.querySelector('option[value="' + value + '"]');
            if (opt) opt.remove();
            const form = el.closest('form');
            if (form) { const ni = form.querySelector('input[name="with_keywords_names[]"][data-kw-id="' + value + '"]'); if (ni) ni.remove(); }
        });
    })();

    /* ── People ─────────────────────────────────────────────────── */
    makePeopleTs('with_cast', 'Acting');
    makePeopleTs('with_crew', null);

    $(document).on('click', '[data-modal-open="modal-form"]', function () {
        if (!window['_ts_modal-with_cast']) {
            makePeopleTs('modal-with_cast', 'Acting');
            makePeopleTs('modal-with_crew', null);
            const isTv = window.location.pathname.startsWith('/tv/');
            $.getJSON('/userinput', isTv ? { type: 'tv' } : {}, function (data) {
                if (!data) return;
                restorePeople(data['with_cast'], 'modal-with_cast');
                restorePeople(data['with_crew'], 'modal-with_crew');
            });
        }
        if (!window['_ts_modal-with_keywords']) {
            const el = document.getElementById('modal-with_keywords');
            if (el) {
                const ts = new TomSelect('#modal-with_keywords', {
                    plugins: ['remove_button'],
                    placeholder: 'Search sub-genres…',
                    maxOptions: null,
                    create: false,
                });
                let timer;
                ts.on('type', function (query) {
                    clearTimeout(timer);
                    if (!query) return;
                    if (!ts.loading) { ts.loading++; ts.wrapper.classList.add(ts.settings.loadingClass); ts.clearOptions(); ts.refreshOptions(false); }
                    timer = setTimeout(function () {
                        $.getJSON('/tmdb/search/keywords', { q: query })
                            .done(function (data) { data.forEach(function (kw) { ts.addOption({ value: String(kw.id), text: kw.name }); }); })
                            .always(function () { ts.loading = Math.max(ts.loading - 1, 0); if (!ts.loading) ts.wrapper.classList.remove(ts.settings.loadingClass); ts.refreshOptions(true); });
                    }, 300);
                });
                ts.on('item_add', function (value) {
                    let opt = el.querySelector('option[value="' + value + '"]');
                    if (!opt) { opt = document.createElement('option'); opt.value = value; el.appendChild(opt); }
                    opt.selected = true;
                    const name = ts.options[value] && ts.options[value].text;
                    if (name) {
                        const form = el.closest('form');
                        if (form) {
                            let ni = form.querySelector('input[name="with_keywords_names[]"][data-kw-id="' + value + '"]');
                            if (!ni) { ni = document.createElement('input'); ni.type = 'hidden'; ni.name = 'with_keywords_names[]'; ni.dataset.kwId = value; form.appendChild(ni); }
                            ni.value = name;
                        }
                    }
                });
                ts.on('item_remove', function (value) {
                    const opt = el.querySelector('option[value="' + value + '"]');
                    if (opt) opt.remove();
                    const form = el.closest('form');
                    if (form) { const ni = form.querySelector('input[name="with_keywords_names[]"][data-kw-id="' + value + '"]'); if (ni) ni.remove(); }
                });
                window['_ts_modal-with_keywords'] = ts;
            }
        }
    });

    /* ── Reset ──────────────────────────────────────────────────── */
    function resetForm(formId, prefix) {
        const $form = $(formId);
        $form.find('.bg-input').val('');
        ['with_genres', 'without_genres', 'with_watch_providers', 'with_cast', 'with_crew', 'with_keywords'].forEach(function (key) {
            const ts = window['_ts_' + prefix + key];
            if (ts) ts.clear(true);
        });
        const langTs = window['_ts_' + prefix + 'with_original_language'];
        if (langTs) langTs.setValue('', true);
        const countryTs = window['_ts_' + prefix + 'with_origin_country'];
        if (countryTs) countryTs.setValue('', true);
    }

    $('#btn-reset, #btn-reset-mobile').on('click', function () { resetForm('#criteria', ''); });
    $('#modal-btn-reset').on('click', function () { resetForm('#modal-criteria', 'modal-'); });

    /* ── Restore session data (criteria page only) ──────────────── */
    if ($('#criteria').length) {
        const inp = window._criteriaInput || {};
        restorePeople(inp['with_cast'], 'with_cast');
        restorePeople(inp['with_crew'], 'with_crew');
    }

    function restorePeople(ids, tsId) {
        if (!ids) return;
        const ts = window['_ts_' + tsId];
        if (!ts) return;
        const sel = document.getElementById(tsId);
        const idArr = Array.isArray(ids) ? ids : ids.split(',');
        idArr.forEach(function (personId) {
            $.getJSON('/tmdb/people/' + personId)
                .done(function (d) {
                    const val = String(d.id);
                    ts.addOption({ value: val, text: d.name });
                    ts.addItem(val, true);
                    // addItem with silent=true won't fire item_add, so manually sync the select
                    if (sel) {
                        let opt = sel.querySelector('option[value="' + val + '"]');
                        if (!opt) {
                            opt = document.createElement('option');
                            opt.value = val;
                            sel.appendChild(opt);
                        }
                        opt.selected = true;
                    }
                });
        });
    }

    /* ── Validation: remove error border on change ──────────────── */
    $(document).on('change', '.input-dark', function () {
        $(this).removeClass('border-danger');
    });
});
