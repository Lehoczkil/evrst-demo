<script setup lang="ts">
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();

const projects = computed(() => [
  { title: t('about.project1.title'), description: t('about.project1.description') },
  { title: t('about.project2.title'), description: t('about.project2.description') },
  { title: t('about.project3.title'), description: t('about.project3.description') },
]);

const goals = computed(() => [
  { title: t('about.goal1.title'), description: t('about.goal1.description') },
  { title: t('about.goal2.title'), description: t('about.goal2.description') },
  { title: t('about.goal3.title'), description: t('about.goal3.description') },
]);
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div class="about">
    <div class="about__block">
      <h3 class="about__subtitle">{{ t('about.whoWeAre') }}</h3>
      <p class="about__body">{{ t('about.whoWeAreBody') }}</p>
    </div>

    <div class="about__block">
      <h3 class="about__subtitle">{{ t('about.projects') }}</h3>
      <div class="about__grid">
        <div v-for="(item, idx) in projects" :key="`p-${idx}`" class="about__card">
          <div class="about__card-title">{{ item.title }}</div>
          <p class="about__card-body">{{ item.description }}</p>
        </div>
      </div>
    </div>

    <div class="about__block">
      <h3 class="about__subtitle">{{ t('about.goals') }}</h3>
      <div class="about__grid">
        <div v-for="(item, idx) in goals" :key="`g-${idx}`" class="about__card">
          <div class="about__card-title">{{ item.title }}</div>
          <p class="about__card-body">{{ item.description }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import 'breakpoints';

.about {
  display: flex;
  flex-direction: column;
  gap: 40px;

  &__block {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  &__subtitle {
    font-size: 24px;
    font-weight: 600;
    text-transform: uppercase;
    line-height: 1.2;
    color: var(--color-primary);
    margin: 0;
  }

  &__body {
    font-size: 16px;
    line-height: 1.6;
    margin: 0;
  }

  &__grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;

    @include media-up(sm) {
      grid-template-columns: repeat(2, 1fr);
    }

    @include media-up(md) {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  &__card {
    padding: 20px;
    background: var(--card-bg);
    border: 1px solid var(--card-border-accent);
    border-radius: 8px;
    height: 100%;
  }

  &__card-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
  }

  &__card-body {
    font-size: 14px;
    color: var(--color-dimmed);
    line-height: 1.5;
    margin: 0;
  }
}
</style>
