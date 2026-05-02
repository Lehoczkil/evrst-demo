<script setup lang="ts">
import { CmsRequests, type AboutItemResource } from '@/services/requests/CmsRequests';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();

const projectsCollectionId = import.meta.env.VITE_ABOUT_PROJECTS_COLLECTION_ID ?? '';
const goalsCollectionId = import.meta.env.VITE_ABOUT_GOALS_COLLECTION_ID ?? '';

const { data: projectsData } = useQuery<AboutItemResource[]>({
  key: ['about-projects'],
  request: () => CmsRequests.aboutProjects(),
  enabled: Boolean(projectsCollectionId),
});

const { data: goalsData } = useQuery<AboutItemResource[]>({
  key: ['about-goals'],
  request: () => CmsRequests.aboutGoals(),
  enabled: Boolean(goalsCollectionId),
});
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const projects = computed(() => {
  if (projectsData.value?.length) return projectsData.value.map((r) => r.payload);
  return [
    { title: t('about.project1.title'), description: t('about.project1.description') },
    { title: t('about.project2.title'), description: t('about.project2.description') },
    { title: t('about.project3.title'), description: t('about.project3.description') },
  ];
});

const goals = computed(() => {
  if (goalsData.value?.length) return goalsData.value.map((r) => r.payload);
  return [
    { title: t('about.goal1.title'), description: t('about.goal1.description') },
    { title: t('about.goal2.title'), description: t('about.goal2.description') },
    { title: t('about.goal3.title'), description: t('about.goal3.description') },
  ];
});
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div class="flex flex-col gap-40px">
    <div class="flex flex-col gap-16px">
      <h3 class="m-0 text-primary font-600 fs-24px lh-[1.2] uppercase">
        {{ t('about.whoWeAre') }}
      </h3>
      <p class="m-0 fs-16px lh-[1.6]">{{ t('about.whoWeAreBody') }}</p>
    </div>

    <div class="flex flex-col gap-16px">
      <h3 class="m-0 text-primary font-600 fs-24px lh-[1.2] uppercase">
        {{ t('about.projects') }}
      </h3>
      <div class="grid grid-cols-1 gap-16px sm:grid-cols-2 md:grid-cols-3">
        <div
          v-for="(item, idx) in projects"
          :key="`p-${idx}`"
          class="h-full p-20px border border-cardBorderAccent rounded-8px bg-cardBg"
        >
          <div class="mb-8px font-600 fs-18px">{{ item.title }}</div>
          <p class="m-0 text-[var(--color-dimmed)] fs-14px lh-[1.5]">
            {{ item.description }}
          </p>
        </div>
      </div>
    </div>

    <div class="flex flex-col gap-16px">
      <h3 class="m-0 text-primary font-600 fs-24px lh-[1.2] uppercase">
        {{ t('about.goals') }}
      </h3>
      <div class="grid grid-cols-1 gap-16px sm:grid-cols-2 md:grid-cols-3">
        <div
          v-for="(item, idx) in goals"
          :key="`g-${idx}`"
          class="h-full p-20px border border-cardBorderAccent rounded-8px bg-cardBg"
        >
          <div class="mb-8px font-600 fs-18px">{{ item.title }}</div>
          <p class="m-0 text-[var(--color-dimmed)] fs-14px lh-[1.5]">
            {{ item.description }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped></style>
