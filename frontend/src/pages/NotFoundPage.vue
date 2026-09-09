<script setup lang="ts">
import { motion } from 'motion-v';
import { heroBuild } from '@/components/Hero/anims';

/*
  The 404.

  No parallax: a page that is not there does not deserve seven layers. It
  borrows the hero's sky gradient at 40dvh so it still belongs to the
  site, and then gets out of the way with two routes onward.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();
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
</script>

<template>
  <div class="notfound surface-ignore">
    <HtmlTitle :title="t('notFound.title')" />
    <div class="container">
      <motion.p v-bind="heroBuild()" class="notfound__code">{{ t('notFound.code') }}</motion.p>
      <motion.h1 v-bind="heroBuild(0.06)" class="notfound__title">
        {{ t('notFound.title') }}
      </motion.h1>
      <motion.p v-bind="heroBuild(0.12)" class="lede notfound__body">
        {{ t('notFound.body') }}
      </motion.p>
      <motion.div v-bind="heroBuild(0.18)" class="cta-row">
        <RouterLink to="/" class="btn">{{ t('notFound.home') }}</RouterLink>
        <RouterLink to="/join-us" class="btn btn--ghost">{{ t('join.cta') }}</RouterLink>
      </motion.div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.notfound {
  display: flex;
  align-items: center;
  min-height: 40dvh;
  padding-block: clamp(96px, 14vw, 200px);
  background:
    radial-gradient(120% 90% at 50% 0%, #101725 0%, #07080d 55%, var(--ink-0) 100%);
}

.notfound__code {
  color: var(--gold-500);
  font-weight: 700;
  font-size: clamp(3rem, 9vw, 7rem);
  line-height: 1;
  font-family: var(--font-display);
  letter-spacing: -0.04em;
}

.notfound__title {
  margin-top: 12px;
  font-size: var(--fs-h1);
}

.notfound__body {
  margin-top: 20px;
}

.cta-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 32px;
}
</style>
