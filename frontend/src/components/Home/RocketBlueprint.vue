<script setup lang="ts">
/*
  The hero's rocket redrawn as a drawing: 1.5px hairline strokes, no
  fills, a gold dashed centre line, over a 34px grid masked to a soft
  radial so it fades out rather than ending on an edge.

  Same geometry as RocketSvg on purpose — it is the same vehicle, and two
  silhouettes for one object is how a page stops being about a real
  thing.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const { height = '2 400 mm', diameter = '⌀ 102' } = defineProps<{
  /** Rendered as the vertical dimension callout. */
  height?: string;
  diameter?: string;
}>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
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
  <div class="blueprint">
    <span class="blueprint__dim blueprint__dim--h">{{ height }}</span>
    <span class="blueprint__dim blueprint__dim--d">{{ diameter }}</span>

    <svg viewBox="0 0 120 270" role="img" :aria-label="$t('rocket.blueprintAlt')">
      <path
        d="M60 6 C74 40 84 78 84 120 L84 236 L36 236 L36 120 C36 78 46 40 60 6 Z"
        fill="none"
        stroke="var(--line-hi)"
        stroke-width="1.5"
      />
      <path d="M36 198 L12 254 L36 240 Z" fill="none" stroke="var(--line-hi)" stroke-width="1.5" />
      <path d="M84 198 L108 254 L84 240 Z" fill="none" stroke="var(--line-hi)" stroke-width="1.5" />
      <path
        d="M42 236 L78 236 L84 258 L36 258 Z"
        fill="none"
        stroke="var(--line-hi)"
        stroke-width="1.5"
      />
      <!-- The axis, which is what makes it read as a drawing rather than
           as an outline. -->
      <line
        x1="60"
        y1="6"
        x2="60"
        y2="262"
        stroke="var(--gold-500)"
        stroke-width="1"
        stroke-dasharray="5 5"
        opacity=".5"
      />
      <line x1="36" y1="120" x2="84" y2="120" stroke="var(--line)" stroke-width="1" />
      <line x1="36" y1="152" x2="84" y2="152" stroke="var(--line)" stroke-width="1" />
      <rect x="36" y="152" width="48" height="15" fill="var(--gold-500)" opacity=".9" />
      <circle cx="60" cy="128" r="12" fill="none" stroke="var(--gold-500)" stroke-width="2" />
    </svg>
  </div>
</template>

<style lang="scss" scoped>
.blueprint {
  display: flex;
  justify-content: center;
  position: relative;
  padding: 30px clamp(28px, 5vw, 60px);

  // The grid, faded by a mask rather than cropped: a hard edge on a
  // technical grid reads as a mistake.
  &::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
      linear-gradient(var(--line) 1px, transparent 1px),
      linear-gradient(90deg, var(--line) 1px, transparent 1px);
    background-size: 34px 34px;
    opacity: 0.45;
    mask-image: radial-gradient(70% 70% at 50% 50%, #000 18%, transparent 76%);
  }

  svg {
    display: block;
    position: relative;
    width: clamp(104px, 15vw, 176px);
    height: auto;
    overflow: visible;
  }
}

.blueprint__dim {
  position: absolute;
  color: var(--text-low);
  font-size: 10.5px;
  font-family: var(--font-mono);
  letter-spacing: 0.1em;
}

.blueprint__dim--h {
  top: 50%;
  left: 0;
  writing-mode: vertical-rl;
  translate: 0 -50%;
}

.blueprint__dim--d {
  top: 22%;
  right: 0;
}
</style>
