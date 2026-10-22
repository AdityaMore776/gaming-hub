/* =====================================================================
   Gaming Hub — recommendation and explanation rules
   Used by the quiz (recommend.js) and the game details page (game.js).

   This is a transparent, rule-based weighted score — not AI.
   Each answered question gives a game a fraction between 0 and 1.
   The match % = sum(weight × fraction) / sum(weights of answered questions).
   Questions answered "Any" are left out, so the score is normalised to 100.
   ===================================================================== */
'use strict';

const GHLogic = (() => {
  const WEIGHTS = {
    genre: 20,       // preferred genre
    mood: 15,        // current mood
    play_style: 15,  // solo / co-op / multiplayer
    difficulty: 15,  // preferred difficulty
    session: 10,     // available gaming time
    focus: 15,       // story / combat / exploration emphasis
    platform: 10,    // platform
  }; // total = 100

  const DIFFICULTY_TARGET = { relaxed: 2, balanced: 5, challenging: 8.5 };
  const SESSION_FIT = {
    short: { short: 1, medium: 0.5, long: 0 },
    medium: { short: 0.8, medium: 1, long: 0.5 },
    long: { short: 0.5, medium: 0.8, long: 1 },
  };
  const SESSION_TEXT = {
    short: 'short sessions (under an hour)',
    medium: 'sessions of 1–2 hours',
    long: 'long sessions (2+ hours)',
  };
  const FOCUS_NAME = { story: 'Story', combat: 'Combat', exploration: 'Exploration' };

  const clamp = (n) => Math.max(0, Math.min(1, n));
  const article = (word) => (/^(rpg|[aeiou])/i.test(word) ? 'an' : 'a');

  function difficultyLabel(d) {
    if (d <= 3) { return 'relaxed'; }
    if (d <= 6) { return 'moderate'; }
    if (d <= 8) { return 'hard'; }
    return 'brutal';
  }

  function hasAnswers(prefs) {
    return !!prefs && Object.keys(WEIGHTS).some((k) => prefs[k]);
  }

  /**
   * Score one game against the answers.
   * Returns every criterion with its fraction and an explanation.
   */
  function evaluate(game, prefs, names) {
    const dna = game.dna;
    const parts = [];
    const add = (key, fraction, good, bad, trait = null) => parts.push({ key, weight: WEIGHTS[key], fraction: clamp(fraction), good, bad, trait });

    if (prefs.genre) {
      const name = names.genres[prefs.genre] || prefs.genre;
      const has = Object.prototype.hasOwnProperty.call(game.genres, prefs.genre);
      add('genre', has ? 1 : 0,
        `It's ${article(name)} ${name} game, the genre you picked.`,
        `It isn't ${article(name)} ${name} game (it's ${Object.values(game.genres).join(' / ')}).`);
    }

    switch (prefs.mood) {
      case 'adventure':
        add('mood', dna.exploration / 10,
          `Lots to explore (exploration ${dna.exploration}/10), good for an adventure.`,
          `Not much exploration (${dna.exploration}/10) for an adventure mood.`, 'exploration');
        break;
      case 'story':
        add('mood', dna.story / 10,
          `Story-driven (story ${dna.story}/10), as you were in the mood for.`,
          `The story is light (${dna.story}/10).`, 'story');
        break;
      case 'challenge':
        add('mood', dna.difficulty / 10,
          `A real challenge: difficulty ${dna.difficulty}/10.`,
          `Probably not challenging enough (difficulty ${dna.difficulty}/10).`);
        break;
      case 'relax':
        add('mood', (20 - dna.difficulty - dna.combat) / 16,
          `Low pressure: difficulty ${dna.difficulty}/10 and combat ${dna.combat}/10.`,
          `Can be intense for a relaxing session (difficulty ${dna.difficulty}/10, combat ${dna.combat}/10).`);
        break;
      case 'social':
        add('mood', game.coop || game.multi ? 1 : 0,
          'You can play it with other people.',
          'Single-player only, so no playing with friends.');
        break;
      case 'quick':
        add('mood', SESSION_FIT.short[game.session],
          'Easy to enjoy in a quick session.',
          `Usually needs ${SESSION_TEXT[game.session]} to make progress.`);
        break;
      default:
        break;
    }

    switch (prefs.play_style) {
      case 'solo':
        add('play_style', game.single ? 1 : 0,
          'Has a full single-player mode.',
          'No single-player mode: you need other players.');
        break;
      case 'coop':
        add('play_style', game.coop ? 1 : (game.multi ? 0.4 : 0),
          'Supports co-op play.',
          game.multi ? 'No co-op mode (competitive multiplayer only).' : 'No co-op mode.');
        break;
      case 'multiplayer':
        add('play_style', game.multi ? 1 : (game.coop ? 0.6 : 0),
          'Has competitive or online multiplayer.',
          'No multiplayer mode.');
        break;
      default:
        break;
    }

    if (prefs.difficulty) {
      const target = DIFFICULTY_TARGET[prefs.difficulty];
      const harder = dna.difficulty > target;
      add('difficulty', 1 - Math.abs(dna.difficulty - target) / 6,
        `Difficulty ${dna.difficulty}/10 (${difficultyLabel(dna.difficulty)}) suits the ${prefs.difficulty} experience you asked for.`,
        `${harder ? 'Harder' : 'Easier'} than you asked for (difficulty ${dna.difficulty}/10).`);
    }

    if (prefs.session) {
      add('session', SESSION_FIT[prefs.session][game.session],
        game.session === prefs.session
          ? `Fits ${SESSION_TEXT[prefs.session]}.`
          : `Plays well in ${SESSION_TEXT[game.session]}, which fit the time you have.`,
        `Works best in ${SESSION_TEXT[game.session]}, but you have ${SESSION_TEXT[prefs.session]}.`);
    }

    if (prefs.focus) {
      const score = dna[prefs.focus];
      const name = FOCUS_NAME[prefs.focus];
      add('focus', score / 10,
        `Strong on ${name.toLowerCase()}: ${score}/10.`,
        `${name} isn't its strength (${score}/10).`, prefs.focus);
    }

    if (prefs.platform) {
      const name = names.platforms[prefs.platform] || prefs.platform;
      const has = Object.prototype.hasOwnProperty.call(game.platforms, prefs.platform);
      add('platform', has ? 1 : 0, `Available on ${name}.`, `Not available on ${name}.`);
    }

    const totalWeight = parts.reduce((sum, p) => sum + p.weight, 0);
    const earned = parts.reduce((sum, p) => sum + p.weight * p.fraction, 0);
    return {
      percent: totalWeight ? Math.round((earned / totalWeight) * 100) : null,
      parts,
    };
  }

  /** Attribute-based strengths, used when there are no answers or too few matches. */
  function strengths(game) {
    const d = game.dna;
    const out = [];
    if (d.story >= 8) { out.push([d.story, `The story is one of its strongest parts (${d.story}/10).`]); }
    if (d.exploration >= 8) { out.push([d.exploration, `Rewards curiosity and exploring (exploration ${d.exploration}/10).`]); }
    if (d.combat >= 8) { out.push([d.combat, `Combat is a highlight (${d.combat}/10).`]); }
    if (d.replayability >= 8) { out.push([d.replayability, `Plenty of reasons to come back (replayability ${d.replayability}/10).`]); }
    if (d.difficulty <= 3) { out.push([7, `Approachable, with low difficulty (${d.difficulty}/10).`]); }
    if (game.session === 'short') { out.push([6.5, 'Works well in short sessions.']); }
    if (game.coop || game.multi) {
      const modes = [game.coop && 'co-op', game.multi && 'multiplayer'].filter(Boolean).join(' and ');
      out.push([6, `You can play with friends (${modes}).`]);
    }
    if (game.reviews >= 3 && game.rating >= 4.5) {
      out.push([8.5, `Rated ${game.rating.toFixed(1)}/5 across ${game.reviews} community reviews.`]);
    }
    return out.sort((a, b) => b[0] - a[0]).map((x) => x[1]);
  }

  /** Attribute-based things to keep in mind. */
  function considerations(game) {
    const d = game.dna;
    const out = [];
    if (d.difficulty >= 8) { out.push(`Very demanding: difficulty ${d.difficulty}/10.`); }
    if (game.hours && game.hours >= 40) { out.push(`A big time commitment: about ${game.hours} hours to finish.`); }
    if (game.session === 'long') { out.push('Works best in long play sessions (2+ hours).'); }
    if (!game.single) {
      out.push(game.coop && !game.multi ? 'Co-op only: you need a partner to play.' : 'No single-player mode: you need other players.');
    }
    if (d.story <= 2) { out.push(`Very little story (${d.story}/10).`); }
    if (d.replayability <= 3) { out.push(`Limited replay value once finished (${d.replayability}/10).`); }
    if (game.reviews < 3) {
      out.push(game.reviews === 0
        ? 'No community reviews yet.'
        : `Only ${game.reviews} community review${game.reviews === 1 ? '' : 's'} so far, so the rating may change.`);
    }
    return out;
  }

  // Most telling criteria first. Solo play and platform are true for most games, so they come last.
  const REASON_ORDER = ['genre', 'mood', 'focus', 'difficulty', 'session', 'play_style', 'platform'];

  /** "Why this matches you": matched criteria, most telling first, topped up with strengths. */
  function reasons(game, result, max = 3) {
    const seenTraits = new Set();
    const matched = result.parts
      .filter((p) => p.fraction >= 0.7)
      .sort((a, b) => REASON_ORDER.indexOf(a.key) - REASON_ORDER.indexOf(b.key))
      .filter((p) => {
        if (!p.trait) { return true; }
        if (seenTraits.has(p.trait)) { return false; } // e.g. "story" mood and "story" focus
        seenTraits.add(p.trait);
        return true;
      })
      .map((p) => p.good);
    const extra = strengths(game).filter((s) => !matched.includes(s));
    return matched.concat(matched.length < 2 ? extra : []).slice(0, max);
  }

  /** Drawbacks: biggest missed criteria first, then general considerations. */
  function drawbacks(game, result, max = 3) {
    const missed = result.parts
      .filter((p) => p.fraction < 0.5)
      .sort((a, b) => b.weight * (1 - b.fraction) - a.weight * (1 - a.fraction))
      .map((p) => p.bad);
    return missed.concat(considerations(game)).slice(0, max);
  }

  /** Rank games by match %, then community rating, then number of reviews. */
  function rank(games, prefs, names) {
    return games
      .map((game) => ({ game, result: evaluate(game, prefs, names) }))
      .sort((a, b) => (b.result.percent - a.result.percent)
        || (b.game.rating - a.game.rating)
        || (b.game.reviews - a.game.reviews));
  }

  // Quiz answers for guests are kept in this browser tab only.
  const PREFS_KEY = 'gh_quiz_prefs';
  function loadGuestPrefs() {
    try { return JSON.parse(sessionStorage.getItem(PREFS_KEY) || 'null'); } catch (e) { return null; }
  }
  function saveGuestPrefs(prefs) {
    try { sessionStorage.setItem(PREFS_KEY, JSON.stringify(prefs)); } catch (e) { /* storage blocked */ }
  }

  return {
    WEIGHTS, SESSION_TEXT, evaluate, reasons, drawbacks, strengths, considerations,
    rank, hasAnswers, difficultyLabel, loadGuestPrefs, saveGuestPrefs,
  };
})();
