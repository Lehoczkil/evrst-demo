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
      <Section id="events" :index="0" :title="t('section.events')">
        <Events />
      </Section>
    </div>
  </div>

  <div class="section-wrap section-wrap--gray">
    <div class="container">
      <Section id="about" :index="1" :title="t('section.about')">
        <About />
      </Section>
    </div>
  </div>

  <div class="section-wrap section-wrap--dark">
    <div class="container">
      <Section id="team" :index="2" :title="t('section.team')">
        <Team />
      </Section>
    </div>
  </div>

  <div class="section-wrap section-wrap--dark">
    <div class="container">
      <Section id="mentors" :index="3" :title="t('section.mentors')">
        <Mentors />
      </Section>
    </div>
  </div>

  <div class="section-wrap section-wrap--gray">
    <div class="container">
      <Section id="sponsors" :index="4" :title="t('section.sponsors')">
        <template #right>
          <SectionButton href="mailto:evrstrocket@gmail.com?subject=Sponsorship Inquiry">
            {{ t('button.becomeSponsor') }}
          </SectionButton>
        </template>
        <Sponsors />
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
