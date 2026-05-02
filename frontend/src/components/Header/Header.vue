<script setup lang="ts">
import Drawer from 'primevue/drawer';
import SelectButton from 'primevue/selectbutton';
import type { Language } from '@/translations';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();
const { locale, setLocale } = useLocale();
const scrolled = ref(false);
const drawerOpen = ref(false);

const localeOptions = [
  { label: 'EN', value: 'en' },
  { label: 'HU', value: 'hu' },
];

const menuItems = computed(() => [
  { label: t('nav.events'), to: '/#events' },
  { label: t('nav.about'), to: '/#about' },
  { label: t('nav.team'), to: '/#team' },
  { label: t('nav.mentors'), to: '/#mentors' },
  { label: t('nav.sponsors'), to: '/#sponsors' },
  { label: t('nav.joinUs'), to: '/join-us' },
]);

const localeModel = computed({
  get: () => locale.value,
  set: (val: string) => setLocale(val as Language),
});

/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const onScroll = () => {
  scrolled.value = window.scrollY > 8;
};

const closeDrawer = () => {
  drawerOpen.value = false;
};

/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
onMounted(() => {
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll);
});
</script>

<template>
  <header class="header" :class="{ scrolled }">
    <div class="container header__inner">
      <div class="header__col header__col--logo">
        <span class="header__logo-wrap">
          <EvrstLogo :height="scrolled ? '36px' : '80px'" />
        </span>
      </div>

      <div class="header__col header__col--center">
        <nav class="header__nav">
          <RouterLink v-for="item in menuItems" :key="item.to" :to="item.to" class="header__link">
            {{ item.label }}
          </RouterLink>
        </nav>
      </div>

      <div class="header__col header__col--end">
        <div class="header__locale">
          <SelectButton
            v-model="localeModel"
            :options="localeOptions"
            option-label="label"
            option-value="value"
            :allow-empty="false"
            size="small"
          />
        </div>
        <button
          type="button"
          class="header__burger"
          aria-label="Toggle navigation"
          @click="drawerOpen = true"
        >
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>
    </div>

    <Drawer v-model:visible="drawerOpen" position="right" class="header__drawer">
      <nav class="header__drawer-nav">
        <RouterLink
          v-for="item in menuItems"
          :key="item.to"
          :to="item.to"
          class="header__drawer-link"
          @click="closeDrawer"
        >
          {{ item.label }}
        </RouterLink>
      </nav>
      <div class="mt-24px">
        <SelectButton
          v-model="localeModel"
          :options="localeOptions"
          option-label="label"
          option-value="value"
          :allow-empty="false"
          size="small"
        />
      </div>
    </Drawer>
  </header>
</template>

<style lang="scss" scoped>
@import 'breakpoints';

.header {
  position: fixed;
  top: 0;
  right: 0;
  left: 0;
  border-bottom: 1px solid transparent;
  background-color: transparent;
  transition: background-color 200ms ease, backdrop-filter 200ms ease, border-color 200ms ease;
  z-index: 100;

  &.scrolled {
    background-color: rgb(20 21 23 / 60%);
    backdrop-filter: blur(12px);
    backdrop-filter: blur(12px);
    border-bottom-color: var(--card-border);

    .header__inner {
      padding-top: 8px;
      padding-bottom: 8px;
    }
  }

  &__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding-top: 16px;
    padding-bottom: 16px;
    transition: padding 200ms ease;
  }

  &__col {
    flex: 1 1 0;
    display: flex;
    align-items: center;

    &--logo {
      justify-content: flex-start;
      position: relative;
    }

    &--center {
      justify-content: center;

      @include media-down(md) {
        display: none;
      }
    }

    &--end {
      justify-content: flex-end;
      gap: 12px;
    }
  }

  &__logo-wrap {
    display: inline-flex;

    :deep(img) {
      margin-bottom: -40px;

      @include media-down(sm) {
        height: 56px !important;
        margin-bottom: -28px;
      }
    }
  }

  &.scrolled &__logo-wrap :deep(img) {
    margin-bottom: 0;

    @include media-down(sm) {
      height: 28px !important;
    }
  }

  &__nav {
    display: flex;
    gap: 32px;
    text-transform: uppercase;
    white-space: nowrap;
  }

  &__link {
    color: white;
    font-size: 14px;
    text-decoration: none;
    transition: color 150ms ease;

    &:hover {
      color: var(--color-primary);
    }
  }

  &__locale {
    @include media-down(md) {
      display: none;
    }
  }

  &__burger {
    display: none;
    justify-content: space-around;
    width: 32px;
    height: 32px;
    padding: 6px;
    border: none;
    background: transparent;
    flex-direction: column;
    cursor: pointer;

    @include media-down(md) {
      display: flex;
    }

    span {
      display: block;
      width: 100%;
      height: 2px;
      background-color: white;
    }
  }

  &__drawer-nav {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  &__drawer-link {
    color: white;
    font-weight: 500;
    font-size: 18px;
    text-transform: uppercase;
    text-decoration: none;
  }
}
</style>
