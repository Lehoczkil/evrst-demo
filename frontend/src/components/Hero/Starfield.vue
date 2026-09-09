<script setup lang="ts">
/*
  A starfield as one generated tile, repeated.

  Not a canvas sized to the layer: the stars layer is oversized by its own
  overscan (≈0.92 × the pin on either side), so a full-size canvas would
  be tens of MB of backing store at DPR 2 for a few hundred dots. A 320px
  tile is painted once, turned into a data URI and repeated — no request,
  no per-frame work, and the layer above it is a plain composited box.

  Generated rather than authored because it is decorative noise: hand
  writing forty <circle>s would be longer, larger and no more controlled.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const { size = 320, count = 46, opacity = 1 } = defineProps<{
  /** Tile edge in px. Larger repeats less visibly, costs more to paint. */
  size?: number;
  /** Stars per tile. */
  count?: number;
  opacity?: number;
}>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const tile = ref<string | null>(null);
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const paint = () => {
  const canvas = document.createElement('canvas');
  canvas.width = size;
  canvas.height = size;
  const ctx = canvas.getContext('2d');
  if (!ctx) {
    return;
  }

  for (let i = 0; i < count; i += 1) {
    const x = Math.random() * size;
    const y = Math.random() * size;
    // Mostly sub-pixel dust with a few brighter stars, which is what
    // stops the field reading as an even dot grid.
    const r = Math.random() < 0.86 ? Math.random() * 0.85 + 0.35 : Math.random() * 0.9 + 1.1;
    const a = Math.random() * 0.55 + 0.2;
    ctx.beginPath();
    ctx.arc(x, y, r, 0, Math.PI * 2);
    // One in nine picks up the brand gold, so the field belongs to this
    // page rather than to any night sky.
    ctx.fillStyle = i % 9 === 0 ? `rgba(247,195,104,${a})` : `rgba(226,232,244,${a})`;
    ctx.fill();
  }

  tile.value = canvas.toDataURL('image/png');
};
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const style = computed(() => (tile.value
  ? { backgroundImage: `url(${tile.value})`, opacity: String(opacity) }
  : undefined));
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
onMounted(paint);
</script>

<template>
  <div class="starfield absolute inset-0" :style="style" aria-hidden="true" />
</template>

<style lang="scss" scoped>
.starfield {
  background-position: 50% 0;
  background-repeat: repeat;
  pointer-events: none;
}
</style>
