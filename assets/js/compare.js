/* =====================================================================
   Compare page
   - Keeps the saved selection (tray) in sync with the games on the page
   - Writes the "Quick verdict" from the games' stored attributes
   ===================================================================== */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const page = document.querySelector('[data-compare-page]');
  if (!page) { return; }
  const games = JSON.parse(document.getElementById('compare-data').textContent);
  const params = new URLSearchParams(location.search);

  // ------------------------------------------------------------ selection sync
  if (params.has('cleared')) {
    GH.compare.clear();
  } else if (!page.dataset.ids) {
    // Opened compare.php directly: use the games selected on other pages.
    const saved = GH.compare.list();
    if (saved.length) {
      location.replace(GH.compare.url(saved));
      return;
    }
  } else {
    GH.compare.save(games.map((g) => ({ id: g.id, title: g.title })));
  }

  document.querySelectorAll('[data-replace-form] select').forEach((select) => {
    select.addEventListener('change', () => { if (select.value) { select.form.submit(); } });
  });

  // ------------------------------------------------------------ verdict
  const box = document.querySelector('[data-verdict]');
  if (!box || games.length < 2) { return; }

  const names = (list) => {
    const titles = list.map((g) => g.title);
    return titles.length <= 2 ? titles.join(' and ') : `${titles.slice(0, -1).join(', ')} and ${titles.at(-1)}`;
  };
  const traitText = {
    story: 'a stronger story',
    combat: 'more combat focus',
    exploration: 'more to explore',
    replayability: 'more replay value',
  };
  const sentences = [];
  const similar = [];

  // Story, combat, exploration and replayability
  Object.keys(traitText).forEach((key) => {
    const sorted = [...games].sort((a, b) => b.dna[key] - a.dna[key]);
    const best = sorted[0];
    const second = sorted[1];
    const worst = sorted.at(-1);
    if (best.dna[key] - worst.dna[key] < 2) {
      similar.push(key);
    } else if (best.dna[key] - second.dna[key] >= 2) {
      sentences.push(`${best.title} offers ${traitText[key]} (${key} ${best.dna[key]}/10 vs ${worst.dna[key]}/10 for ${worst.title}).`);
    } else {
      const leaders = sorted.filter((g) => best.dna[key] - g.dna[key] < 2);
      sentences.push(`${names(leaders)} both score well for ${key}, while ${worst.title} is lower (${worst.dna[key]}/10).`);
    }
  });

  // Difficulty
  const byDifficulty = [...games].sort((a, b) => b.dna.difficulty - a.dna.difficulty);
  const hardest = byDifficulty[0];
  const easiest = byDifficulty.at(-1);
  if (hardest.dna.difficulty - easiest.dna.difficulty >= 3) {
    sentences.push(`${hardest.title} is the most challenging (difficulty ${hardest.dna.difficulty}/10), while ${easiest.title} is more relaxed (${easiest.dna.difficulty}/10).`);
  } else {
    similar.push('difficulty');
  }

  // Session length
  const sessionRank = { short: 0, medium: 1, long: 2 };
  const bySession = [...games].sort((a, b) => sessionRank[a.session] - sessionRank[b.session]);
  if (bySession[0].session !== bySession.at(-1).session) {
    const shortest = bySession.filter((g) => g.session === bySession[0].session);
    sentences.push(`${names(shortest)} may suit shorter play sessions better (${GHLogic.SESSION_TEXT[bySession[0].session]}).`);
  }

  // Completion time (only when every game has an estimate)
  if (games.every((g) => g.hours)) {
    const byHours = [...games].sort((a, b) => a.hours - b.hours);
    if (byHours.at(-1).hours >= byHours[0].hours * 2) {
      sentences.push(`${byHours[0].title} takes about ${byHours[0].hours} hours to finish, compared with about ${byHours.at(-1).hours} hours for ${byHours.at(-1).title}.`);
    }
  } else {
    const openEnded = games.filter((g) => !g.hours);
    sentences.push(`${names(openEnded)} ${openEnded.length > 1 ? 'are' : 'is'} open-ended, with no fixed time to finish.`);
  }

  // Ways to play
  const coop = games.filter((g) => g.coop);
  if (coop.length && coop.length < games.length) {
    sentences.push(`Only ${names(coop)} ${coop.length > 1 ? 'support' : 'supports'} co-op.`);
  }
  const noSolo = games.filter((g) => !g.single);
  if (noSolo.length) {
    sentences.push(`${names(noSolo)} ${noSolo.length > 1 ? 'have' : 'has'} no single-player mode.`);
  }

  // Platforms in common
  const shared = Object.keys(games[0].platforms).filter((p) => games.every((g) => p in g.platforms));
  sentences.push(shared.length
    ? `Platforms in common: ${shared.map((p) => games[0].platforms[p]).join(', ')}.`
    : 'These games have no platform in common, so check that you can play the one you choose.');

  // Community ratings (only when there are enough reviews)
  if (games.every((g) => g.reviews >= 2)) {
    const byRating = [...games].sort((a, b) => b.rating - a.rating);
    if (byRating[0].rating - byRating.at(-1).rating < 0.3) {
      sentences.push('They have similar community ratings.');
    } else {
      sentences.push(`${byRating[0].title} is rated higher by the community (${byRating[0].rating.toFixed(1)} from ${byRating[0].reviews} reviews vs ${byRating.at(-1).rating.toFixed(1)} from ${byRating.at(-1).reviews}).`);
    }
  } else {
    sentences.push('There are not enough reviews yet to compare community ratings fairly.');
  }

  if (similar.length) {
    sentences.push(`Similar on: ${similar.join(', ')}.`);
  }

  // "Choose X if..." — each game's relative strength, never an overall winner.
  const picks = games.map((g) => {
    let bestKey = null;
    let bestLead = 0;
    Object.keys(traitText).forEach((key) => {
      const others = games.filter((o) => o !== g);
      const lead = g.dna[key] - Math.max(...others.map((o) => o.dna[key]));
      if (lead > bestLead) { bestLead = lead; bestKey = key; }
    });
    if (bestKey && bestLead >= 2) { return `<strong>${GH.escapeHtml(g.title)}</strong> if you want ${traitText[bestKey]}`; }
    if (g.dna.difficulty === easiest.dna.difficulty && hardest.dna.difficulty - easiest.dna.difficulty >= 3) {
      return `<strong>${GH.escapeHtml(g.title)}</strong> if you want something more relaxed`;
    }
    return null;
  }).filter(Boolean);

  box.innerHTML = `<ul>${sentences.map((s) => `<li>${GH.escapeHtml(s)}</li>`).join('')}</ul>`
    // Only shown when at least two games have a clear strength, so it never reads as one overall winner.
    + (picks.length >= 2 ? `<p class="mt-3 mb-0">Choose ${picks.join('; choose ')}.</p>` : '');
});
