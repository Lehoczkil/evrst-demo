<script setup lang="ts">
import { CONTACT_EMAIL } from '@/lib/site';
import { motion } from 'motion-v';
import { CmsRequests, type SponsorResource } from '@/services/requests/CmsRequests';
import { imgUrl, pathFromStorageUrl } from '@/lib/imgUrl';
import { cardStagger } from './anims';

/*
  The design follows the data VOLUME, not a template.

  With two sponsors on file, a scrolling logo ribbon is a ribbon with a
  hole in it — and the section's real job at that size is recruitment. So
  the pitch panel takes the lead and the partners sit under it as equal,
  quiet tiles with their tier and year.

  Past RIBBON_FROM sponsors this inverts: the tiles become the embla
  auto-scroll ribbon and the pitch demotes to the head slot. Specified
  now, as one branch on `sponsors.length`, so it is not a redesign the
  first time the list grows.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t, locale } = useI18n();

/** Where a grid of tiles stops being the right object. */
const RIBBON_FROM = 8;

const { data: sponsors, status, fetch: refetch } = useQuery<SponsorResource[]>({
  key: ['sponsors', locale],
  request: () => CmsRequests.sponsors(),
  cache: true,
  staleTime: 300,
});
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const logoFor = (logo: string | undefined) => {
  if (!logo) {
    return undefined;
  }
  const path = pathFromStorageUrl(logo);
  if (!path || /\.svg($|\?)/i.test(path)) {
    return logo;
  }

  return imgUrl(path, { width: 200, format: 'webp', fit: 'contain' });
};
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const rows = computed(() => sponsors.value ?? []);
const asRibbon = computed(() => rows.value.length >= RIBBON_FROM);
const isLoading = computed(() => status.value === 'PENDING' && !rows.value.length);

const eyebrow = computed(() => t('sponsors.eyebrow', { count: rows.value.length }));
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <SectionShell id="sponsors" :eyebrow="eyebrow" :title="t('sponsors.title')">
    <FetchError v-if="status === 'FAILED' && !rows.length" @retry="refetch()" />
    <Skeleton v-else-if="isLoading" :rows="2" height="160px" />

    <div v-else class="sponsor-grid" :class="{ 'sponsor-grid--ribbon': asRibbon }">
      <div class="pitch">
        <div>
          <h3>{{ t('sponsors.pitchTitle') }}</h3>
          <p>{{ t('sponsors.pitchBody') }}</p>
        </div>
        <a class="btn" :href="`mailto:${CONTACT_EMAIL}?subject=${t('sponsors.mailSubject')}`">
          {{ t('sponsors.cta') }}
        </a>
      </div>

      <EmptyNote v-if="!rows.length" class="sponsor-grid__empty">
        {{ t('sponsors.empty') }}
      </EmptyNote>

      <motion.a
        v-for="(sponsor, i) in rows"
        :key="sponsor.id"
        v-bind="cardStagger(i)"
        class="sponsor"
        :href="sponsor.payload.url || undefined"
        :target="sponsor.payload.url ? '_blank' : undefined"
        rel="noopener noreferrer"
      >
        <!--
          A logo when there is one; the name set as display type when
          there is not. That is a design, not a fallback — a grey
          placeholder box would read as a hole in the page.
        -->
        <img
          v-if="logoFor(sponsor.payload.logo)"
          :src="logoFor(sponsor.payload.logo)"
          :alt="sponsor.payload.name"
          loading="lazy"
        />
        <b v-else>{{ sponsor.payload.name }}</b>

        <span class="sponsor__meta">
          <span v-if="sponsor.payload.description" class="sponsor__tier">
            {{ sponsor.payload.description }}
          </span>
          <template v-if="sponsor.payload.year">{{ sponsor.payload.year }}</template>
        </span>
      </motion.a>
    </div>
  </SectionShell>
</template>

<style lang="scss" scoped>
.sponsor-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1px;
  margin-top: clamp(32px, 4vw, 50px);

  // The 1px gap over a --line ground IS the border: no tile needs one of
  // its own, and the grid lines cannot double up.
  border: 1px solid var(--line);
  background: var(--line);
}

// Past RIBBON_FROM the tiles get narrower and scroll, and the pitch stops
// taking the full width.
.sponsor-grid--ribbon {
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));

  .pitch {
    grid-column: span 2;
  }
}

.sponsor-grid__empty {
  border-top: 0;
  background: var(--ink-0);
  grid-column: 1 / -1;
  padding-inline: clamp(24px, 3vw, 38px);
}

.pitch {
  display: flex;
  flex-direction: column;
  gap: 24px;
  justify-content: space-between;
  grid-column: span 2;
  padding: clamp(26px, 3.4vw, 46px);
  color: var(--ink-0);
  background: var(--gold-500);

  h3 {
    max-width: 26ch;
    font-size: clamp(1.35rem, 2.6vw, 2rem);
  }

  p {
    max-width: 48ch;
    margin-top: 14px;
    font-size: 15px;
  }

  // On gold, the action inverts to ink: gold on gold is invisible.
  .btn {
    align-self: flex-start;
    color: var(--paper);
    background: var(--ink-0);

    &:hover {
      background: #1b1d23;
    }
  }
}

.sponsor {
  display: flex;
  flex-direction: column;
  gap: 26px;
  justify-content: space-between;
  padding: clamp(24px, 3vw, 38px);
  background: var(--ink-0);
  text-decoration: none;
  transition: background-color 0.2s ease;

  &:hover {
    background: var(--ink-2);
  }

  b {
    font-weight: 700;
    font-size: clamp(1.15rem, 2vw, 1.6rem);
    line-height: 1.05;
    font-family: var(--font-display);
    letter-spacing: -0.025em;
  }

  img {
    max-width: 200px;
    max-height: 64px;
    object-fit: contain;
    object-position: left center;
  }
}

.sponsor__meta {
  color: var(--text-low);
  font-size: 10.5px;
  font-family: var(--font-mono);
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.sponsor__tier {
  display: block;
  color: var(--gold-500);
}

@media (width < 992px) {
  .sponsor-grid,
  .sponsor-grid--ribbon {
    grid-template-columns: 1fr;
  }

  .pitch,
  .sponsor-grid--ribbon .pitch {
    grid-column: auto;
  }
}
</style>
