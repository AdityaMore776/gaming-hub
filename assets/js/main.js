/* =====================================================================
   Gaming Hub — shared JavaScript (loaded on every page)
   - Comparison selection (saved in localStorage so it survives page changes)
   - Wishlist buttons (saved in MySQL through actions/wishlist.php)
   - Toast messages, login prompt, confirm dialogs
   - Live filtering on the Explore page
   ===================================================================== */
'use strict';

const GH = (() => {
  const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content ?? '';
  const basePath = meta('base-path');
  const csrfToken = meta('csrf-token');
  const loggedIn = meta('logged-in') === '1';

  const url = (path) => `${basePath}/${path.replace(/^\//, '')}`;

  const escapeHtml = (text) => String(text).replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]));

  // ----------------------------------------------------------- toasts
  function toast(message, type = 'info') {
    const box = document.querySelector('[data-toasts]');
    if (!box || !window.bootstrap) { return; }
    const el = document.createElement('div');
    el.className = `toast align-items-center toast--${type}`;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.innerHTML = `<div class="d-flex"><div class="toast-body">${escapeHtml(message)}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
    box.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 3200 });
    el.addEventListener('hidden.bs.toast', () => el.remove());
    t.show();
  }

  function showLoginPrompt() {
    const next = encodeURIComponent(location.pathname + location.search);
    document.querySelectorAll('[data-login-link]').forEach((a) => {
      const base = a.getAttribute('href').split('?')[0];
      a.setAttribute('href', `${base}?next=${next}`);
    });
    const modal = document.getElementById('loginPrompt');
    if (modal && window.bootstrap) {
      bootstrap.Modal.getOrCreateInstance(modal).show();
    } else {
      location.href = url(`login.php?next=${next}`);
    }
  }

  // ----------------------------------------------------------- comparison store
  const COMPARE_KEY = 'gh_compare';
  const COMPARE_MAX = 3;

  const compare = {
    list() {
      try {
        const items = JSON.parse(localStorage.getItem(COMPARE_KEY) || '[]');
        return Array.isArray(items) ? items.filter((i) => Number.isInteger(i.id)).slice(0, COMPARE_MAX) : [];
      } catch (e) {
        return [];
      }
    },
    save(items) {
      try { localStorage.setItem(COMPARE_KEY, JSON.stringify(items.slice(0, COMPARE_MAX))); } catch (e) { /* storage blocked */ }
      renderCompareUi();
    },
    has(id) { return this.list().some((i) => i.id === id); },
    add(id, title) {
      const items = this.list();
      if (items.some((i) => i.id === id)) { return 'duplicate'; }
      if (items.length >= COMPARE_MAX) { return 'full'; }
      items.push({ id, title });
      this.save(items);
      return 'added';
    },
    remove(id) { this.save(this.list().filter((i) => i.id !== id)); },
    clear() { this.save([]); },
    url(items = this.list()) {
      return items.length ? url(`compare.php?ids=${items.map((i) => i.id).join(',')}`) : url('compare.php');
    },
  };

  function renderCompareUi() {
    const items = compare.list();

    document.querySelectorAll('[data-compare-count]').forEach((el) => {
      el.textContent = items.length;
      el.hidden = items.length === 0;
    });

    document.querySelectorAll('[data-compare-toggle]').forEach((btn) => {
      const active = items.some((i) => i.id === Number(btn.dataset.id));
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-pressed', active ? 'true' : 'false');
      btn.title = active ? 'Remove from comparison' : 'Add to comparison';
      const label = btn.querySelector('.compare-btn__label');
      if (label) { label.textContent = active ? 'Comparing' : 'Compare'; }
    });

    // Navbar link goes straight to the selected games.
    document.querySelectorAll('.site-nav a[href$="compare.php"], .site-nav a[href*="compare.php?"]').forEach((a) => {
      a.setAttribute('href', compare.url(items));
    });

    const tray = document.querySelector('[data-compare-tray]');
    if (!tray) { return; }
    const onComparePage = document.body.dataset.page === 'compare';
    tray.hidden = items.length === 0 || onComparePage;
    document.body.classList.toggle('has-tray', !tray.hidden);

    const list = tray.querySelector('[data-compare-items]');
    list.innerHTML = '';
    items.forEach((item) => {
      const li = document.createElement('li');
      li.innerHTML = `<span>${escapeHtml(item.title)}</span>
        <button type="button" data-compare-remove="${item.id}" aria-label="Remove ${escapeHtml(item.title)} from comparison">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>`;
      list.appendChild(li);
    });
    for (let i = items.length; i < COMPARE_MAX; i++) {
      const li = document.createElement('li');
      li.className = 'is-empty';
      li.textContent = 'Empty slot';
      list.appendChild(li);
    }

    const go = tray.querySelector('[data-compare-go]');
    go.href = compare.url(items);
    go.classList.toggle('disabled', items.length < 2);
    go.setAttribute('aria-disabled', items.length < 2 ? 'true' : 'false');
    tray.querySelector('[data-compare-hint]').textContent = items.length < 2
      ? `Select ${2 - items.length} more to compare`
      : `${items.length} of ${COMPARE_MAX} selected`;
  }

  // ----------------------------------------------------------- fetch helper
  async function postForm(action, formData) {
    const response = await fetch(action, {
      method: 'POST',
      body: formData,
      headers: { Accept: 'application/json', 'X-CSRF-Token': csrfToken },
      credentials: 'same-origin',
    });
    let data = {};
    try { data = await response.json(); } catch (e) { data = { ok: false, message: 'Unexpected server response.' }; }
    if (response.status === 401) { data.login = true; }
    return data;
  }

  // ----------------------------------------------------------- wishlist
  // Note: these forms contain an input named "action", which hides form.action — so use getAttribute('action').
  async function handleWishlistSubmit(form) {
    if (!loggedIn) { showLoginPrompt(); return; }
    const button = form.querySelector('button');
    button.disabled = true;
    try {
      const data = await postForm(form.getAttribute('action'), new FormData(form));
      if (data.login) { showLoginPrompt(); return; }
      if (!data.ok) { toast(data.message || 'Could not update your wishlist.', 'error'); return; }

      const gameId = form.querySelector('[name="game_id"]').value;
      document.querySelectorAll('form[data-wishlist-form]').forEach((f) => {
        if (f.querySelector('[name="game_id"]').value !== gameId) { return; }
        f.querySelector('[name="action"]').value = data.saved ? 'remove' : 'add';
        const b = f.querySelector('button');
        b.classList.toggle('is-active', data.saved);
        b.setAttribute('aria-pressed', data.saved ? 'true' : 'false');
        b.title = data.saved ? 'Remove from wishlist' : 'Add to wishlist';
        const label = b.querySelector('.wish-btn__label');
        if (label) { label.textContent = data.saved ? 'Saved' : 'Wishlist'; }
      });
      toast(data.message, 'success');

      // On the wishlist page, removing a game removes its card.
      const page = document.querySelector('[data-wishlist-page]');
      if (page && !data.saved) {
        const card = page.querySelector(`[data-game-id="${gameId}"]`);
        card?.closest('[data-wishlist-item]')?.remove();
        const left = page.querySelectorAll('[data-wishlist-item]').length;
        const counter = document.querySelector('[data-wishlist-count]');
        if (counter) { counter.textContent = left === 1 ? '1 game' : `${left} games`; }
        if (left === 0) { location.reload(); }
      }
    } catch (e) {
      toast('Network error — please try again.', 'error');
    } finally {
      button.disabled = false;
    }
  }

  // ----------------------------------------------------------- explorer live filtering
  function initExplorer() {
    const form = document.querySelector('[data-explore-form]');
    const results = document.querySelector('[data-results]');
    if (!form || !results) { return; }
    let timer = null;
    let controller = null;

    async function load(targetUrl) {
      const u = new URL(targetUrl, location.href);
      u.searchParams.set('partial', '1');
      controller?.abort();
      controller = new AbortController();
      results.classList.add('is-loading');
      try {
        const response = await fetch(u, { signal: controller.signal, headers: { Accept: 'text/html' } });
        if (!response.ok) { throw new Error(String(response.status)); }
        results.innerHTML = await response.text();
        u.searchParams.delete('partial');
        history.replaceState(null, '', u.pathname + u.search);
        syncFormFromUrl(u);
        renderCompareUi();
      } catch (e) {
        if (e.name !== 'AbortError') { form.submit(); } // fall back to a normal page load
      } finally {
        results.classList.remove('is-loading');
      }
    }

    function formUrl() {
      const params = new URLSearchParams();
      new FormData(form).forEach((value, key) => { if (value !== '') { params.append(key, value); } });
      return `${form.getAttribute('action')}${params.toString() ? '?' + params : ''}`;
    }

    // Keep the form in sync when a chip removes a filter.
    function syncFormFromUrl(u) {
      form.querySelectorAll('input[name], select[name]').forEach((field) => {
        const value = u.searchParams.get(field.name) ?? '';
        if (field.type === 'radio' || field.type === 'checkbox') {
          field.checked = field.value === value;
        } else if (document.activeElement !== field) {
          field.value = value || (field.name === 'sort' ? 'rating' : '');
        }
      });
    }

    form.addEventListener('submit', (event) => { event.preventDefault(); load(formUrl()); });
    form.addEventListener('change', (event) => {
      if (event.target.name !== 'q') { load(formUrl()); }
    });
    form.addEventListener('input', (event) => {
      if (event.target.name !== 'q') { return; }
      clearTimeout(timer);
      timer = setTimeout(() => load(formUrl()), 350);
    });
    form.querySelector('[data-clear-filters]')?.addEventListener('click', (event) => {
      event.preventDefault();
      form.reset();
      form.querySelectorAll('input[type="search"], input[type="text"]').forEach((i) => { i.value = ''; });
      form.querySelectorAll('select').forEach((s) => { s.value = s.name === 'sort' ? 'rating' : ''; });
      load(form.getAttribute('action'));
    });
    results.addEventListener('click', (event) => {
      const link = event.target.closest('a[data-page-link], a[data-filter-link]');
      if (!link) { return; }
      event.preventDefault();
      load(link.href);
      if (link.matches('[data-page-link]')) { form.closest('main')?.scrollIntoView({ behavior: 'smooth' }); }
    });
  }

  // ----------------------------------------------------------- event wiring
  document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-compare-toggle]');
    if (toggle) {
      const id = Number(toggle.dataset.id);
      if (compare.has(id)) {
        compare.remove(id);
        toast(`Removed ${toggle.dataset.title} from comparison.`);
      } else {
        const result = compare.add(id, toggle.dataset.title);
        if (result === 'full') {
          toast(`You can compare up to ${COMPARE_MAX} games. Remove one from the tray first.`, 'error');
        } else if (result === 'added') {
          toast(`Added ${toggle.dataset.title} to comparison.`, 'success');
        }
      }
      return;
    }
    const remove = event.target.closest('[data-compare-remove]');
    if (remove) { compare.remove(Number(remove.dataset.compareRemove)); return; }
    if (event.target.closest('[data-compare-clear]')) { compare.clear(); return; }
    const go = event.target.closest('[data-compare-go].disabled');
    if (go) { event.preventDefault(); toast('Select at least two games to compare.'); }
  });

  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (form.matches('[data-confirm]') && !window.confirm(form.dataset.confirm)) {
      event.preventDefault();
      return;
    }
    if (form.matches('[data-wishlist-form]')) {
      event.preventDefault();
      handleWishlistSubmit(form);
    }
  });

  // Close an open "Report" box when clicking elsewhere.
  document.addEventListener('click', (event) => {
    document.querySelectorAll('details.report[open]').forEach((d) => {
      if (!d.contains(event.target)) { d.removeAttribute('open'); }
    });
  });

  // Keep several tabs in sync.
  window.addEventListener('storage', (event) => { if (event.key === COMPARE_KEY) { renderCompareUi(); } });

  document.addEventListener('DOMContentLoaded', () => {
    renderCompareUi();
    initExplorer();
  });

  return { url, csrfToken, loggedIn, toast, escapeHtml, compare, renderCompareUi, postForm, showLoginPrompt };
})();
