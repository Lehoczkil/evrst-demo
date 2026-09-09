<script setup lang="ts">
/*
  One collection failed. The section keeps its heading and offers a retry;
  the rest of the page carries on.

  Deliberately not a 404 and not a page-level error: telling a reader the
  content does not exist when the server is merely down is a lie, and one
  dead collection must not take the page with it.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const emit = defineEmits<{ retry: [] }>();
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
  <div class="fetch-error" role="alert">
    <p class="fetch-error__text">
      <slot>{{ t('state.fetchFailed') }}</slot>
    </p>
    <button type="button" class="btn btn--ghost" @click="emit('retry')">
      {{ t('state.retry') }}
    </button>
  </div>
</template>

<style lang="scss" scoped>
.fetch-error {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  align-items: center;
  justify-content: space-between;
  padding-block: clamp(24px, 3.4vw, 40px);
  border-top: 1px solid var(--line);

  .paper & {
    border-top-color: var(--paper-line);
  }
}

.fetch-error__text {
  max-width: 52ch;
  color: var(--text-mid);

  .paper & {
    color: var(--on-paper-mid);
  }
}
</style>
