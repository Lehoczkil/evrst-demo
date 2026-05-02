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
  <header
    class="header fixed top-0 right-0 left-0 z-100 border-b border-transparent bg-transparent transition-[background-color,backdrop-filter,border-color] duration-200"
    :class="{ scrolled }"
  >
    <div
      class="container header__inner flex items-center justify-between gap-16px py-16px transition-[padding] duration-200"
    >
      <div class="flex flex-1 items-center justify-start relative">
        <span class="header__logo-wrap inline-flex">
          <EvrstLogo :height="scrolled ? '36px' : '80px'" />
        </span>
      </div>

      <div class="hidden md:flex flex-1 items-center justify-center">
        <nav class="flex gap-32px uppercase whitespace-nowrap">
          <RouterLink
            v-for="item in menuItems"
            :key="item.to"
            :to="item.to"
            class="text-white fs-14px no-underline transition-colors duration-150 hover:text-primary"
          >
            {{ item.label }}
          </RouterLink>
        </nav>
      </div>

      <div class="flex flex-1 items-center justify-end gap-12px">
        <div class="hidden md:block">
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
          class="w-32px h-32px p-6px border-none bg-transparent flex-col justify-around cursor-pointer flex md:hidden"
          aria-label="Toggle navigation"
          @click="drawerOpen = true"
        >
          <span class="block w-full h-2px bg-white"></span>
          <span class="block w-full h-2px bg-white"></span>
          <span class="block w-full h-2px bg-white"></span>
        </button>
      </div>
    </div>

    <Drawer v-model:visible="drawerOpen" position="right" class="header__drawer">
      <nav class="flex flex-col gap-16px">
        <RouterLink
          v-for="item in menuItems"
          :key="item.to"
          :to="item.to"
          class="text-white uppercase fs-18px font-500 no-underline"
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
  &.scrolled {
    background-color: rgb(20 21 23 / 60%);
    backdrop-filter: blur(12px);
    border-bottom-color: var(--card-border);

    .header__inner {
      padding-top: 8px;
      padding-bottom: 8px;
    }
  }

  &__logo-wrap {
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
}
</style>
