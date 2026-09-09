<script setup lang="ts">
import { AnimatePresence, motion } from 'motion-v';
import { EASE_OUT } from '@/components/Motion/springs';

/*
  One live region for the whole app, mounted once in App.vue.

  `aria-live="polite"` and not `assertive`: a submit result is worth
  announcing but not worth interrupting whatever the reader is in the
  middle of. Errors that block progress are announced by the field itself,
  which is where the fix is.

  Positioned above the nav pill so the two never overlap.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { toasts, dismiss } = useToasts();
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
  <div class="toast-host" role="status" aria-live="polite">
    <AnimatePresence>
      <motion.div
        v-for="toast in toasts"
        :key="toast.id"
        class="toast"
        :class="`toast--${toast.tone}`"
        :initial="{ opacity: 0, y: 12 }"
        :animate="{ opacity: 1, y: 0 }"
        :exit="{ opacity: 0, y: 8 }"
        :transition="{ duration: 0.3, ease: EASE_OUT }"
      >
        <span>{{ toast.text }}</span>
        <button type="button" aria-label="OK" @click="dismiss(toast.id)">✕</button>
      </motion.div>
    </AnimatePresence>
  </div>
</template>

<style lang="scss" scoped>
.toast-host {
  display: grid;
  position: fixed;

  // Clear of the nav pill, which owns the bottom centre.
  bottom: calc(2vh + 64px);
  left: 50%;
  width: min(440px, calc(100vw - 32px));
  z-index: var(--z-nav);
  gap: 8px;
  translate: -50% 0;
  pointer-events: none;
}

.toast {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 14px 16px;
  border: 1px solid var(--line-hi);
  border-radius: var(--radius);
  color: var(--text-hi);
  background: var(--ink-2);
  font-size: 14.5px;
  gap: 14px;
  pointer-events: auto;

  button {
    padding: 0;
    border: 0;
    color: var(--text-low);
    background: transparent;
    font-size: 13px;
    flex: 0 0 auto;
    cursor: pointer;

    &:hover {
      color: var(--text-hi);
    }
  }
}

.toast--success {
  border-color: rgb(67 214 138 / 45%);
}

.toast--error {
  border-color: rgb(242 104 92 / 55%);
}
</style>
