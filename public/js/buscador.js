function buscador(config) {

    function navigateTo(url) { if (url) window.location.href = url; }

    const inputId = config.input.replace(/^#/, '');
    const listId  = config.list.replace(/^#/, '');

    const s = { timeout: null, focusedIndex: -1, resultItems: [] };

    function updateFocus(newIndex) {
        s.resultItems.forEach((el, i) => el.classList.toggle('active', i === newIndex));
        s.focusedIndex = newIndex;
    }

    function getList() { return document.getElementById(listId); }
    function getInput() { return document.getElementById(inputId); }

    document.addEventListener('input', function onInput(e) {
        const input = getInput();
        if (!input || e.target !== input) return;
        const list = getList();
        if (!list) return;

        const q = input.value.trim();
        s.focusedIndex = -1;
        clearTimeout(s.timeout);

        if (q.length < 1) {
            list.innerHTML = '';
            s.resultItems = [];
            var hiddenId = inputId + '-id';
            var hidden = document.getElementById(hiddenId);
            if (hidden) hidden.value = '';
            return;
        }

        s.timeout = setTimeout(async () => {
            const params = new URLSearchParams({ q });
            if (config.tipo) params.append(config.tipo, '1');

            if (typeof window.showElLoading === 'function') {
                window.showElLoading(list);
            }

            let json;
            try {
                const res = await fetch((config.url || '/buscador') + '?' + params.toString());
                json = await res.json();
            } catch (err) {
                json = { data: [] };
            }

            if (typeof window.hideElLoading === 'function') {
                window.hideElLoading(list);
            }

            list.innerHTML = '';
            s.resultItems  = [];
            s.focusedIndex = -1;

            const filtrados = json.data.filter(item => {
                if (!config.excluirTipos) return true;
                return !config.excluirTipos.includes(item.tipo);
            });

            if (filtrados.length === 0) {
                const msg = document.createElement('div');
                msg.className   = 'list-group-item text-muted fst-italic';
                msg.textContent = 'No se encontraron resultados.';
                list.appendChild(msg);
                return;
            }

            const iconos = { propiedad: '🏠', cliente: '👤', unidad: '🚪', contrato: '📄', cobro: '💰', servicio: '🔧' };

            filtrados.forEach(item => {
                const a       = document.createElement('a');
                a.href        = item.url;
                a.className   = 'list-group-item list-group-item-action';
                a.dataset.url = item.url;
                a.dataset.id  = item.id;

                a.innerHTML = (iconos[item.tipo] || '📄') + ' ' + item.texto +
                    ' <small class="text-muted">(' + item.tipo + ')</small>';

                a.addEventListener('mousedown', e => {
                    e.preventDefault();
                    if (typeof config.onSelect === 'function') {
                        config.onSelect(item);
                        list.innerHTML = '';
                        s.resultItems  = [];
                    } else {
                        navigateTo(item.url);
                    }
                });

                list.appendChild(a);
                s.resultItems.push(a);
            });

        }, config.delay || 200);
    });

    document.addEventListener('keydown', function onKeydown(e) {
        const input = getInput();
        if (!input || e.target !== input) return;
        const list = getList();
        if (!list) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (s.resultItems.length) updateFocus(Math.min(s.focusedIndex + 1, s.resultItems.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (s.resultItems.length) updateFocus(Math.max(s.focusedIndex - 1, 0));
        } else if (e.key === 'Tab') {
            if (!s.resultItems.length) return;
            e.preventDefault();
            updateFocus(s.focusedIndex === -1 ? 0 : Math.min(s.focusedIndex + 1, s.resultItems.length - 1));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const target = s.focusedIndex >= 0
                ? s.resultItems[s.focusedIndex]
                : (s.resultItems.length === 1 ? s.resultItems[0] : null);
            if (target) {
                if (typeof config.onSelect === 'function') {
                    config.onSelect({ url: target.dataset.url, texto: target.textContent.trim(), id: target.dataset.id });
                    list.innerHTML = '';
                    s.resultItems  = [];
                } else {
                    navigateTo(target.dataset.url);
                }
            }
        } else if (e.key === 'Escape') {
            list.innerHTML = '';
            s.resultItems  = [];
            s.focusedIndex = -1;
        }
    });

    document.addEventListener('click', function onClick(e) {
        const input = getInput();
        const list  = getList();
        if (!input || !list) return;
        if (!input.contains(e.target) && !list.contains(e.target)) {
            list.innerHTML = '';
            s.resultItems  = [];
            s.focusedIndex = -1;
        }
    });
}
