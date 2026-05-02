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
    class="absolute left-0 w-full pointer-events-none top-auto bottom-0 h-60vh md:(top-0 bottom-auto h-100vh)"
  >
    <canvas ref="canvasRef" class="block w-full h-full" />
  </div>
</template>

<style lang="scss" scoped></style>
