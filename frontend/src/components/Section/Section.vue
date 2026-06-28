<script setup lang="ts">
import { motion } from 'motion-v';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const props = defineProps<{
  id: string;
  index: number;
  title: string;
}>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const ease: [number, number, number, number] = [0.22, 1, 0.36, 1];
const inViewOptions = { once: true, amount: 0.1 };
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const number = computed(() => `0${props.index + 1}`.slice(-2));
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <section :id="id">
    <div
      class="flex flex-col items-start sm:flex-row sm:items-center sm:justify-between gap-16px mb-32px"
    >
      <div class="flex items-end gap-8px">
        <motion.span
          class="section__number fs-[clamp(28px,6vw,40px)] lh-1 uppercase font-500 inline-block select-none pointer-events-none"
          :initial="{ opacity: 0, x: -32 }"
          :while-in-view="{ opacity: 1, x: 0 }"
          :in-view-options="inViewOptions"
          :transition="{ duration: 0.6, ease }"
        >
          {{ number }}
        </motion.span>
        <motion.div
          :initial="{ opacity: 0, y: 16 }"
          :while-in-view="{ opacity: 1, y: 0 }"
          :in-view-options="inViewOptions"
          :transition="{ duration: 0.55, delay: 0.1, ease }"
        >
          <h2
            class="m-0 fs-[clamp(28px,6vw,40px)] lh-1 uppercase font-[var(--font-family-headline)]"
          >
            {{ title }}
          </h2>
        </motion.div>
      </div>
      <Reveal v-if="$slots.right" :duration="0.4" :delay="0.2" :y="0">
        <slot name="right" />
      </Reveal>
    </div>
    <Reveal :duration="0.5" :delay="0.15" :y="20">
      <slot />
    </Reveal>
  </section>
</template>

<style lang="scss" scoped>
.section__number {
  color: transparent;
  background: linear-gradient(to right, transparent, var(--color-primary));
  background-clip: text;
}
</style>
