<script setup lang="ts">
import { MotionConfig } from 'motion-v';

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
    description: 'Az Escape Velocity Rocketry Student Team az Óbudai Egyetem hallgatói rakétacsapata.',
    theme: '#050506',
  });
});
</script>

<template>
  <!--
    `reduced-motion="user"` makes every motion-v animation on the site —
    the presets and the imperative animate() calls alike — honour the OS
    setting: transforms and layout animations are skipped while opacity
    and colour still run, so content appears instead of flying in.

    Plain CSS animations cannot see this and carry their own
    `@media (prefers-reduced-motion: reduce)` blocks; so does
    `scroll-behavior: smooth` in _base.scss.
  -->
  <MotionConfig reduced-motion="user">
    <Meta />
    <SiteHeader />
    <main>
      <RouterView v-slot="{ Component }">
        <transition name="router-fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </RouterView>
    </main>
    <SiteFooter />
    <SiteNav />
    <BackToTop />
    <ToastHost />
  </MotionConfig>
</template>

<style src="./App.scss" lang="scss"></style>
