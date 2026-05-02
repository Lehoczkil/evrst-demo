<script setup lang="ts">
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
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <Hero />

  <div style="height: 32px" />

  <div class="section-wrap section-wrap--dark">
    <div class="container">
      <Section id="about" :index="1" :title="t('section.about')">
        <About />
      </Section>
    </div>
  </div>

  <div class="section-wrap section-wrap--dark">
    <div class="container">
      <Section id="team" :index="2" :title="t('section.team')">
        <p style="color: var(--color-dimmed)">{{ t('placeholder.team') }}</p>
      </Section>
    </div>
  </div>

  <Outro />
</template>

<style lang="scss" scoped>
.section-wrap {
  padding-block: 40px;
  position: relative;

  &--dark {
    background-color: var(--bg-dark);
  }

  &--gray {
    background-color: var(--bg-gray);
  }
}
</style>
