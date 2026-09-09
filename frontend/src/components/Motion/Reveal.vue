<script setup lang="ts">
import { motion } from 'motion-v';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const {
  delay = 0,
  duration = 0.5,
  y = 16,
  x = 0,
  scale = 1,
  immediate = false,
  amount = 0.15,
  once = true,
} = defineProps<{
  delay?: number;
  duration?: number;
  y?: number;
  x?: number;
  scale?: number;
  immediate?: boolean;
  amount?: number;
  once?: boolean;
}>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const initial = computed(() => ({
  opacity: 0,
  y,
  x,
  ...(scale !== 1 ? { scale } : {}),
}));

const target = computed(() => ({
  opacity: 1,
  y: 0,
  x: 0,
  ...(scale !== 1 ? { scale: 1 } : {}),
}));

const transition = computed(() => ({
  duration,
  delay,
  ease: [0.22, 1, 0.36, 1] as [number, number, number, number],
}));
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <motion.div
    v-if="immediate"
    :initial="initial"
    :animate="target"
    :transition="transition"
  >
    <slot />
  </motion.div>
  <motion.div
    v-else
    :initial="initial"
    :while-in-view="target"
    :in-view-options="{ once, amount }"
    :transition="transition"
  >
    <slot />
  </motion.div>
</template>

<style lang="scss" scoped></style>
