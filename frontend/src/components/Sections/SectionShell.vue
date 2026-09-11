<script setup lang="ts">
/*
  Every section's frame: eyebrow, heading, hairline rule, optional right
  slot, content. Declared once so the geometry cannot drift between nine
  sections.

  The eyebrow carries a COUNT, not an ordinal. An earlier draft had
  `01 —`, `02 —`, `03 —` here; that was decoration pretending to be
  structure. The home page is not a sequence, so ordinals encode nothing a
  reader needs, and they are one of the more recognisable
  generated-design tells. Each eyebrow instead states something true and
  derived from the data it introduces — `19 tag · 9 csoport`,
  `3 rakéta · 1 repült` — which is the mission-console vernacular doing
  real work rather than wearing a costume.

  (The one place ordinal structure earns its keep is the programme
  timeline, whose content genuinely is a sequence.)
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const {
  id = undefined,
  eyebrow = '',
  title = '',
  surface = 'ink-0',
  wide = false,
} = defineProps<{
  id?: string;
  eyebrow?: string;
  title?: string;
  /** Which of the three surfaces this section paints. */
  surface?: 'ink-0' | 'ink-1' | 'paper';
  /** Skip the container's max-width, for full-bleed content. */
  wide?: boolean;
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
  <!--
    `paper` is a real class, not just a modifier: useSurfaceMode falls
    back to `hit.closest('.paper')` when it finds no opaque background
    anywhere up the chain, so the chrome can still tell which side of the
    page it is over.
  -->
  <section
    :id="id"
    class="section"
    :class="[`section--${surface}`, { paper: surface === 'paper' }]"
  >
    <!--
      Background art goes HERE, outside the container.

      Anything full-bleed put in the default slot is laid out inside
      `.container` — max-width 1440px with a page gutter — so on a wide
      screen it stops short of both edges and paints a visible rectangle
      of itself. The rocket section's starfield and its gold glow both did
      exactly that: the field ended mid-page, and the radial was centred
      on the container rather than on the viewport, so it read as an
      off-centre gradient. Full-bleed decoration needs the section box,
      not the reading measure.
    -->
    <slot name="bleed" />

    <div :class="wide ? 'w-full' : 'container'">
      <div v-if="eyebrow || title || $slots.right" class="section__head">
        <div>
          <p v-if="eyebrow" class="eyebrow">{{ eyebrow }}</p>
          <h2 v-if="title" class="section__title">{{ title }}</h2>
        </div>
        <slot name="right" />
      </div>
      <Rule v-if="eyebrow || title" class="section__rule" />

      <slot />
    </div>
  </section>
</template>

<style lang="scss" scoped>
.section {
  position: relative;
  padding-block: var(--section-y);
}

// Everything in the reading measure sits above the bleed slot's art,
// which is absolutely positioned against the section box.
.section > .container,
.section > .w-full {
  position: relative;
  z-index: 1;
}

.section--ink-1 {
  background: var(--ink-1);
}

.section--paper {
  color: var(--on-paper);

  /*
    A FLAT colour, deliberately — see --paper-surface in _tokens.scss for
    why it is no longer the logo's off-white.

    Not a gradient ramping out of the ink either side, though that was
    tried: `surfaceModeAt` decides the wordmark's colour by reading the
    first opaque `background-color` under it, so a gradient band is
    invisible to it and the mark would flip to ink while still sitting
    over black. Softening this edge properly means teaching the sampler
    to evaluate a gradient at a given y, which is a bigger change than
    the edge is worth.
  */
  background: var(--paper-surface);

  /*
    On paper, --gold-500 is a FILL colour and never text. The eyebrow
    takes --gold-700, which clears 4.5:1 against --paper-surface;
    --gold-600 is only 2.1:1 there and was never the 4.5:1 an older
    comment here claimed.
  */
  :deep(.eyebrow) {
    color: var(--gold-700);
  }

  :deep(.mono-label) {
    color: var(--on-paper-mid);
  }

  :deep(.lede) {
    color: var(--on-paper-mid);
  }
}

.section__head {
  display: flex;
  flex-wrap: wrap;
  gap: 24px;
  align-items: flex-end;
  justify-content: space-between;
}

.section__title {
  margin-top: 10px;
  font-size: var(--fs-h2);
}

.section__rule {
  margin-top: clamp(26px, 3.6vw, 42px);
}
</style>
