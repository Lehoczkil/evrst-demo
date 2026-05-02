<script setup lang="ts">
import { imgUrl, type ImgFit, type ImgFormat } from '@/lib/imgUrl';
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const {
  src,
  path,
  width,
  height,
  alt = '',
  loading = 'lazy',
  aspectRatio,
  fit = 'cover',
  quality = 82,
  formats = ['avif', 'webp', 'jpg'],
  lqip = true,
  sizes,
} = defineProps<{
  src?: string;
  path?: string;
  width?: number;
  height?: number;
  alt?: string;
  loading?: 'lazy' | 'eager';
  aspectRatio?: string;
  fit?: ImgFit;
  quality?: number;
  formats?: ImgFormat[];
  lqip?: boolean;
  sizes?: string;
}>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const lqipUri = ref<string | null>(null);
const intrinsic = ref<{ w: number; h: number } | null>(null);
const isLoaded = ref(false);
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const buildSrcset = (format: ImgFormat) => {
  if (!path) return '';
  const w = width;
  return [1, 2, 3]
    .map((dpr) => `${imgUrl(path, { width: w, height, format, quality, fit, dpr })} ${dpr}x`)
    .join(', ');
};

const fetchMeta = async () => {
  if (!path || !lqip) return;
  try {
    const base = (import.meta.env.VITE_API_URL ?? '').replace(/\/+$/, '');
    const res = await fetch(`${base}/img/meta?path=${encodeURIComponent(path)}`);
    if (!res.ok) return;
    const data = (await res.json()) as { width: number; height: number; lqip: string };
    lqipUri.value = data.lqip;
    intrinsic.value = { w: data.width, h: data.height };
  } catch {
    // LQIP is a progressive-enhancement; failing the meta fetch just
    // skips the placeholder, the real image still loads normally.
  }
};
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const isApiMode = computed(() => Boolean(path));

const fallbackSrc = computed(() => {
  if (!path) return src ?? '';
  return imgUrl(path, { width, height, format: 'jpg', quality, fit });
});

const wrapperStyle = computed<Record<string, string> | undefined>(() => {
  const out: Record<string, string> = {};
  if (aspectRatio) out.aspectRatio = aspectRatio;
  return Object.keys(out).length ? out : undefined;
});

const imgStyle = computed<Record<string, string> | undefined>(() => {
  if (!isApiMode.value || !lqipUri.value || isLoaded.value) return undefined;
  return {
    backgroundImage: `url(${lqipUri.value})`,
    backgroundSize: 'cover',
    backgroundPosition: 'center',
  };
});

const intrinsicWidth = computed(() => width ?? intrinsic.value?.w);
const intrinsicHeight = computed(() => height ?? intrinsic.value?.h);
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
onMounted(() => {
  if (isApiMode.value) {
    fetchMeta();
  }
});
</script>

<template>
  <picture v-if="isApiMode" :style="wrapperStyle">
    <source
      v-for="format in formats"
      :key="format"
      :type="`image/${format === 'jpg' ? 'jpeg' : format}`"
      :srcset="buildSrcset(format)"
      :sizes="sizes"
    />
    <img
      :src="fallbackSrc"
      :alt="alt"
      :loading="loading"
      decoding="async"
      :width="intrinsicWidth"
      :height="intrinsicHeight"
      :style="imgStyle"
      @load="isLoaded = true"
    />
  </picture>
  <img v-else :src="src" :alt="alt" :loading="loading" :style="wrapperStyle" />
</template>

<style lang="scss" scoped>
picture {
  display: block;
  width: 100%;
}

img {
  display: block;
  width: 100%;
  height: auto;
}
</style>
