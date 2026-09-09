<script setup lang="ts">
import type { ApplicationFieldOption } from '@/types/applicationForm';

/*
  Radio and checkbox questions, rendered as a row of pills.

  The seeded questions are short choice sets, and a pill row reads and
  taps better than a stack of dots — especially on a phone, where a 44px
  pill is a real target and a radio's circle is not.

  It stays a real <fieldset> of <input>s with the label wrapping each one,
  so keyboard, arrow-key group navigation and screen-reader semantics are
  the platform's. The pill is only what the input LOOKS like: the native
  control is visually hidden, never `display: none`, because a
  display-none input is not focusable and drops out of the tab order.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const {
  id,
  options,
  multiple = false,
  describedBy = undefined,
  invalid = false,
} = defineProps<{
  id: string;
  options: ApplicationFieldOption[];
  /** checkbox questions take many answers, radio takes one. */
  multiple?: boolean;
  describedBy?: string;
  invalid?: boolean;
}>();

const model = defineModel<string | string[]>({ default: '' });
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const isChecked = (value: string) => (Array.isArray(model.value)
  ? model.value.includes(value)
  : model.value === value);

const toggle = (value: string) => {
  if (!multiple) {
    model.value = value;

    return;
  }
  const current = Array.isArray(model.value) ? model.value : [];
  model.value = current.includes(value)
    ? current.filter((entry) => entry !== value)
    : [...current, value];
};
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
  <div
    class="opt-pills"
    :aria-describedby="describedBy"
    :aria-invalid="invalid ? 'true' : undefined"
  >
    <label v-for="option in options" :key="option.value" class="opt-pill">
      <input
        :type="multiple ? 'checkbox' : 'radio'"
        :name="id"
        :value="option.value"
        :checked="isChecked(option.value)"
        @change="toggle(option.value)"
      >
      <span>{{ option.label }}</span>
    </label>
  </div>
</template>

<style lang="scss" scoped>
.opt-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
}

.opt-pill {
  cursor: pointer;

  // Visually hidden, NOT display:none — the input has to stay focusable
  // and in the tab order for the keyboard to reach these at all.
  input {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip-path: inset(50%);
    white-space: nowrap;
  }

  span {
    display: inline-block;
    padding: 9px 15px 10px;
    border: 1px solid var(--line);
    border-radius: 999px;
    color: var(--text-mid);
    font-size: 13.5px;
    transition:
      border-color var(--dur-fast) ease,
      color var(--dur-fast) ease,
      background-color var(--dur-fast) ease;
  }

  &:hover span {
    border-color: var(--line-hi);
    color: var(--text-hi);
  }

  // The checked state is the gold fill; the focus ring is drawn on the
  // pill rather than on the hidden input, which has no box to ring.
  input:checked + span {
    color: var(--ink-0);
    background: var(--gold-500);
    font-weight: 600;
    border-color: var(--gold-500);
  }

  input:focus-visible + span {
    outline: 2px solid var(--gold-500);
    outline-offset: 3px;
  }
}
</style>
