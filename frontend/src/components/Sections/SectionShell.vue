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

.section--ink-1 {
  background: var(--ink-1);
}

.section--paper {
  color: var(--on-paper);
  background: var(--paper);

  // On paper, gold is 1.87:1 — a fill colour, never text. The eyebrow
  // takes the darker gold, which clears 4.5:1 at this size.
  :deep(.eyebrow) {
    color: var(--gold-600);
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
