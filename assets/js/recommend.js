/* =====================================================================
   "What Should I Play?" quiz
   Steps through the questions, then ranks every game with GHLogic.
   ===================================================================== */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const dataEl = document.getElementById('quiz-data');
  if (!dataEl) { return; }
  const data = JSON.parse(dataEl.textContent);
  const names = { genres: data.genres, platforms: data.platforms };
  const keys = data.steps.map((s) => s.key);

  const intro = document.querySelector('[data-quiz-intro]');
  const form = document.querySelector('[data-quiz-form]');
  const results = document.querySelector('[data-quiz-results]');
  const steps = [...form.querySelectorAll('.quiz-step')];
  const backBtn = form.querySelector('[data-quiz-back]');
  const nextBtn = form.querySelector('[data-quiz-next]');
  let current = 0;
  let lastPrefs = null;

  const esc = GH.escapeHtml;
  const show = (el, visible) => { el.hidden = !visible; };

  // ------------------------------------------------------------ answers
  function readPrefs() {
    const prefs = {};
    keys.forEach((key) => {
      const checked = form.querySelector(`input[name="${key}"]:checked`);
      prefs[key] = checked && checked.value !== '' ? checked.value : null;
    });
    return prefs;
  }

  function setPrefs(prefs) {
    keys.forEach((key) => {
      const value = prefs?.[key] ?? '';
      const input = form.querySelector(`input[name="${key}"][value="${CSS.escape(value)}"]`);
      if (input) { input.checked = true; }
    });
  }

  function isAnswered(index) {
    return !!form.querySelector(`input[name="${keys[index]}"]:checked`);
  }

  // ------------------------------------------------------------ navigation
  function showStep(index) {
    current = index;
    steps.forEach((step, i) => step.classList.toggle('is-active', i === index));
    form.querySelector('[data-quiz-counter]').textContent = `Question ${index + 1} of ${steps.length}`;
    form.querySelector('[data-quiz-bar]').style.width = `${(index / steps.length) * 100}%`;
    nextBtn.innerHTML = index === steps.length - 1 ? 'See my matches' : 'Next →';
    steps[index].querySelector('h2')?.focus?.();
  }

  function startQuiz() {
    show(intro, false);
    show(results, false);
    show(form, true);
    showStep(0);
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function next() {
    if (!isAnswered(current)) {
      GH.toast('Choose an answer, or pick the “no preference” option.');
      return;
    }
    if (current === steps.length - 1) {
      finish(readPrefs());
    } else {
      showStep(current + 1);
    }
  }

  backBtn.addEventListener('click', () => {
    if (current === 0) {
      show(form, false);
      show(intro, true);
    } else {
      showStep(current - 1);
    }
  });
  nextBtn.addEventListener('click', next);
  form.addEventListener('change', (event) => {
    if (event.target.type === 'radio') { setTimeout(next, 220); }
  });

  // ------------------------------------------------------------ results
  function describePrefs(prefs) {
    const parts = [];
    data.steps.forEach((step) => {
      const value = prefs[step.key];
      if (value) { parts.push(step.options[value]); }
    });
    return parts.join(', ');
  }

  function wishlistForm(game) {
    const saved = data.wishlist.includes(game.id);
    return `<form method="post" action="${GH.url('actions/wishlist.php')}" class="d-inline" data-wishlist-form>
      <input type="hidden" name="csrf_token" value="${esc(GH.csrfToken)}">
      <input type="hidden" name="game_id" value="${game.id}">
      <input type="hidden" name="action" value="${saved ? 'remove' : 'add'}">
      <button type="submit" class="btn btn-sm btn-icon wish-btn${saved ? ' is-active' : ''}" data-label="1"
              aria-pressed="${saved}" title="${saved ? 'Remove from wishlist' : 'Add to wishlist'}">
        <svg class="icon icon-outline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 20s-7-4.4-9.2-8.6C1.3 8.4 3 5 6.3 5c2 0 3.2 1 3.7 2 .5-1 1.7-2 3.7-2C17 5 18.7 8.4 17.2 11.4 15 15.6 12 20 12 20z" transform="translate(2 0)"/></svg>
        <svg class="icon icon-filled" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 20s-7-4.4-9.2-8.6C1.3 8.4 3 5 6.3 5c2 0 3.2 1 3.7 2 .5-1 1.7-2 3.7-2C17 5 18.7 8.4 17.2 11.4 15 15.6 12 20 12 20z" transform="translate(2 0)"/></svg>
        <span class="wish-btn__label">${saved ? 'Saved' : 'Wishlist'}</span>
      </button></form>`;
  }

  function dnaBars(game) {
    const labels = { story: 'Story', combat: 'Combat', exploration: 'Exploration', difficulty: 'Difficulty', replayability: 'Replayability' };
    return `<ul class="dna dna--compact">${Object.entries(labels).map(([key, label]) => `
      <li class="dna__row dna--${key}"><span class="dna__label">${label}</span>
        <span class="dna__track" role="img" aria-label="${label} ${game.dna[key]} out of 10"><span class="dna__fill" style="width:${game.dna[key] * 10}%"></span></span>
        <span class="dna__value">${game.dna[key]}</span></li>`).join('')}</ul>`;
  }

  function resultCard({ game, result }, index, personal) {
    const why = GHLogic.reasons(game, result, 3);
    const drawback = GHLogic.drawbacks(game, result, 1)[0] || 'No obvious drawback for your answers.';
    const percent = result.percent ?? 0;
    const cover = `<div class="cover"><img src="${esc(game.cover)}" alt="Cover art for ${esc(game.title)}" loading="lazy">${
      game.customCover ? '' : `<span class="cover__title">${esc(game.title)}</span>`}</div>`;
    return `<article class="result-card${index === 0 ? ' result-card--top' : ''}" data-game-id="${game.id}">
      <a href="${esc(game.url)}" tabindex="-1" aria-hidden="true">${cover}</a>
      <div>
        <div class="result-rank">#${index + 1} match</div>
        <h3><a class="text-white text-decoration-none" href="${esc(game.url)}">${esc(game.title)}</a></h3>
        <div class="small text-soft">${esc(Object.values(game.genres).join(' · '))} · ${game.year} · ${esc(Object.values(game.platforms).join(', '))}</div>
        ${personal ? `<div class="match-meter">
          <span class="match-meter__value">${percent}%</span>
          <span class="match-meter__bar" aria-hidden="true"><span style="width:${percent}%"></span></span>
          <span class="small text-soft">compatibility</span></div>` : '<div class="mb-3"></div>'}
        <div class="row g-3">
          <div class="col-md-6">${dnaBars(game)}</div>
          <div class="col-md-6">
            <h4 class="h6 mb-2">${personal ? 'Why this matches you' : 'Why it stands out'}</h4>
            <ul class="why-list">${why.map((w) => `<li><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg><span>${esc(w)}</span></li>`).join('')}</ul>
          </div>
        </div>
        <div class="drawback"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.5"/></svg>
          <span><strong>Keep in mind:</strong> ${esc(drawback)}</span></div>
        <div class="d-flex flex-wrap gap-2 mt-3">
          <a class="btn btn-sm btn-ghost" href="${esc(game.url)}">View details</a>
          ${wishlistForm(game)}
          <button type="button" class="btn btn-sm compare-btn" data-compare-toggle data-id="${game.id}" data-title="${esc(game.title)}" aria-pressed="false">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="7" height="16" rx="1.5"/><rect x="14" y="4" width="7" height="16" rx="1.5"/></svg>
            <span class="compare-btn__label">Compare</span></button>
        </div>
      </div>
    </article>`;
  }

  function finish(prefs, note = '') {
    lastPrefs = prefs;
    GHLogic.saveGuestPrefs(prefs); // lets game pages explain matches for this browser tab
    const personal = GHLogic.hasAnswers(prefs);
    const ranked = personal
      ? GHLogic.rank(data.games, prefs, names)
      : [...data.games].sort((a, b) => (b.rating - a.rating) || (b.reviews - a.reviews))
        .map((game) => ({ game, result: { percent: null, parts: [] } }));

    const summary = results.querySelector('[data-results-summary]');
    summary.textContent = personal
      ? `${note}Based on: ${describePrefs(prefs)}.`
      : 'You chose “no preference” for every question, so these are simply the highest-rated games in our community.';

    results.querySelector('[data-results-list]').innerHTML = ranked.slice(0, 3)
      .map((entry, i) => resultCard(entry, i, personal)).join('');
    results.querySelector('[data-more-list]').innerHTML = ranked.slice(3, 10).map(({ game, result }) => `
      <li class="mb-1"><a href="${esc(game.url)}">${esc(game.title)}</a>
        ${result.percent !== null ? `<span class="text-soft"> · ${result.percent}% match</span>` : ''}</li>`).join('');

    show(intro, false);
    show(form, false);
    show(results, true);
    GH.renderCompareUi();
    results.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  // ------------------------------------------------------------ buttons
  intro.querySelector('[data-quiz-start]').addEventListener('click', startQuiz);

  intro.querySelector('[data-quiz-surprise]').addEventListener('click', () => {
    const prefs = {};
    data.steps.forEach((step) => {
      const values = Object.keys(step.options);
      prefs[step.key] = values[Math.floor(Math.random() * values.length)];
    });
    setPrefs(prefs);
    finish(prefs, 'Surprise! We picked random answers. ');
  });

  const saved = GHLogic.hasAnswers(data.saved) ? data.saved : GHLogic.loadGuestPrefs();
  if (GHLogic.hasAnswers(saved)) {
    setPrefs(saved);
    const useSaved = intro.querySelector('[data-quiz-use-saved]');
    const savedNote = intro.querySelector('[data-quiz-saved-note]');
    useSaved.hidden = false;
    savedNote.hidden = false;
    savedNote.textContent = `${data.saved ? 'Saved to your account' : 'From earlier in this tab'}: ${describePrefs(saved)}.`;
    useSaved.addEventListener('click', () => finish(saved));
  }

  results.querySelector('[data-quiz-restart]').addEventListener('click', startQuiz);

  results.querySelector('[data-quiz-login]')?.addEventListener('click', () => GH.showLoginPrompt());

  results.querySelector('[data-quiz-save]')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const body = new FormData();
    body.append('csrf_token', GH.csrfToken);
    body.append('action', 'save');
    keys.forEach((key) => body.append(key, lastPrefs?.[key] ?? ''));
    button.disabled = true;
    try {
      const reply = await GH.postForm(GH.url('actions/preferences.php'), body);
      GH.toast(reply.message || (reply.ok ? 'Preferences saved.' : 'Could not save your preferences.'), reply.ok ? 'success' : 'error');
      if (reply.ok) { data.saved = lastPrefs; }
    } catch (e) {
      GH.toast('Network error. Please try again.', 'error');
    } finally {
      button.disabled = false;
    }
  });
});
