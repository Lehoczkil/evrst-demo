<script setup lang="ts">
import { useLanguage } from '@/composables/useLanguage';
import { LANGUAGES, type Language } from '@/translations';

/*
  HU / EN, seated in the nav pill.

  It lives there because the header is only a wordmark — there is no
  top-right corner left to put it in. It inherits the pill's
  `surface-{mode}` class along with everything else in there, so it
  inverts with the rest of the chrome and needs no sampling of its own.

  Two <button>s in a role="group", not a <select>: there are exactly two
  options, both fit, and a native select on this would open a system menu
  over the page for a one-tap choice. The active cell takes a gold
  underline rather than a fill, so it never competes with the pill's
  active-route dot two pixels away.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
/*
  `switchTo` rather than `setLocale`: changing language also rewrites the
  URL to the other locale's spelling of the route we are on, so
  /csatlakozz and /join-us stay in step with the language shown.
*/
const { locale, switchTo } = useLanguage();
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const pick = (next: Language) => {
  void switchTo(next);
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
  <span
    class="lang flex items-center gap-1px ml-6px pl-7px"
    role="group"
    aria-label="Nyelv / Language"
  >
    <button
      v-for="lang in LANGUAGES"
      :key="lang"
      type="button"
      class="lang__btn relative px-8px pt-7px pb-9px border-0 bg-transparent cursor-pointer font-mono fs-11px font-500 ls-[0.1em] uppercase"
      :aria-pressed="locale === lang"
      @click="pick(lang)"
    >
      {{ lang }}
    </button>
  </span>
</template>

<style lang="scss" scoped>
.lang {
  border-left: 1px solid rgb(5 5 6 / 18%);
}

.lang__btn {
  color: rgb(5 5 6 / 45%);
  transition: color 0.2s;

  &:hover,
  &[aria-pressed='true'] {
    color: var(--ink-0);
  }

  // The gold underline marks the active locale. ::after rather than a
  // border so it can inset from the tap target's own padding.
  &[aria-pressed='true']::after {
    content: '';
    position: absolute;
    inset: auto 7px 4px;
    height: 1.5px;
    background: var(--gold-600);
  }
}
</style>
