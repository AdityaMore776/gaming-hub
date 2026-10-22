/* Game details page: "You might like this game because" and "Consider this before playing". */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const dataEl = document.getElementById('game-data');
  if (!dataEl) { return; }
  const data = JSON.parse(dataEl.textContent);
  const game = data.game;
  const names = { genres: data.genres, platforms: data.platforms };

  // Logged-in users: answers saved in MySQL. Guests: answers from this browser tab.
  let prefs = data.prefs;
  let source = 'account';
  if (!GHLogic.hasAnswers(prefs)) {
    prefs = GHLogic.loadGuestPrefs();
    source = 'browser';
  }
  const personal = GHLogic.hasAnswers(prefs);

  let why;
  let consider;
  if (personal) {
    const result = GHLogic.evaluate(game, prefs, names);
    why = GHLogic.reasons(game, result, 4);
    consider = GHLogic.drawbacks(game, result, 4);

    const badge = document.querySelector('[data-match-badge]');
    if (badge && result.percent !== null) {
      badge.textContent = `${result.percent}% match with your quiz answers`;
      badge.hidden = false;
    }
  } else {
    why = GHLogic.strengths(game).slice(0, 4);
    consider = GHLogic.considerations(game).slice(0, 4);
  }

  const check = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>';
  const warn = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.5"/></svg>';
  const fill = (selector, items, iconSvg, emptyText) => {
    const list = document.querySelector(selector);
    if (!list) { return; }
    list.innerHTML = (items.length ? items : [emptyText])
      .map((text) => `<li>${iconSvg}<span>${GH.escapeHtml(text)}</span></li>`).join('');
  };
  fill('[data-why-list]', why, check, 'It is a solid all-rounder without one standout trait. The reviews below can help you decide.');
  fill('[data-consider-list]', consider, warn, 'Nothing stands out for your answers. Check the reviews to see what players think.');

  const note = document.querySelector('[data-why-source]');
  if (note) {
    const quizUrl = GH.url('recommend.php');
    note.innerHTML = personal
      ? (source === 'account'
        ? `Based on this game's Game DNA and the quiz answers saved to your account. <a href="${quizUrl}">Retake the quiz</a>.`
        : `Based on this game's Game DNA and the quiz answers you gave in this browser tab. <a href="${quizUrl}">Retake the quiz</a>.`)
      : `Based on this game's Game DNA. <a href="${quizUrl}">Take the quiz</a> to get reasons tailored to you.`;
  }
});
