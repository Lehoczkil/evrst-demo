<script setup lang="ts">
import { motion } from 'motion-v';
import { EventRequests, soonest, startOf } from '@/services/requests/EventRequests';
import { CSS_SCROLL_DRIVEN, useParallax } from '@/composables/useParallax';
import { BUILD, CHAR_STEP, COPY_OUT, WORD_STEP, heroBuild } from './anims';
import { PIN_VH, STAGE_VH } from './layers';

/*
  The hero: a 100dvh pane pinned inside a 300dvh stage.

  Why pinned rather than simply tall — three problems it solves at once,
  all of them found by building it the other way first:

    1. A 100dvh hero gave the layers 100dvh of scroll to move through and
       the parallax was barely legible. The pin is the runway, and the
       runway IS the effect: travel is depth × pin.
    2. Making the hero 160dvh tall was the obvious fix and the wrong one —
       everything positioned from its bottom edge (the ridges, the tower,
       the rocket) fell below the fold and the first frame showed sky. A
       pinned pane is always exactly the viewport, so bottom-anchored
       geometry is always in view.
    3. An oversized overflow:hidden box has a bottom edge for a
       travelling layer to slide into, which is where the stuck band came
       from. A pinned pane has none — it is the viewport.

  `--pin` is the pin's length and it feeds both paths: the CSS keyframes'
  travel and animation-range, and the fallback's arithmetic.

  The copy sits on the pane's VERTICAL CENTRE, in the same place the
  statement sequence arrives — so the headline does not vacate a spot at
  the top and leave the statements to appear somewhere else. One optical
  centre, handed from one thing to the next.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();
const { locale } = useLocale();

const stageRef = ref<HTMLElement | null>(null);
const paneRef = ref<HTMLElement | null>(null);

const { progress, pin, measure } = useParallax(stageRef, paneRef);

/*
  The comets, the beacon and the engine flame have no resting state, and
  the hero is off screen for most of a nine-viewport page. See
  useOffscreenIdle for why pausing them matters more than the ticks.
*/
const { idle } = useOffscreenIdle(stageRef);

/*
  The build waits for Panchang.

  `font-display: swap` paints the headline in Space Grotesk first and
  swaps when the real face lands. The two have different metrics, so the
  swap changes the copy block's height AND where its lines wrap — and the
  block is vertically centred, so every line in it moves. That is the
  jump on a cold load.

  Holding the build until `document.fonts.ready` means the space is
  reserved (the block is laid out the whole time, just at opacity 0 and
  behind its masks) and the reader sees the arrival once, in the right
  font. The timeout is the safety net: a font that never resolves must
  not leave the hero permanently blank.
*/
const FONT_TIMEOUT = 1500;
const ready = ref(false);
let readyTimer: ReturnType<typeof setTimeout> | null = null;

/*
  The countdown's target. Keyed on locale because the payload is localised
  server-side, and the label comes out of the same row as the date.
*/
const { data: events } = useQuery({
  key: ['events', locale],
  request: () => EventRequests.all(locale.value as string),
  cache: true,
  staleTime: 120,
  refetchTime: 900,
});
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const nextEvent = computed(() => soonest(events.value));
const nextAt = computed(() => (nextEvent.value ? startOf(nextEvent.value) : null));

/*
  "Következő: <event title>" — the label names the actual thing, so the
  clock is never counting to something unspecified. When no future event
  exists <Countdown> renders nothing and this is never read.
*/
const nextLabel = computed(() => (nextEvent.value?.payload?.title
  ? `${t('hero.next')}: ${nextEvent.value.payload.title}`
  : ''));

/*
  Both figures come from STAGE_VH, and `--pin` is a dvh length rather than
  the measured px: it is correct on the first paint, before any script has
  run. The measured `pin` is still used, but only by the JS fallback's own
  arithmetic.
*/
const stageStyle = computed(() => ({
  height: `${STAGE_VH * 100}dvh`,
  '--pin': `${PIN_VH}dvh`,
}));

/** The copy's hand-over, on the fallback path only. */
const copyStyle = computed(() => (CSS_SCROLL_DRIVEN
  ? undefined
  : { opacity: String(Math.max(0, 1 - progress.value / COPY_OUT)) }));
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
onMounted(() => {
  // The stage's height is in dvh, so it settles a frame after mount on
  // mobile browsers that resolve dvh against a collapsing toolbar.
  nextTick(measure);

  readyTimer = setTimeout(() => {
    ready.value = true;
  }, FONT_TIMEOUT);

  const go = () => {
    ready.value = true;
    if (readyTimer) {
      clearTimeout(readyTimer);
      readyTimer = null;
    }
  };

  if (document.fonts?.ready) {
    document.fonts.ready.then(go, go);
  } else {
    go();
  }
});

onBeforeUnmount(() => {
  if (readyTimer) {
    clearTimeout(readyTimer);
  }
});
</script>

<template>
  <div
    ref="stageRef"
    class="hero-stage relative"
    :style="stageStyle"
  >
    <!--
      `surface-ignore` keeps the hero's own gradient layers out of the
      chrome's sampling: without it the wordmark reads the sky's radial
      instead of the page, and flips on a decorative overlay. The walk
      falls through to body's --ink-0, which is the right answer anyway.
    -->
    <section
      ref="paneRef"
      class="hero surface-ignore sticky top-0 h-100dvh overflow-hidden"
      :class="{ 'is-idle': idle }"
      :aria-label="t('hero.eyebrow')"
    >
      <HeroParallax :progress="progress" :pin="pin">
        <div class="hero-copy wrap" :style="copyStyle">
          <SplitText
            as="p"
            class="eyebrow"
            :text="t('hero.eyebrow')"
            :play="ready"
            :delay="BUILD.eyebrow"
            :stagger="0.018"
            :duration="0.6"
          />

          <h1 class="hero-title">
            <SplitText
              :text="t('hero.title1')"
              :play="ready"
              :delay="BUILD.title1"
              :stagger="CHAR_STEP"
            />
            <SplitText
              class="hero-title__accent"
              :text="t('hero.title2')"
              :play="ready"
              :delay="BUILD.title2"
              :stagger="CHAR_STEP"
            />
          </h1>

          <SplitText
            as="p"
            class="hero-lede"
            per="word"
            :text="t('hero.lede')"
            :play="ready"
            :delay="BUILD.lede"
            :stagger="WORD_STEP"
            :duration="0.7"
          />

          <motion.div v-bind="heroBuild(BUILD.cta, ready)" class="cta-row">
            <RouterLink to="/join-us" class="btn">
              {{ t('hero.ctaJoin') }}
            </RouterLink>
            <a href="#mission" class="btn btn--ghost">
              {{ t('hero.ctaMission') }}
            </a>
          </motion.div>

          <!--
            The clock's height is reserved whether or not there is an
            event to count to. <Countdown> renders nothing when the next
            event is in the past or the query has not landed yet, and on
            a vertically centred block "nothing" is not free: the clock
            arriving with the events response would grow the block and
            shove the headline up by half its height.
          -->
          <motion.div v-bind="heroBuild(BUILD.countdown, ready)" class="countdown-slot">
            <Countdown :target="nextAt" :label="nextLabel" />
          </motion.div>
        </div>

        <HeroSays :progress="progress" />
      </HeroParallax>
    </section>
  </div>
</template>

<style lang="scss" scoped>
.hero {
  /*
    z0 — the sky. Two gradients, so it costs no request and nothing to
    composite. Not pure black at the bottom: the gold radial over it bands
    visibly on a 6-bit panel without somewhere to ramp into.
  */
  background:
    radial-gradient(120% 76% at 50% 106%, rgb(241 171 60 / 14%) 0%, rgb(241 171 60 / 0%) 46%),
    radial-gradient(150% 100% at 50% 0%, #101725 0%, #07080d 52%, var(--ink-0) 100%);
}

.hero-copy {
  text-align: center;
  will-change: opacity, transform;
}

/*
  The copy hands over in the first fifth of the pin, and it LEAVES rather
  than switching off: a small lift and a slight recede, on the same
  timeline as the fade. The statements arrive into the space it vacates,
  on the same optical centre.
*/
@supports (animation-timeline: scroll()) {
  .hero-copy {
    animation: hero-copy-out linear both;
    animation-timeline: scroll(root block);
    animation-range-start: 0;
    animation-range-end: calc(var(--pin) * 0.2);
  }
}

@keyframes hero-copy-out {
  from {
    opacity: 1;
    transform: translate3d(0, 0, 0) scale(1);
  }

  to {
    opacity: 0;
    transform: translate3d(0, -78px, 0) scale(0.93);
  }
}

.hero-title {
  margin-top: 18px;
  font-size: var(--fs-display);
}

/*
  The second line takes the accent as a gradient rather than a flat gold:
  at display size a flat fill reads as a slab, and the ramp gives the
  letterforms an edge to catch.

  The clip is on the PIECE, not on the line: `background-clip: text` on
  the line would paint a gradient the masked pieces then slide out of, so
  every character would fade through the ramp as it arrived. On the piece
  each glyph carries its own copy of the same vertical ramp, and since
  they are all one line tall the result is identical to a single fill.
*/
.hero-title__accent {
  display: block;

  :deep(.split__piece) {
    background: linear-gradient(180deg, var(--gold-400) 0%, var(--gold-600) 92%);
    background-clip: text;
    -webkit-text-fill-color: transparent;
  }
}

.hero-lede {
  max-width: 52ch;
  margin: 22px auto 0;
  color: var(--text-mid);
  font-size: clamp(1rem, 1.35vw, 1.16rem);
}

.cta-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  justify-content: center;
  margin-top: 30px;
}

// The clock is ~40px of numerals over an ~17px label. Reserved so the
// block's height does not depend on whether an event exists.
.countdown-slot {
  min-height: clamp(48px, 5vw, 64px);
  margin-top: 32px;
}

/*
  Off screen: stop the loops that never end.

  Named individually rather than as a blanket `:deep(*)`, because the
  scroll-driven animations in here must NOT be paused — they are the
  parallax, and freezing one holds it at whatever position it had when it
  left the frame.
*/
.hero.is-idle {
  :deep(.comet),
  :deep(.tower__lamp),
  :deep(#exhaust) {
    animation-play-state: paused;
  }
}

@media (prefers-reduced-motion: reduce) {
  .hero-copy {
    animation: none;
    opacity: 1 !important;
    transform: none !important;
  }
}
</style>
