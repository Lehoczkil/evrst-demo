<script setup lang="ts">
import { useRocketScene } from '@/composables/useRocketScene';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const props = defineProps<{
  url: string;
  scale?: number | [number, number, number];
  position?: [number, number, number];
}>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const canvasRef = ref<HTMLCanvasElement | null>(null);
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
useRocketScene(canvasRef, {
  url: props.url,
  scale: props.scale,
  position: props.position,
});
</script>

<template>
  <div
    class="absolute inset-x-0 z-0 pointer-events-none top-auto bottom-0 h-60vh md:(top-0 bottom-auto h-100vh)"
  >
    <canvas ref="canvasRef" class="block w-full h-full" />
  </div>
</template>

<style lang="scss" scoped></style>
