<script setup lang="ts">
import { motion } from 'motion-v';
import { CmsRequests, type AboutItemResource } from '@/services/requests/CmsRequests';
import { cardStagger, railFill } from './anims';

/*
  A timeline, because this content genuinely IS a sequence: one vehicle
  flown, one in build, one on paper, and `start_at` / `end_at` order them.

  **This is the only place on the site where ordinal structure earns its
  keep** — which is exactly why every section eyebrow elsewhere carries a
  count instead (see SectionShell).

  A rail with a gradient fill marks how far the programme has got, and
  each node's fill encodes its state: solid live-green = flown, gold with
  a halo = in build, hollow = design. Below `lg` the rail rotates into a
  left border and the entries hang off it.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t, tm, locale } = useI18n();

const { data: rows, status, fetch: refetch } = useQuery<AboutItemResource[]>({
  key: ['about-projects', locale],
  request: () => CmsRequests.aboutProjects(),
  cache: true,
  staleTime: 300,
});

/*
  What the CMS does not carry: the state, the year span and the apogee
  figure. These are per-vehicle facts the AboutProject payload has no
  columns for — `start_at` / `end_at` exist but a flown/building/design
  flag does not, and neither does an altitude.

  Matched to the CMS rows BY INDEX, which is the honest limitation here:
  reordering the collection in the panel would mismatch them. Promoting
  these to real columns is the fix, and it is a backend change.
*/
const META = [
  { state: 'flown', years: '2024 — 2025' },
  { state: 'building', years: '2025 — 2026' },
  { state: 'design', years: '2027 —' },
] as const;
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const fallback = computed(() => {
  const raw = tm('programme.vehicles') as unknown[];

  return (Array.isArray(raw) ? raw : []) as { title: string; description: string }[];
});

const vehicles = computed(() => {
  const source = rows.value?.length
    ? rows.value.map((row) => row.payload)
    : fallback.value;

  return source.map((vehicle, i) => ({
    ...vehicle,
    state: META[i]?.state ?? 'design',
    years: META[i]?.years ?? '',
    apogee: t(`programme.apogee${i + 1}`, ''),
  }));
});

/*
  How far the rail is filled: the share of vehicles that have flown, plus
  half a step for one in build. Derived, so adding a fourth vehicle moves
  the line rather than needing a new magic percentage.
*/
const railWidth = computed(() => {
  const list = vehicles.value;
  if (!list.length) {
    return '0%';
  }
  const flown = list.filter((v) => v.state === 'flown').length;
  const building = list.filter((v) => v.state === 'building').length;

  return `${Math.round(((flown + building * 0.5) / list.length) * 100)}%`;
});

const isEmpty = computed(() => !vehicles.value.length && status.value !== 'PENDING');
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <SectionShell
    id="programme"
    :eyebrow="t('programme.eyebrow', { total: vehicles.length })"
    :title="t('programme.title')"
  >
    <FetchError v-if="status === 'FAILED' && !vehicles.length" @retry="refetch()" />
    <Skeleton v-else-if="status === 'PENDING' && !vehicles.length" :rows="3" height="140px" />
    <EmptyNote v-else-if="isEmpty">{{ t('programme.empty') }}</EmptyNote>

    <div v-else class="timeline">
      <div class="timeline__rail" aria-hidden="true">
        <motion.i v-bind="railFill()" :style="{ width: railWidth }" />
        <span class="timeline__tip" :style="{ left: railWidth }">
          <RocketGlyph />
        </span>
      </div>

      <div class="timeline__items">
        <motion.article
          v-for="(vehicle, i) in vehicles"
          :key="vehicle.title"
          v-bind="cardStagger(i, 0.08)"
          class="tl-item"
          :data-state="vehicle.state"
        >
          <span class="tl-item__year">{{ vehicle.years }}</span>
          <span class="tl-item__node" aria-hidden="true" />
          <h3>{{ vehicle.title }}</h3>
          <p>{{ vehicle.description }}</p>
          <span v-if="vehicle.apogee" class="tl-item__apogee">{{ vehicle.apogee }}</span>
          <span class="pill" :class="`pill--${vehicle.state}`">
            {{ t(`programme.state.${vehicle.state}`) }}
          </span>
        </motion.article>
      </div>
    </div>
  </SectionShell>
</template>

<style lang="scss" scoped>
.timeline {
  position: relative;
  margin-top: clamp(56px, 7vw, 96px);
}

.timeline__rail {
  position: absolute;
  top: 46px;
  right: 0;
  left: 0;
  height: 1px;
  background: var(--line);

  i {
    position: absolute;
    top: 0;
    left: 0;
    height: 1px;

    // Live-green into gold: flown, then building. The gradient carries
    // the same state coding as the nodes.
    background: linear-gradient(90deg, var(--live), var(--gold-500));
    transform-origin: left center;
  }
}

// The rocket rides the tip of the progress line, nose forward — the same
// glyph as the back-to-top control, rotated into the direction of travel.
.timeline__tip {
  position: absolute;
  top: 50%;
  width: 22px;
  height: 22px;
  color: var(--gold-500);
  rotate: 90deg;
  translate: -30% -50%;

  :deep(svg) {
    display: block;
    width: 100%;
    height: 100%;
  }
}

.timeline__items {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: clamp(24px, 5vw, 80px);
}

.tl-item {
  position: relative;
  padding-top: 82px;

  h3 {
    font-size: clamp(1.5rem, 2.8vw, 2.15rem);
  }

  p {
    max-width: 38ch;
    margin-top: 14px;
    color: var(--text-mid);
    font-size: 15px;
  }

  .pill {
    margin-top: 14px;
  }
}

.tl-item__year {
  position: absolute;
  top: 0;
  left: 0;
  color: var(--text-low);
  font-size: 11.5px;
  font-family: var(--font-mono);
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.14em;
}

.tl-item__node {
  position: absolute;
  top: 40px;
  left: 0;
  width: 13px;
  height: 13px;
  border: 2px solid var(--line-hi);
  border-radius: 50%;
  background: var(--ink-0);
}

.tl-item[data-state='flown'] {
  .tl-item__node {
    border-color: var(--live);
    background: var(--live);
  }

  .tl-item__year {
    color: var(--live);
  }
}

.tl-item[data-state='building'] {
  .tl-item__node {
    border-color: var(--gold-500);
    background: var(--gold-500);
    box-shadow: 0 0 0 5px var(--gold-dim);
  }

  .tl-item__year {
    color: var(--gold-500);
  }
}

.tl-item__apogee {
  display: block;
  margin-top: 20px;
  color: var(--text-low);
  font-size: 11px;
  font-family: var(--font-mono);
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

@media (width < 992px) {
  .timeline__rail {
    display: none;
  }

  .timeline__items {
    grid-template-columns: 1fr;
  }

  // The rail becomes a left border and the nodes hang off it.
  .tl-item {
    padding-top: 0;
    padding-bottom: 36px;
    padding-left: 26px;
    border-left: 1px solid var(--line);
  }

  .tl-item__year {
    display: block;
    position: static;
    margin-bottom: 8px;
  }

  .tl-item__node {
    top: 4px;
    left: -7px;
  }
}
</style>
