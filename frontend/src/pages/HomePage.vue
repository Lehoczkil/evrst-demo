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
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const rocketObject = computed(() =>
  page.value?.objects?.find((o) => o.key === 'rocket'),
);
const rocketScale = computed(() => page.value?.payload?.data?.rocket?.scale);
const rocketPosition = computed(() => page.value?.payload?.data?.rocket?.position);
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div class="relative">
    <Hero />

    <RocketScene
      v-if="rocketObject?.url"
      :url="rocketObject.url"
      :scale="rocketScale"
      :position="rocketPosition"
    />

    <div class="h-32px sm:h-93px" />

    <SectionWrap bg="dark">
      <Section id="events" :index="0" :title="t('section.events')">
        <Events />
      </Section>
    </SectionWrap>

    <DiagonalDivider top-color="var(--bg-dark)" bottom-color="var(--bg-gray)" />

    <SectionWrap bg="gray">
      <Section id="about" :index="1" :title="t('section.about')">
        <About />
      </Section>
    </SectionWrap>

    <DiagonalDivider top-color="var(--bg-gray)" bottom-color="var(--bg-dark)" />

    <SectionWrap bg="dark">
      <Section id="team" :index="2" :title="t('section.team')">
        <Team />
      </Section>
    </SectionWrap>

    <SectionWrap bg="dark" with-grid-overlay>
      <Section id="mentors" :index="3" :title="t('section.mentors')">
        <Mentors />
      </Section>
    </SectionWrap>

    <DiagonalDivider top-color="var(--bg-dark)" bottom-color="var(--bg-gray)" />

    <SectionWrap bg="gray">
      <Section id="sponsors" :index="4" :title="t('section.sponsors')">
        <template #right>
          <SectionButton href="mailto:evrstrocket@gmail.com?subject=Sponsorship Inquiry">
            {{ t('button.becomeSponsor') }}
          </SectionButton>
        </template>
        <Sponsors />
      </Section>
    </SectionWrap>

    <EvrstMarquee />

    <Outro />
  </div>
</template>

<style lang="scss" scoped></style>
