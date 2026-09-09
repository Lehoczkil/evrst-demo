<script setup lang="ts">
/*
  The label / help / error wrapper every control sits in, so all of them
  inherit identical geometry and focus treatment.

  The help and error ids are handed to the control through the slot, which
  is what wires `aria-describedby` without every call site repeating it.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const {
  id,
  label,
  help = null,
  error = null,
  required = false,
} = defineProps<{
  id: string;
  label: string;
  help?: string | null;
  error?: string | null;
  required?: boolean;
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
/*
  Only ids that exist: pointing aria-describedby at an absent element is
  worse than omitting it, because a screen reader announces nothing and
  the author believes it is wired.
*/
const describedBy = computed(() => [
  help ? `${id}-help` : null,
  error ? `${id}-error` : null,
].filter(Boolean).join(' ') || undefined);
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div class="field" :data-invalid="!!error">
    <label :for="id">
      {{ label }}
      <b v-if="required" :aria-label="t('form.required')">*</b>
    </label>

    <slot :id="id" :described-by="describedBy" :invalid="!!error" />

    <p v-if="help" :id="`${id}-help`" class="field__help">{{ help }}</p>
    <!-- role=alert so the message is announced when it appears, not only
         when the field is next focused. -->
    <p v-if="error" :id="`${id}-error`" class="field__error" role="alert">{{ error }}</p>
  </div>
</template>

<style lang="scss" scoped>
.field {
  margin-bottom: 26px;

  label {
    display: block;
    margin-bottom: 9px;
    color: var(--text-low);
    font-weight: 500;
    font-size: 10.5px;
    font-family: var(--font-mono);
    letter-spacing: 0.15em;
    text-transform: uppercase;
  }

  b {
    color: var(--gold-500);
    font-weight: 500;
  }
}

.field__help {
  margin-top: 9px;
  color: var(--text-low);
  font-size: 10.5px;
  font-family: var(--font-mono);
  letter-spacing: 0.05em;
}

.field__error {
  margin-top: 9px;
  color: var(--error);
  font-size: 13px;
}
</style>
