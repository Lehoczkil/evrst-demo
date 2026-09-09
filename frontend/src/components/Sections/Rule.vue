<script setup lang="ts">
/*
  The hairline that does the structural work on this site in place of
  borders, shadows and boxes.

  It reads its colour from the surface rather than taking a prop: a rule
  inside a paper section is `--paper-line`, everywhere else `--line`, and
  making that a prop would mean every caller repeating what its own
  section already knows.
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
  <div class="rule" role="presentation" />
</template>

<style lang="scss" scoped>
.rule {
  height: 1px;
  background: var(--line);

  .paper & {
    background: var(--paper-line);
  }
}
</style>
