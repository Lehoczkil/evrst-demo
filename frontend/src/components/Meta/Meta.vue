<script setup lang="ts">
/*
  Writes the head. Mounted once, in App.vue.

  It does NOT teleport tags — see the note at the top of lib/seo.ts. It
  syncs the tags index.html already ships, so there is exactly one <title>,
  one description and one og:* set in the document at any time.

  Canonical and hreflang are derived from the route rather than set by each
  page, because they are a property of the URL, not of the copy: `/` is one
  page with one canonical, and `/csatlakozz` + `/join-us` are one page with
  two spellings that have to point at each other.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
import { alternatesFor, absoluteUrl, applyHead, OG_LOCALE } from '@/lib/seo';

const metaStore = useMetaStore();
const route = useRoute();
const { locale } = useLocale();
const { t } = useI18n();
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const meta = computed(() => metaStore.meta);

/** The named route the current path belongs to, in whichever locale spells it. */
const routeName = computed(() => {
  const matched = route.matched[route.matched.length - 1]?.name;
  // Localized duplicates are registered as `joinUs:1`, `joinUs:2`, … — the
  // suffix is a Vue Router uniqueness requirement, not a different page.
  return typeof matched === 'string' ? matched.split(':')[0] : undefined;
});

const alternates = computed(() => (routeName.value ? alternatesFor(routeName.value) : []));

/**
 * The current locale's spelling of this route is the canonical one; a
 * path with no named route (a CMS page under the catch-all) is its own.
 */
const canonicalPath = computed(() => {
  const match = alternates.value.find((entry) => entry.lang === locale.value);
  return match?.path ?? route.path;
});
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
watchEffect(() => {
  const canonical = absoluteUrl(canonicalPath.value);

  applyHead({
    title: meta.value?.title,
    // Always a real string: the tag index.html ships is reused rather than
    // duplicated, so leaving this undefined would strand the PREVIOUS
    // page's description on a page that has none of its own.
    description: meta.value?.description || t('seo.description'),
    theme: meta.value?.theme,
    ogTitle: meta.value?.ogTitle,
    ogDescription: meta.value?.ogDescription,
    ogImage: meta.value?.ogImage,
    ogType: meta.value?.ogType,
    ogUrl: canonical,
    ogLocale: OG_LOCALE[locale.value as keyof typeof OG_LOCALE],
    canonical,
    robots: meta.value?.robots ?? undefined,
    alternates: alternates.value,
  });
});
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <span style="display: none" />
</template>
