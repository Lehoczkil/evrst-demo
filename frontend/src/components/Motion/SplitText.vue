<script setup lang="ts">
import { motion } from 'motion-v';
import { EASE_OUT } from './springs';

/*
  Text that arrives one piece at a time, from behind its own baseline.

  Ported from feat.agency's `v-reveal-text` directive, with two changes.

  It is a COMPONENT rather than a directive because that one splits the
  DOM it is given — `el.innerHTML = ''`, then rebuild — which is fine
  under GSAP and wrong under Vue: the next re-render puts the original
  text node back and the split is gone. Declaring the pieces in the
  template means Vue owns them, so a locale switch re-splits the new
  string instead of fighting over the old one.

  And the piece is a CHARACTER by default, not a word. Same mechanism —
  an `overflow: hidden` box with the glyph pushed below it, released on a
  stagger — but at display size the per-word version reveals a headline
  in four moves, and per-character reads as the line being typed into
  place. Body copy still wants `per="word"`: per-character on a sentence
  is 90 boxes to say the same thing, and it reads as a gimmick.

  ACCESSIBILITY. The pieces are `aria-hidden` and the whole thing carries
  the plain string as its accessible name, so a screen reader is never
  handed a headline one letter at a time.

  The travel is a `transform` STRING rather than motion's `y`, because
  motion hands `transform` to the browser's own animation engine and
  drives `y` from its frameloop instead — one main-thread style write per
  piece per frame, and a headline is forty pieces.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const {
  text = '',
  as = 'span',
  per = 'char',
  driver = 'enter',
  delay = 0,
  stagger = 0.028,
  duration = 0.8,
  play = true,
} = defineProps<{
  /**
   * The line. `<em>…</em>` marks an accented run — the message files use
   * it for the one word that takes the gold, and splitting would
   * otherwise throw the markup away.
   */
  text?: string;
  /** The wrapper's tag. */
  as?: string;
  per?: 'char' | 'word';
  /**
   * Who moves the pieces.
   *
   * `enter` is the build on mount, run by motion-v. `none` splits and
   * masks but animates nothing — every piece carries its index as `--i`
   * and the caller's own CSS drives it. That is what the statement
   * sequence uses: those pieces are on a SCROLL timeline, and a
   * motion-driven inline transform on the same element would win the
   * fight against the keyframe and freeze them.
   */
  driver?: 'enter' | 'none';
  /** Seconds before the first piece moves. */
  delay?: number;
  /** Seconds between one piece and the next. */
  stagger?: number;
  duration?: number;
  /** False holds every piece hidden — for a build that waits on a cue. */
  play?: boolean;
}>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
/*
  How far below its own box a piece starts.

  120%, not 100%: `.piece-mask` carries `padding-bottom: 0.16em` so
  descenders are not clipped, which makes the mask taller than the glyph.
  Exactly 100% leaves a hairline of the next line's caps showing in that
  strip — feat.agency's note, and it is still true here.
*/
const HIDDEN = 'translateY(120%)';
const SHOWN = 'translateY(0%)';
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/**
 * Split the raw string into accented / plain runs.
 *
 * The only markup the message files carry is `<em>` around one word, and
 * it is authored by us — so this is a two-token scan, not an HTML parser,
 * and nothing from the API is ever passed through here.
 */
const runs = (raw: string) => raw
  .split(/(<em>|<\/em>)/)
  .reduce<{ text: string; accent: boolean }[]>((acc, token) => {
    if (token === '<em>' || token === '</em>') {
      acc.push({ text: '', accent: token === '<em>' });

      return acc;
    }
    if (!token) {
      return acc;
    }
    const last = acc[acc.length - 1];
    if (last && last.text === '') {
      last.text = token;
    } else {
      acc.push({ text: token, accent: last?.accent ?? false });
    }

    return acc;
  }, []);
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/** The plain string, for the accessible name. */
const label = computed(() => text.replace(/<\/?em>/g, ''));

/*
  Words carry the pieces rather than the pieces sitting loose in the
  wrapper: a word is `display: inline-block; white-space: nowrap`, so a
  line break can only ever fall between words. Splitting straight to
  characters let a headline wrap mid-word, which no amount of
  `text-wrap: balance` can undo.

  The accent flag rides the PIECE, not the word, and the runs are
  flattened to characters before words are cut out of them. Grouping by
  run first was the obvious way and it was wrong: `…the <em>launch
  pad</em>.` has the full stop in a different run from the word it
  belongs to, so it came out as its own word and the line rendered
  "launch pad ." with a space in front of the period. Whitespace in the
  source is the only thing that starts a new word.

  `i` counts across the whole line rather than per word, so the stagger
  is one continuous cascade instead of restarting at every space.
*/
type Piece = { key: string; text: string; accent: boolean; i: number };

const words = computed(() => {
  const out: { key: string; pieces: Piece[] }[] = [];
  let current: Piece[] = [];
  let i = 0;

  const flush = () => {
    if (current.length) {
      out.push({ key: `w${out.length}`, pieces: current });
      current = [];
    }
  };

  for (const run of runs(text)) {
    for (const ch of Array.from(run.text)) {
      if (/\s/.test(ch)) {
        flush();
        continue;
      }

      // In word mode a run boundary inside a word still has to break the
      // piece — the two halves take different colours — so pieces merge
      // only while the accent holds.
      const last = current[current.length - 1];
      if (per === 'word' && last && last.accent === run.accent) {
        last.text += ch;
        continue;
      }

      current.push({ key: `p${i}`, text: ch, accent: run.accent, i: i++ });
    }
  }
  flush();

  return out;
});
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <component :is="as" class="split" :aria-label="label">
    <!--
      The separator is a real text node BETWEEN the words, never inside
      one: a word is `white-space: nowrap`, so a space carried inside it
      is a space a line can never break at, and a long headline would
      overflow rather than wrap.
    -->
    <template v-for="(word, w) in words" :key="word.key">
      <span v-if="w" aria-hidden="true">{{ ' ' }}</span><span
        class="split__word"
        aria-hidden="true"
      ><span
        v-for="piece in word.pieces"
        :key="piece.key"
        class="split__mask"
        :class="{ 'split__mask--accent': piece.accent }"
      ><motion.span
        v-if="driver === 'enter'"
        class="split__piece"
        :initial="{ transform: HIDDEN }"
        :animate="{ transform: play ? SHOWN : HIDDEN }"
        :transition="{ duration, ease: EASE_OUT, delay: delay + piece.i * stagger }"
      >{{ piece.text }}</motion.span><span
        v-else
        class="split__piece"
        :style="{ '--i': piece.i }"
      >{{ piece.text }}</span></span></span>
    </template>
  </component>
</template>

<style lang="scss" scoped>
.split {
  display: block;
}

.split__word {
  display: inline-block;
  white-space: nowrap;
}

/*
  The clip box. `padding-bottom` makes room for descenders without
  changing where the baseline sits — the negative margin gives the space
  straight back to the line box.
*/
.split__mask {
  display: inline-block;
  padding-bottom: 0.16em;
  margin-bottom: -0.16em;
  overflow: hidden;
  vertical-align: bottom;
}

.split__piece {
  display: inline-block;
}

/*
  Reduced motion: MotionConfig skips the transform and the piece is
  simply there. The masks stay — they change nothing at rest — so the
  layout is identical either way.
*/
@media (prefers-reduced-motion: reduce) {
  .split__piece {
    transform: none !important;
  }
}
</style>
