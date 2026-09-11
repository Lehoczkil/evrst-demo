<script setup lang="ts">
import { PageRequests } from '@/services/requests/PageRequests';
import type { PageResource } from '@/types/api';

/*
  The catch-all CMS route.

  It splits three cases the previous version conflated into one "Coming
  soon" placeholder:

    · a matching resource        → render it
    · the query SUCCEEDED and nothing matched → the 404 treatment
    · the query FAILED           → an inline error with a retry

  That last distinction is the point. Telling someone their URL is wrong
  when the server is down is a lie, and a 404 for a page the team simply
  has not written yet is a different message from a 404 for a typo — but
  the API cannot tell those apart either, so an unmatched slug is a 404.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const route = useRoute();
const { locale } = useLocale();
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/** Array params arrive from the `(.*)*` matcher as path segments. */
const slug = computed(() => {
  const raw = route.params.slug;

  return Array.isArray(raw) ? raw.join('/') : String(raw ?? '');
});
/*---------------------------------------------
/  VARIABLES (query — needs `slug` above)
---------------------------------------------*/
const { data, status, fetch: refetch } = useQuery<PageResource[]>({
  key: ['page', slug, locale],
  request: () => PageRequests.bySlug(slug.value),
  enabled: computed(() => !!slug.value),
});
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const page = computed(() => data.value?.[0] ?? null);
const settled = computed(() => status.value === 'SUCCESS' || status.value === 'FAILED');
const notFound = computed(() => status.value === 'SUCCESS' && !page.value);
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div class="dynamic-page">
    <FetchError v-if="status === 'FAILED'" class="container" @retry="refetch()" />
    <NotFoundPage v-else-if="notFound" />

    <SectionShell v-else-if="page" :title="page.payload.title">
      <HtmlTitle :title="page.payload.title" />
      <!-- eslint-disable-next-line vue/no-v-html -- CMS content authored in
           the admin panel by the team; the prose scale in _prose.scss is
           what makes it look like part of this site. -->
      <div v-if="page.payload.content" class="prose" v-html="page.payload.content" />
    </SectionShell>

    <SectionShell v-else-if="!settled">
      <Skeleton :rows="4" height="24px" gap="14px" />
    </SectionShell>
  </div>
</template>

<style lang="scss" scoped></style>
