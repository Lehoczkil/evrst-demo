<script setup lang="ts">
/*
  The gold band, and the one loud element on the page.

  Two things were wrong with it and they were unrelated.

  ── The colour ──────────────────────────────────────────────────────────
  It was `bg-primary text-bgDark`, and those UnoCSS colours stopped
  existing in the 0.6.0 redesign — the theme is `ink` / `gold` / `paper` /
  `txt` now. Unknown utilities emit nothing rather than failing, so the
  band silently lost its background and rendered white-on-black: the
  section that is supposed to be the page's one burst of gold looked like
  a plain caption strip. Grep for the old names before assuming a utility
  still resolves.

  ── The loop ────────────────────────────────────────────────────────────
  It was one flex row of three word-copies animated to `translateX(-33.33%)`,
  which cannot be seamless for two separate reasons:

    · The `gap` between copies belongs to the track, not to a copy, so a
      third of the track's WIDTH is not a copy's ADVANCE. It was short by
      about half a gap, and the strip jumped by that much every cycle.
    · One copy was ~690px against a 1512px viewport, so at the end of the
      travel there was less content left than screen to fill and the tail
      end ran off into empty gold.

  The fix is the standard one, stated properly: exactly TWO identical
  rows, each carrying its own trailing gap, animated to -50%. Half the
  track is then exactly one row, so the frame at -50% is pixel-identical
  to the frame at 0 and there is no seam to see. REPEATS makes one row
  wider than any viewport it will meet, which is the other half of the
  requirement — at the end of the travel the second row alone has to fill
  the screen.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const WORDS = ['Escape', 'Velocity', 'Rocketry', 'Student', 'Team', '×'];

/**
 * How many times the word list is repeated inside ONE row.
 *
 * One pass is ~690px at this type size, so five is ~3450px: wider than
 * any viewport this will realistically be read on, including a 3440px
 * ultrawide. Below that width the tail of the loop would show bare gold.
 */
const REPEATS = 5;

const row = Array.from({ length: REPEATS }, () => WORDS).flat();

const bandRef = ref<HTMLElement | null>(null);

/*
  Two rows of five passes is a ~6900px composited layer, and a running
  animation is what keeps it promoted. It is on screen for one of the
  page's nine viewports.
*/
const { idle } = useOffscreenIdle(bandRef);
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <!--
    aria-hidden on the whole band: it is the brand name repeated thirty
    times, which is noise in a screen reader and says nothing the page
    has not already said in its heading.
  -->
  <div ref="bandRef" class="marquee" :class="{ 'is-idle': idle }" aria-hidden="true">
    <div class="marquee__track">
      <div v-for="copy in 2" :key="copy" class="marquee__row">
        <span v-for="(word, i) in row" :key="`${word}-${i}`" class="marquee__word">
          {{ word }}
        </span>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.marquee {
  position: relative;
  height: 32px;
  overflow: hidden;
  color: var(--ink-0);
  background: var(--gold-500);
  user-select: none;
  pointer-events: none;
}

.marquee__track {
  display: flex;
  width: max-content;
  height: 100%;
  animation: marquee-scroll 60s linear infinite;
  will-change: transform;
}

/*
  The gap lives INSIDE the row, and the row carries one more of it on its
  trailing edge. That is what makes a row's width its true advance, and
  the -50% exact: no gap is owned by the track, so none of it is
  unaccounted for when the track is halved.
*/
.marquee__row {
  display: flex;
  flex: none;
  align-items: center;
  gap: 32px;
  padding-right: 32px;
}

.marquee__word {
  font-weight: 700;
  font-size: 14px;
  line-height: 1;
  font-family: var(--font-display);
  text-transform: uppercase;
  white-space: nowrap;
}

@keyframes marquee-scroll {
  from {
    transform: translate3d(0, 0, 0);
  }

  to {
    transform: translate3d(-50%, 0, 0);
  }
}

// Off screen: see useOffscreenIdle. Pausing is what lets the browser
// drop this track's several-viewports-wide compositor layer.
.marquee.is-idle .marquee__track {
  animation-play-state: paused;
}

/*
  A marquee is motion with no resting state, so under reduced motion it
  stops — and it holds at its start rather than mid-travel, where the
  second row's leading edge would sit somewhere arbitrary on screen.
*/
@media (prefers-reduced-motion: reduce) {
  .marquee__track {
    animation: none;
    transform: none;
  }
}
</style>
