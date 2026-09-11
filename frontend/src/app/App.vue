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
      <!--
        EVERY routed page must have a SINGLE ELEMENT root.

        `mode="out-in"` waits for the outgoing page's leave transition to
        finish before mounting the incoming one, and a fragment or comment
        root has nothing to run that transition on — so the wait never
        ends and the next page never mounts. That was the join-us → home
        bug: JoinUsPage's root was `<HtmlTitle>` + `<SectionShell>`, Vue
        warned "renders non-element root node that cannot be animated",
        and clicking the wordmark left `<main>` empty. A `v-if` chain with
        no `v-else` is the same trap — it renders a comment node.
      -->
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
