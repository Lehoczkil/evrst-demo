<script setup lang="ts">
/*
  What a section says when its collection is empty.

  It renders a sentence, never nothing and never an empty grid. A section
  that vanishes makes the page look broken; a section that says why looks
  maintained. With a CMS behind it, that is the whole difference between
  "unfinished" and "finished but empty".

  The exception is a block that is better absent than explained — the
  hero's countdown removes itself entirely rather than showing
  `— : — : —`. That decision belongs to the caller.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
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
  <p class="empty-note">
    <slot />
  </p>
</template>

<style lang="scss" scoped>
.empty-note {
  border-top: 1px solid var(--line);
  color: var(--text-mid);
  font-size: var(--fs-body-lg);
  padding-block: clamp(28px, 4vw, 48px);

  .paper & {
    color: var(--on-paper-mid);
    border-top-color: var(--paper-line);
  }
}
</style>
