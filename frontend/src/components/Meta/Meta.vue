<script setup lang="ts">
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const metaStore = useMetaStore();
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const meta = computed(() => metaStore.meta);
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <teleport to="head">
    <title v-if="meta?.title">{{ meta.title }}</title>
    <meta v-if="meta?.description" name="description" :content="meta.description" />
    <meta v-if="meta?.theme" name="theme-color" :content="meta.theme" />
    <meta
      v-if="meta?.ogTitle || meta?.title"
      property="og:title"
      :content="meta?.ogTitle || meta?.title"
    />
    <meta
      v-if="meta?.ogDescription || meta?.description"
      property="og:description"
      :content="meta?.ogDescription || meta?.description"
    />
    <meta v-if="meta?.ogImage" property="og:image" :content="meta.ogImage" />
  </teleport>
</template>
