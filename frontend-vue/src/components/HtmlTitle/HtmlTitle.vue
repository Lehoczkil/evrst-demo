<script setup lang="ts">
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const props = defineProps<{ title: string }>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const metaStore = useMetaStore();
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const fullTitle = computed(() => `${props.title} – Escape Velocity Rocketry Student Team`);
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
watchEffect(() => {
  metaStore.setMeta({ title: fullTitle.value });
});
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <span style="display: none" />
</template>
