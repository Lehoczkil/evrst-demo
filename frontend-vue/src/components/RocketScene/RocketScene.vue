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
  <div class="rocket-scene">
    <canvas ref="canvasRef" />
  </div>
</template>

<style lang="scss" scoped>
@import 'breakpoints';

.rocket-scene {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100vh;
  pointer-events: none;

  @include media-down(md) {
    top: auto;
    bottom: 0;
    height: 60vh;
  }

  canvas {
    display: block;
    width: 100%;
    height: 100%;
  }
}
</style>
