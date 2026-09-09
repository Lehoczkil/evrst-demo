<script setup lang="ts">
/*
  The long-answer control. Same `.input` styling as the single-line one,
  with a min-height and vertical resize only — horizontal resize would
  break the column the form is laid out in.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const {
  id,
  placeholder = '',
  maxlength = undefined,
  describedBy = undefined,
  invalid = false,
  required = false,
} = defineProps<{
  id: string;
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
  <textarea
    :id="id"
    v-model="model"
    class="input"
    rows="4"
    :placeholder="placeholder"
    :maxlength="maxlength"
    :required="required"
    :aria-describedby="describedBy"
    :aria-invalid="invalid ? 'true' : undefined"
  />
</template>

<style lang="scss" scoped></style>
