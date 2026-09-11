<script setup lang="ts">
import { CSS_SCROLL_DRIVEN } from '@/composables/useParallax';
import { SAY_IN, SAY_OUT, SAY_SHIFT, SAY_WINDOWS } from './anims';

/*
  What the About section used to be, done in the hero's own empty band.

  Three statements arrive one after another in the space the headline
  leaves behind, each wiping in character by character, holding, and
  fading out as the pinned stage is scrolled through. The reader is
  already here when they want to know what this is, so the claim is made
  here rather than 200px further down in a section of its own.

  Centred on the viewport's middle rather than measured down from the
  headline: the headline's height changes with the type scale, the
  viewport's middle does not, so the band lands in the same place at every
  width — and it is the same centre the headline itself sits on, so the
  sequence reads as one thing replacing another rather than as two blocks
  in two places.

  ── Two animations, two elements ────────────────────────────────────────

  The wipe is per CHARACTER and the fade is per LINE, and they are on
  different elements on purpose. Doing both per character means the
  cascade has to finish before the hold starts and start again before the
  hold ends, which at a stagger wide enough to see leaves a line "fully
  present" for about a fiftieth of its own window. Splitting them, the
  characters only ever come IN on a stagger and the line leaves as one
  piece of type, which is also how it reads best: a line assembling
  itself, then a line.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const { progress = 0 } = defineProps<{
  /** 0-1 through the pin. Only read on the JS fallback path. */
  progress?: number;
}>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { tm, rt } = useI18n();

/*
  Character-by-character on a real screen, word-by-word on a phone.

  Each piece carries its own scroll-driven animation, and three
  statements split to characters is ~160 of them. That costs nothing
  measurable on a desktop — they run on the compositor, and a full pass
  through the pin here holds 120fps with no dropped frames — but each one
  is also a composited layer, and 160 of them is memory a mid-range
  Android would rather spend on the eight parallax planes above it.
  Splitting to words instead drops it to ~35 for the same reveal, one
  notch coarser.
*/
const isWide = ref(false);
let mq: MediaQueryList | null = null;

const syncViewport = () => {
  isWide.value = !!mq?.matches;
};
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/**
 * A line's opacity and offset at the current progress, for the fallback.
 *
 * Mirrors the @keyframes below: in over the first SAY_IN of its window,
 * held to SAY_OUT, out over the rest. Returns undefined on the CSS path
 * so nothing is bound and the keyframe owns the element outright — a
 * `:style` here would fight it, and the inline style would win.
 *
 * The fallback deliberately does NOT reproduce the per-character wipe.
 * That would be one inline style write per character per frame on the
 * main thread of a browser that already has no scroll timeline to lean
 * on — the exact machine that can least afford it.
 */
const styleFor = (index: number) => {
  if (CSS_SCROLL_DRIVEN) {
    return undefined;
  }

  const [from, to] = SAY_WINDOWS[index];
  const q = (progress - from) / (to - from);

  if (q <= 0 || q >= 1) {
    return { opacity: '0' };
  }

  const opacity = q < SAY_IN
    ? q / SAY_IN
    : q > SAY_OUT
      ? (1 - q) / (1 - SAY_OUT)
      : 1;

  return {
    opacity: String(Math.max(0, Math.min(1, opacity))),
    transform: `translateY(${(SAY_SHIFT - 2 * SAY_SHIFT * q).toFixed(1)}px)`,
  };
};

/** The window as two lengths, for the CSS path's animation-range. */
const rangeFor = (index: number) => {
  if (!CSS_SCROLL_DRIVEN) {
    return undefined;
  }
  const [from, to] = SAY_WINDOWS[index];

  return {
    '--say-from': `calc(var(--pin) * ${from})`,
    '--say-to': `calc(var(--pin) * ${to})`,
  };
};
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/*
  The statements come from the message file as a list, so adding a fourth
  is a translation edit and a fourth window — not a template change.
  `tm` + `rt` rather than `t`, because these carry <em> markup for the one
  word that takes the accent, and <SplitText> keeps that run intact
  through the split.
*/
const says = computed(() => {
  const raw = tm('hero.says') as unknown[];

  return (Array.isArray(raw) ? raw : [])
    .slice(0, SAY_WINDOWS.length)
    .map((line) => rt(line as Parameters<typeof rt>[0]));
});
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
onMounted(() => {
  mq = window.matchMedia('(min-width: 768px)');
  syncViewport();
  mq.addEventListener('change', syncViewport);
});

onBeforeUnmount(() => {
  mq?.removeEventListener('change', syncViewport);
});
</script>

<template>
  <div class="hero-says">
    <!--
      Two elements per line, and both are load-bearing. The outer one is
      the positioned, pane-height box that does the centring and carries
      the fade; the inner one is <SplitText>'s own block, whose children
      are inline-blocks and must stay in inline flow — making the
      centring box the split root turned every word into a grid item.
    -->
    <div
      v-for="(line, i) in says"
      :key="i"
      class="hero-say"
      :style="{ ...rangeFor(i), ...styleFor(i) }"
    >
      <SplitText
        as="p"
        class="hero-say__line"
        driver="none"
        :per="isWide ? 'char' : 'word'"
        :text="line"
      />
    </div>
  </div>
</template>

<style lang="scss" scoped>
/*
  The pane, not a band inside it.

  This used to be `inset: 50dvh 0 auto` with `translate: 0 -50%`, which
  centred nothing: every line inside is absolutely positioned, so the box
  had no height, and a -50% of zero is zero. The lines simply started at
  the halfway mark and hung below it. Each line now owns a full-height
  box and centres its own content in it, which puts it on the same
  optical centre the headline hands over from.
*/
.hero-says {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.hero-say {
  display: grid;
  position: absolute;
  inset: 0;
  align-content: center;
  justify-items: center;
  padding-inline: clamp(16px, 4vw, 56px);
  opacity: 0;
  will-change: opacity;
}

.hero-say__line {
  /*
    `em`, not `ch`. Panchang's zero is 1.19em wide, so the 26ch this was
    written as resolved to 1737px — wider than the viewport it was meant
    to constrain, and the measure did nothing at all.
  */
  max-width: min(17em, 100%);
  margin: 0;
  color: var(--text-hi);
  font-weight: 700;
  font-size: clamp(1.55rem, 4.4vw, 3.5rem);
  line-height: 1.02;
  font-family: var(--font-display);
  text-align: center;
  letter-spacing: -0.03em;
  text-wrap: balance;

  :deep(.split__mask--accent) {
    color: var(--gold-500);
  }
}

/*
  The primary path. One @keyframes for all three lines; the window is what
  differs, and it comes in as two custom properties.
*/
@supports (animation-timeline: scroll()) {
  .hero-say {
    animation: hero-say linear both;
    animation-timeline: scroll(root block);
    animation-range-start: var(--say-from);
    animation-range-end: var(--say-to);
  }

  /*
    The wipe. Each character runs the same one-way rise, its window
    shifted down the timeline by its own index — which is what turns a
    line into a cascade without a second timeline or any JS.

    `--say-step` is a fraction of the pin per character, not a fixed
    length: a 50-character line has to finish arriving inside the first
    part of its own window whatever the pin works out to in pixels.
  */
  .hero-say :deep(.split__piece) {
    --say-step: calc(var(--pin) * 0.0075);
    --say-rise: calc(var(--pin) * 0.05);

    animation: hero-say-piece linear both;
    animation-timeline: scroll(root block);
    animation-range-start: calc(var(--say-from) + var(--i) * var(--say-step));
    animation-range-end: calc(var(--say-from) + var(--i) * var(--say-step) + var(--say-rise));

    // ~50 pieces a line instead of ~11, so each one waits a lot less.
    @media (width >= 768px) {
      --say-step: calc(var(--pin) * 0.0022);
      --say-rise: calc(var(--pin) * 0.045);
    }
  }
}

@keyframes hero-say {
  0% {
    opacity: 0;
  }

  10% {
    opacity: 1;
  }

  76% {
    opacity: 1;
  }

  100% {
    opacity: 0;
  }
}

@keyframes hero-say-piece {
  from {
    transform: translateY(118%);
  }

  to {
    transform: translateY(0%);
  }
}

/*
  With no timeline to sequence them the three lines would stack invisibly
  on top of each other, so they stop being a sequence and stand as a
  static block. The content is the point; the sequencing is not.
*/
@media (prefers-reduced-motion: reduce) {
  .hero-says {
    display: grid;
    position: static;
    padding-top: 44px;
    gap: 28px;
  }

  .hero-say {
    position: static;
    inset: auto;
    animation: none;
    opacity: 1;

    :deep(.split__piece) {
      animation: none;
      transform: none;
    }
  }
}
</style>
