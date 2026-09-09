<script setup lang="ts">
import { CSS_SCROLL_DRIVEN } from '@/composables/useParallax';
import { SAY_IN, SAY_OUT, SAY_SHIFT, SAY_WINDOWS } from './anims';

/*
  What the About section used to be, done in the hero's own empty band.

  Three statements arrive one after another in the space the headline
  leaves behind, each rising in, holding, and rising out as the pinned
  stage is scrolled through. The reader is already here when they want to
  know what this is, so the claim is made here rather than 200px further
  down in a section of its own.

  Centred on the viewport's middle rather than measured down from the
  headline: the headline's height changes with the type scale, the
  viewport's middle does not, so the band lands in the same place at every
  width.
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
  word that takes the accent.
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
</script>

<template>
  <div class="hero-says">
    <!-- eslint-disable vue/no-v-html -- the only markup is an <em> around
         one word, authored by us in the message files. -->
    <p
      v-for="(line, i) in says"
      :key="i"
      class="hero-say"
      :style="{ ...rangeFor(i), ...styleFor(i) }"
      v-html="line"
    />
    <!-- eslint-enable vue/no-v-html -->
  </div>
</template>

<style lang="scss" scoped>
.hero-says {
  position: absolute;
  inset: 50dvh 0 auto;
  translate: 0 -50%;
  text-align: center;
  pointer-events: none;
}

.hero-say {
  position: absolute;
  max-width: 26ch;
  margin: 0 auto;
  color: var(--text-hi);
  font-weight: 700;
  font-size: clamp(1.55rem, 4.4vw, 3.5rem);
  line-height: 1.02;
  font-family: var(--font-display);
  opacity: 0;
  inset-inline: 0;
  padding-inline: clamp(16px, 4vw, 56px);
  letter-spacing: -0.03em;
  text-wrap: balance;
  will-change: transform, opacity;

  :deep(em) {
    color: var(--gold-500);
    font-style: normal;
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
}

@keyframes hero-say {
  0% {
    opacity: 0;
    transform: translateY(18px);
  }

  18% {
    opacity: 1;
    transform: translateY(0);
  }

  72% {
    opacity: 1;
    transform: translateY(0);
  }

  100% {
    opacity: 0;
    transform: translateY(-18px);
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
    translate: none;
  }

  .hero-say {
    position: static;
    animation: none;
    opacity: 1;
  }
}
</style>
