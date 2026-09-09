<script setup lang="ts">
/*
  A thin wrapper over <input>, so the type switch in JoinUsPage reads as a
  list of controls rather than a list of attribute soup.

  The styling lives in _controls.scss as `.input`, shared with the
  textarea and the select — one definition of what an answer looks like.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const {
  id,
  type = 'text',
  placeholder = '',
  maxlength = undefined,
  describedBy = undefined,
  invalid = false,
  required = false,
} = defineProps<{
  id: string;
  type?: 'text' | 'email';
  placeholder?: string;
  maxlength?: number;
  describedBy?: string;
  invalid?: boolean;
  required?: boolean;
}>();

const model = defineModel<string>({ default: '' });
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
  <input
    :id="id"
    v-model="model"
    class="input"
    :type="type"
    :placeholder="placeholder"
    :maxlength="maxlength"
    :required="required"
    :aria-describedby="describedBy"
    :aria-invalid="invalid ? 'true' : undefined"
  >
</template>

<style lang="scss" scoped></style>
