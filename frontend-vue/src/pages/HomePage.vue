<script setup lang="ts">
import { HomeRequests } from '@/services/requests/HomeRequests';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();

const { data: page } = useQuery({
  key: ['home-page'],
  request: () => HomeRequests.page(),
  cache: true,
  staleTime: 60,
  refetchTime: 600,
});
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const rocketObject = computed(() =>
  page.value?.objects?.find((o) => o.key === 'rocket'),
);
const rocketScale = computed(() => page.value?.payload?.data?.rocket?.scale);
const rocketPosition = computed(() => page.value?.payload?.data?.rocket?.position);
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <Hero />

  <RocketScene
    v-if="rocketObject?.url"
    :url="rocketObject.url"
    :scale="rocketScale"
    :position="rocketPosition"
  />

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
