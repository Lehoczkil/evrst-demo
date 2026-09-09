<script setup lang="ts">
/*
  A thin segment rail, one per section, filling as sections are completed.

  Not a wizard: the questions stay on one page, because the schema is
  short and an applicant should be able to see the whole ask before
  starting. This only says how far along they are — there is no
  multi-step navigation to lose answers in.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const { total, done = 0 } = defineProps<{
  total: number;
  /** How many sections have every required question answered. */
  done?: number;
}>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();
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
  <div class="form-progress">
    <div class="form-progress__rail" aria-hidden="true">
      <i v-for="n in total" :key="n" :class="{ 'is-done': n <= done }" />
    </div>
    <span class="mono-label">{{ t('form.step', { current: done, total }) }}</span>
  </div>
</template>

<style lang="scss" scoped>
.form-progress {
  display: flex;
  gap: 14px;
  align-items: center;
  margin-bottom: 30px;
}

.form-progress__rail {
  display: flex;
  flex: 1;
  gap: 8px;
  align-items: center;

  i {
    flex: 1;
    height: 2px;
    border-radius: 2px;
    background: var(--line);
    transition: background-color var(--dur) ease;
  }

  .is-done {
    background: var(--gold-500);
  }
}
</style>
