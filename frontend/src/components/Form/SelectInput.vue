<script setup lang="ts">
import type { ApplicationFieldOption } from '@/types/applicationForm';

/*
  A native <select>.

  Deliberately not a custom listbox: the native control brings keyboard
  navigation, type-ahead, screen-reader semantics and the platform's own
  mobile picker, and a hand-rolled replacement owes all of that back
  before it has earned anything. The only styling is the shared `.input`
  rule plus room for the platform arrow.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const {
  id,
  options,
  placeholder = '',
  describedBy = undefined,
  invalid = false,
  required = false,
} = defineProps<{
  id: string;
  options: ApplicationFieldOption[];
  placeholder?: string;
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
  <select
    :id="id"
    v-model="model"
    class="input"
    :required="required"
    :aria-describedby="describedBy"
    :aria-invalid="invalid ? 'true' : undefined"
  >
    <option value="" disabled>{{ placeholder }}</option>
    <option v-for="option in options" :key="option.value" :value="option.value">
      {{ option.label }}
    </option>
  </select>
</template>

<style lang="scss" scoped></style>
