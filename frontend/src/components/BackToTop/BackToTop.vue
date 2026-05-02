<script setup lang="ts">
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();
const visible = ref(false);
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const onScroll = () => {
  visible.value = window.scrollY > 300;
};

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
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
  <transition name="fade">
    <button
      v-if="visible"
      type="button"
      class="fixed right-24px bottom-48px z-100 flex items-center justify-center w-40px h-40px rounded-full border border-cardBorder text-white bg-[rgb(0_0_0_/_40%)] cursor-pointer transition-[background-color,border-color] duration-150 hover:(bg-primary border-primary)"
      :aria-label="t('button.backToTop')"
      @click="scrollToTop"
    >
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="18 15 12 9 6 15" />
      </svg>
    </button>
  </transition>
</template>

<style lang="scss" scoped></style>
