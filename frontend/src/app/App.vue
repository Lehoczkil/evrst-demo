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
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
onMounted(() => {
  metaStore.setMeta({
    title: 'Escape Velocity Rocketry Student Team',
    description: 'Escape Velocity Rocketry Student Team — Óbuda University.',
    theme: '#0c0d0e',
  });
});
</script>

<template>
  <Meta />
  <Header />
  <main>
    <RouterView v-slot="{ Component }">
      <transition name="router-fade" mode="out-in">
        <component :is="Component" />
      </transition>
    </RouterView>
  </main>
  <Footer />
  <BackToTop />
</template>

<style src="./App.scss" lang="scss"></style>
