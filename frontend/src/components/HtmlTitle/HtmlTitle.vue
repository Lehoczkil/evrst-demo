<script setup lang="ts">
/*
  Per-page head copy. Every routed page renders one.

  `description` and `robots` are written on EVERY page even when a page
  doesn't pass them, because the store merges: without the reset, the 404's
  `noindex` would follow the visitor to the next page they opened, and a
  page with no description of its own would keep the previous page's.
*/
import { SITE_NAME } from '@/lib/seo';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const props = defineProps<{
  title: string;
  description?: string;
  /** e.g. 'noindex, follow' — omit on anything that should be indexed. */
  robots?: string;
}>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const metaStore = useMetaStore();
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const fullTitle = computed(() => `${props.title} – ${SITE_NAME}`);
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
watchEffect(() => {
  metaStore.setMeta({
    title: fullTitle.value,
    description: props.description,
    robots: props.robots ?? null,
  });
});
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <span style="display: none" />
</template>
