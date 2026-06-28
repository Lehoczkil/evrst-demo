<script setup lang="ts">
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const {
  topColor,
  bottomColor,
  height = 80,
  drop = 32,
  strokeWidth = 5,
} = defineProps<{
  topColor: string;
  bottomColor: string;
  height?: number;
  drop?: number;
  strokeWidth?: number;
}>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const w = 100;
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const half = computed(() => height / 2);
const dropHalf = computed(() => drop / 2);

const topPoints = computed(
  () => `0,0 ${w},0 ${w},${half.value - dropHalf.value} 0,${half.value + dropHalf.value}`,
);
const bottomPoints = computed(
  () =>
    `0,${half.value + dropHalf.value} ${w},${half.value - dropHalf.value} ${w},${height} 0,${height}`,
);
const lineY1 = computed(() => half.value + dropHalf.value);
const lineY2 = computed(() => half.value - dropHalf.value);
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <svg
    aria-hidden="true"
    width="100%"
    :height="height"
    :viewBox="`0 0 ${w} ${height}`"
    preserveAspectRatio="none"
    class="block"
  >
    <polygon :points="topPoints" :fill="topColor" />
    <polygon :points="bottomPoints" :fill="bottomColor" />
    <line
      :x1="0"
      :y1="lineY1"
      :x2="w"
      :y2="lineY2"
      stroke="var(--color-primary)"
      :stroke-width="strokeWidth"
      vector-effect="non-scaling-stroke"
    />
  </svg>
</template>

<style lang="scss" scoped></style>
