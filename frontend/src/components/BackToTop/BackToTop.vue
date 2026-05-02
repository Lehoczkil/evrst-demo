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
      class="back-to-top"
      :aria-label="t('button.backToTop')"
      @click="scrollToTop"
    >
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="18 15 12 9 6 15" />
      </svg>
    </button>
  </transition>
</template>

<style lang="scss" scoped>
.back-to-top {
  position: fixed;
  right: 24px;
  bottom: 48px;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background-color: rgba(0, 0, 0, 0.4);
  color: white;
  border: 1px solid var(--card-border);
  border-radius: 999px;
  cursor: pointer;
  transition: background-color 150ms ease, border-color 150ms ease;

  &:hover {
    background-color: var(--color-primary);
    border-color: var(--color-primary);
  }
}
</style>
