document.addEventListener('DOMContentLoaded', async () => {
    const puzzleEl   = document.getElementById('puzzle');
    const statusEl   = document.getElementById('puzzleStatus');
    const okInput    = document.getElementById('captchaOk');
    const checkBtn   = document.getElementById('checkPuzzleBtn');
    const reloadBtn  = document.getElementById('reloadPuzzleBtn');
    const loginForm  = document.getElementById('loginForm');

    if (!puzzleEl || !checkBtn) return;

    const BASE      = window.BASE_URL || '';
    const csrfMeta  = document.querySelector('meta[name="csrf-token"]');
    let csrf        = csrfMeta ? csrfMeta.getAttribute('content') : '';
    let puzzleToken = '';
    let solved      = false;

    // ---------- Отрисовка пазла ----------
    async function loadPuzzle() {
        solved = false;
        okInput.value = '0';
        checkBtn.disabled = false;
        checkBtn.textContent = 'Проверить пазл';
        statusEl.textContent = '';
        statusEl.className = 'puzzle-status';
        puzzleEl.classList.remove('solved');
        puzzleEl.innerHTML = '';

        try {
            const resp = await fetch(BASE + '/captcha/get_puzzle.php', { cache: 'no-store' });
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            const data = await resp.json();
            if (!data.ok) throw new Error(data.error || 'Ошибка сервера');

            puzzleToken = data.token;
            csrf = data.csrf || csrf;

            const base = data.baseUrl;
            const ext  = '.jpg';

            data.parts.forEach((partNum) => {
                const tile = document.createElement('div');
                tile.className = 'puzzle-tile';
                tile.dataset.part = partNum;
                tile.style.backgroundImage = `url(${base}${partNum}${ext})`;
                puzzleEl.appendChild(tile);
            });

            // Предзагрузка картинок
            data.parts.forEach(n => { const i = new Image(); i.src = `${base}${n}${ext}`; });

        } catch (err) {
            console.error('Пазл не загрузился:', err);
            statusEl.textContent = 'Ошибка загрузки капчи. Обновите страницу.';
            statusEl.className = 'puzzle-status err';
        }
    }

    // ---------- Перетаскивание (работает и мышкой, и пальцем) ----------
    let dragged = null;

    puzzleEl.addEventListener('pointerdown', e => {
        const tile = e.target.closest('.puzzle-tile');
        if (!tile) return;
        e.preventDefault(); // запрет выделения текста и скролла
        dragged = tile;
        tile.classList.add('dragging');
        try { tile.setPointerCapture(e.pointerId); } catch (_) {}
    }, { passive: false });

    window.addEventListener('pointermove', e => {
        if (!dragged) return;
        e.preventDefault();
        // Определяем тайл под курсором, даже если он за пределами puzzleEl
        const target = document.elementFromPoint(e.clientX, e.clientY)?.closest('.puzzle-tile');
        if (!target || target === dragged) return;
        const nodes = [...puzzleEl.children];
        const from = nodes.indexOf(dragged);
        const to   = nodes.indexOf(target);
        if (from === -1 || to === -1) return;
        if (from < to) puzzleEl.insertBefore(dragged, target.nextSibling);
        else           puzzleEl.insertBefore(dragged, target);
    }, { passive: false });

    window.addEventListener('pointerup', () => {
        if (dragged) {
            dragged.classList.remove('dragging');
            dragged = null;
        }
    });
    window.addEventListener('pointercancel', () => {
        if (dragged) {
            dragged.classList.remove('dragging');
            dragged = null;
        }
    });

    // ---------- Проверить пазл ----------
    checkBtn.addEventListener('click', async () => {
        if (solved) return;
        checkBtn.disabled = true;
        const order = [...puzzleEl.children].map(t => t.dataset.part).join(',');
        const fd = new FormData();
        fd.append('token', puzzleToken);
        fd.append('order', order);
        fd.append('csrf', csrf);

        try {
            const r = await fetch(BASE + '/captcha/check_puzzle.php', {
                method: 'POST',
                body: fd,
                cache: 'no-store',
            });
            const res = await r.json();

            if (res.ok) {
                solved = true;
                okInput.value = '1';
                statusEl.textContent = res.message || '✔ Капча пройдена';
                statusEl.className = 'puzzle-status ok';
                puzzleEl.classList.add('solved');
                checkBtn.textContent = 'Пройдено';
                checkBtn.disabled = true;
            } else {
                okInput.value = '0';
                statusEl.textContent = '✖ ' + (res.error || 'Собрано неверно, попробуйте ещё раз');
                statusEl.className = 'puzzle-status err';
                checkBtn.disabled = false;
            }
        } catch (err) {
            console.error('Ошибка проверки капчи:', err);
            statusEl.textContent = 'Ошибка проверки капчи. Попробуйте позже.';
            statusEl.className = 'puzzle-status err';
            checkBtn.disabled = false;
        }
    });

    // ---------- Перезагрузить пазл ----------
    if (reloadBtn) {
        reloadBtn.addEventListener('click', () => {
            loadPuzzle();
        });
    }

    // ---------- Запрет отправки формы без пройденной капчи ----------
    if (loginForm) {
        loginForm.addEventListener('submit', e => {
            if (okInput.value !== '1') {
                e.preventDefault();
                statusEl.textContent = '✖ Сначала пройдите проверку «Я не робот»';
                statusEl.className = 'puzzle-status err';
                return false;
            }
        });
    }

    loadPuzzle();
});