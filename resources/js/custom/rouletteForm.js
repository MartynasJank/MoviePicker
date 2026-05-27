import TomSelect from 'tom-select';

$(document).ready(function () {

    // ── People TomSelect ──────────────────────────────────────────────

    function makePeopleTs(id, dept) {
        const el = document.getElementById(id);
        if (!el) return;
        const placeholder = dept === 'Acting' ? 'Search actors…' : 'Search directors, writers…';
        const ts = new TomSelect('#' + id, {
            plugins: ['remove_button'],
            placeholder,
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
                else params.exclude_dept = 'Acting';
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

        // Sync underlying <select> on add/remove (TomSelect v2 doesn't do this for dynamic options)
        ts.on('item_add', function (value) {
            const sel = document.getElementById(id);
            if (!sel) return;
            let opt = sel.querySelector('option[value="' + value + '"]');
            if (!opt) { opt = document.createElement('option'); opt.value = value; sel.appendChild(opt); }
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

    function restorePeople(ids, tsId) {
        if (!ids || !ids.length) return;
        const ts  = window['_ts_' + tsId];
        const sel = document.getElementById(tsId);
        if (!ts) return;
        ids.forEach(function (personId) {
            $.getJSON('/tmdb/people/' + personId)
                .done(function (d) {
                    const val = String(d.id);
                    ts.addOption({ value: val, text: d.name });
                    ts.addItem(val, true);
                    if (sel) {
                        let opt = sel.querySelector('option[value="' + val + '"]');
                        if (!opt) { opt = document.createElement('option'); opt.value = val; sel.appendChild(opt); }
                        opt.selected = true;
                    }
                });
        });
    }

    makePeopleTs('roulette-with_cast', 'Acting');
    makePeopleTs('roulette-with_crew', null);

    // Restore existing values when editing a roulette
    restorePeople(window._rouletteWithCast || [], 'roulette-with_cast');
    restorePeople(window._rouletteWithCrew || [], 'roulette-with_crew');
});
