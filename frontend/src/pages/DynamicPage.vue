<script setup lang="ts">
import { PageRequests } from '@/services/requests/PageRequests';
import type { PageResource } from '@/types/api';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const route = useRoute();
const { t } = useI18n();

const slug = computed(() => {
  const raw = route.params.slug;
  if (Array.isArray(raw)) return raw.join('/');
  return raw ?? '';
});

const { data: pages, status } = useQuery<PageResource[]>({
  key: ['page', slug],
  request: () => PageRequests.bySlug(slug.value),
  enabled: computed(() => Boolean(slug.value)),
});
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const page = computed(() => pages.value?.[0] ?? null);
const notFound = computed(() => status.value === 'SUCCESS' && !page.value);
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div
    class="container pt-[calc(var(--header-height)+32px)] pb-64px"
  >
    <div class="mb-32px">
      <SectionButton to="/">{{ t('button.home') }}</SectionButton>
    </div>

    <template v-if="notFound">
      <h1
        class="m-0 text-center fs-[clamp(2rem,7vw,3.75rem)] font-[var(--font-family-headline)]"
      >
        {{ t('placeholder.comingSoon') }}
      </h1>
    </template>

    <template v-else-if="page">
      <HtmlTitle :title="page.payload.title" />
      <header class="mb-32px text-center">
        <h1
          class="m-0 text-center fs-[clamp(2rem,7vw,3.75rem)] font-[var(--font-family-headline)]"
        >
          {{ page.payload.title }}
        </h1>
      </header>
      <div
        v-if="page.payload.content"
        class="dynamic-page__body fs-16px lh-[1.6]"
        v-html="page.payload.content"
      />
    </template>
  </div>
</template>

<style lang="scss" scoped>
.dynamic-page__body {
  :deep(p) {
    margin: 0 0 16px;
  }

  :deep(a) {
    color: var(--color-primary);
  }

  :deep(h2) {
    margin-top: 32px;
    font-size: 28px;
  }

  :deep(h3) {
    margin-top: 24px;
    font-size: 22px;
  }
}
</style>
