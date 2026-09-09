<script setup lang="ts">
import { motion } from 'motion-v';
import { EventRequests, soonest, startOf } from '@/services/requests/EventRequests';
import { CSS_SCROLL_DRIVEN, useParallax } from '@/composables/useParallax';
import { COPY_OUT, heroBuild } from './anims';
import { PIN_VH, STAGE_VH } from './layers';

/*
  The hero: a 100dvh pane pinned inside a 215dvh stage.

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

  `--pin` is the pin's length in px and it feeds both paths: the CSS
  keyframes' travel and animation-range, and the fallback's arithmetic.
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

/** The copy's fade, on the fallback path only. */
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
      :aria-label="t('hero.eyebrow')"
    >
      <HeroParallax :progress="progress" :pin="pin">
        <div class="hero-copy wrap" :style="copyStyle">
          <motion.p v-bind="heroBuild(0)" class="eyebrow">
            {{ t('hero.eyebrow') }}
          </motion.p>

          <motion.h1 v-bind="heroBuild(0.06)" class="hero-title">
            {{ t('hero.title1') }}<span class="hero-title__accent">{{ t('hero.title2') }}</span>
          </motion.h1>

          <motion.p v-bind="heroBuild(0.12)" class="hero-lede">
            {{ t('hero.lede') }}
          </motion.p>

          <motion.div v-bind="heroBuild(0.18)" class="cta-row">
            <RouterLink to="/join-us" class="btn">
              {{ t('hero.ctaJoin') }}
            </RouterLink>
            <a href="#mission" class="btn btn--ghost">
              {{ t('hero.ctaMission') }}
            </a>
          </motion.div>

          <motion.div v-bind="heroBuild(0.24)" class="mt-32px">
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
  padding-top: clamp(96px, 13vh, 150px);
  text-align: center;
  will-change: opacity;
}

/*
  The copy hands over at 15% of the pin rather than fading across half of
  it, because the statement sequence needs the room.
*/
@supports (animation-timeline: scroll()) {
  .hero-copy {
    animation: hero-copy-out linear both;
    animation-timeline: scroll(root block);
    animation-range-start: 0;
    animation-range-end: calc(var(--pin) * 0.15);
  }
}

@keyframes hero-copy-out {
  from {
    opacity: 1;
  }

  to {
    opacity: 0;
  }
}

.hero-title {
  margin-top: 18px;
  font-size: var(--fs-display);
}

// The second line takes the accent as a gradient rather than a flat gold:
// at display size a flat fill reads as a slab, and the ramp gives the
// letterforms an edge to catch.
.hero-title__accent {
  display: block;
  background: linear-gradient(180deg, var(--gold-400) 0%, var(--gold-600) 92%);
  background-clip: text;
  -webkit-text-fill-color: transparent;
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

@media (prefers-reduced-motion: reduce) {
  .hero-copy {
    animation: none;
    opacity: 1 !important;
  }
}
</style>
