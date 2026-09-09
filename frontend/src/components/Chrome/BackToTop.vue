<script setup lang="ts">
/*
  Back to top, as a rocket.

  A gold disc holding the mark's own silhouette, nose up so it reads as an
  arrow. Bottom RIGHT specifically: the nav pill owns bottom-centre, so
  the two can never collide, and unlike the pill this stays visible over
  the footer — the bottom of a long page is exactly where it is wanted.

  It replaces a right-margin scroll-progress rail from an earlier pass,
  and the reason is worth keeping: that rail occupied a permanent slot on
  every screen and did nothing, where the same mark as a control is useful
  on every one of them. A decorative indicator has to earn its persistence
  against the thing it could have been instead.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();

/** Appears once the hero has handed over. */
const SHOW_AFTER_VH = 0.9;

const visible = ref(false);
let ticking = false;
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const update = () => {
  visible.value = window.scrollY > window.innerHeight * SHOW_AFTER_VH;
};

const schedule = () => {
  if (ticking) {
    return;
  }
  ticking = true;
  requestAnimationFrame(() => {
    ticking = false;
    update();
  });
};

const toTop = () => {
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
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
onMounted(() => {
  update();
  window.addEventListener('scroll', schedule, { passive: true });
  window.addEventListener('resize', schedule, { passive: true });
});

onBeforeUnmount(() => {
  window.removeEventListener('scroll', schedule);
  window.removeEventListener('resize', schedule);
});
</script>

<template>
  <button
    type="button"
    class="to-top fixed z-[var(--z-float)] grid place-items-center w-46px h-46px p-0 border-0 rd-50% cursor-pointer"
    :class="{ 'is-on': visible }"
    :aria-label="t('button.backToTop')"
    @click="toTop"
  >
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path
        fill="currentColor"
        d="M12 1c3 4.2 4.6 8.4 4.6 12.6v4.1h-9.2v-4.1C7.4 9.4 9 5.2 12 1Z"
      />
      <path fill="currentColor" d="M7.4 14.4 3.6 20l3.8-1.9zM16.6 14.4 20.4 20l-3.8-1.9z" />
      <path fill="currentColor" opacity=".55" d="M9.6 18.4h4.8L12 23.4z" />
    </svg>
  </button>
</template>

<style lang="scss" scoped>
.to-top {
  right: clamp(14px, 2.6vw, 34px);

  // Level with the pill's own inset, so the two read as one row of
  // floating controls rather than two unrelated things.
  bottom: calc(2vh + 4px);
  color: var(--ink-0);
  background: var(--gold-500);
  transition:
    opacity 0.35s var(--ease-out),
    transform 0.35s var(--ease-out),
    background-color 0.18s ease;
  opacity: 0;
  transform: translateY(14px) scale(0.9);
  pointer-events: none;

  &.is-on {
    opacity: 1;
    transform: none;
    pointer-events: auto;
  }

  &:hover {
    background: var(--gold-400);
  }

  &:active {
    transform: scale(0.92);
  }

  svg {
    display: block;
    width: 22px;
    height: 22px;
  }
}

// A nudge upward on hover — the one thing a rocket button should do.
@media (hover: hover) {
  .to-top:hover svg {
    animation: to-top-lift 0.5s var(--ease-out);
  }
}

@keyframes to-top-lift {
  0% {
    transform: translateY(0);
  }

  45% {
    transform: translateY(-4px);
  }

  100% {
    transform: translateY(0);
  }
}
</style>
