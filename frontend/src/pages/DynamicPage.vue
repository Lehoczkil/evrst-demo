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
  <div class="container dynamic-page">
    <div class="dynamic-page__back">
      <SectionButton to="/">{{ t('button.home') }}</SectionButton>
    </div>

    <template v-if="notFound">
      <h1 class="dynamic-page__title">{{ t('placeholder.comingSoon') }}</h1>
    </template>

    <template v-else-if="page">
      <HtmlTitle :title="page.payload.title" />
      <header class="dynamic-page__header">
        <h1 class="dynamic-page__title">{{ page.payload.title }}</h1>
      </header>
      <div v-if="page.payload.content" class="dynamic-page__body" v-html="page.payload.content" />
    </template>
  </div>
</template>

<style lang="scss" scoped>
.dynamic-page {
  padding-top: calc(var(--header-height) + 32px);
  padding-bottom: 64px;

  &__back {
    margin-bottom: 32px;
  }

  &__header {
    margin-bottom: 32px;
    text-align: center;
  }

  &__title {
    margin: 0;
    font-size: clamp(2rem, 7vw, 3.75rem);
    font-family: var(--font-family-headline);
    text-align: center;
  }

  &__body {
    font-size: 16px;
    line-height: 1.6;

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
}
</style>
