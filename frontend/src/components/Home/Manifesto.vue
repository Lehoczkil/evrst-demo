<script setup lang="ts">
import { motion } from 'motion-v';
import { CmsRequests, type AboutItemResource } from '@/services/requests/CmsRequests';
import { TeamRequests, type TeamGroup, type TeamMember } from '@/services/requests/TeamRequests';
import { ViewRequests } from '@/services/requests/ViewRequests';
import { cardStagger, sectionRise } from './anims';

/*
  The substance behind the hero's statements — this is what replaced the
  About section.

  The section's STATEMENT lives in the hero (Hero/HeroSays.vue): three
  lines playing in the band the headline leaves behind, which is where a
  reader already is when they want to know what this is. Keeping the
  pull-quote in both places was the first attempt and it made the same
  claim twice, 200px apart.

  What stays here is everything the statement does not carry: the body
  copy, a hairline strip of figures, and the three goals.

  Air is spent BELOW rather than above (see the padding at the bottom of
  this file). Opening with a grand pause put a long empty band immediately
  after the hero's sequence, which is where the "too much empty space"
  report landed.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t, locale } = useI18n();

const { data: goalRows } = useQuery<AboutItemResource[]>({
  key: ['about-goals', locale],
  request: () => CmsRequests.aboutGoals(),
  cache: true,
  staleTime: 300,
});

const { data: aboutView } = useQuery({
  key: ['view-about', locale],
  request: () => ViewRequests.byName('about'),
  cache: true,
  staleTime: 300,
});

const { data: members } = useQuery<TeamMember[]>({
  key: ['team-members', locale],
  request: () => TeamRequests.members(locale.value as string),
  cache: true,
  staleTime: 300,
});

const { data: groups } = useQuery<TeamGroup[]>({
  key: ['team-groups', locale],
  request: () => TeamRequests.groups(locale.value as string),
  cache: true,
  staleTime: 300,
});

const { data: projects } = useQuery<AboutItemResource[]>({
  key: ['about-projects', locale],
  request: () => CmsRequests.aboutProjects(),
  cache: true,
  staleTime: 300,
});

/*
  The instrument each goal names, in order. Deliberately NOT in the CMS:
  it is a typographic device belonging to this layout, not content the
  team would want to edit — and a goal arriving without one should still
  render.
*/
const MARKS = ['manifesto.mark1', 'manifesto.mark2', 'manifesto.mark3'];
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/*
  The CMS row wins; i18n is the fallback for an empty collection, which is
  what a fresh volume looks like before anyone has written anything.
*/
const body = computed(() => aboutView.value?.[0]?.payload?.content || t('manifesto.body'));

const goals = computed(() => {
  const rows = goalRows.value ?? [];

  return rows.length
    ? rows.map((row) => row.payload)
    : [1, 2, 3].map((i) => ({
        title: t(`manifesto.goal${i}.title`),
        description: t(`manifesto.goal${i}.description`),
      }));
});

/*
  Figures derived from the data they describe rather than typed in — the
  point of a count is that it is true. A figure with no data behind it is
  omitted entirely instead of showing a zero, because "0 tag" reads as a
  broken query and an absent row reads as nothing at all.
*/
const figures = computed(() => {
  const rows: { label: string; value: string; gold?: boolean }[] = [
    { label: t('manifesto.founded'), value: '2024' },
  ];

  if (members.value?.length) {
    rows.push({ label: t('manifesto.members'), value: String(members.value.length) });
  }
  if (groups.value?.length) {
    rows.push({ label: t('manifesto.groups'), value: String(groups.value.length) });
  }
  if (projects.value?.length) {
    rows.push({ label: t('manifesto.vehicles'), value: String(projects.value.length), gold: true });
  }

  return rows;
});
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <section id="mission" class="manifesto">
    <div class="container">
      <motion.p v-bind="sectionRise()" class="eyebrow">
        {{ t('manifesto.eyebrow') }}
      </motion.p>

      <motion.div v-bind="sectionRise(0.05)" class="manifesto__body">
        <p class="lede">{{ body }}</p>
        <p class="lede">{{ t('manifesto.second') }}</p>
      </motion.div>

      <!-- One hairline strip, not four number tiles: these are context
           for the hero's claim, not the point of the page. -->
      <motion.div v-bind="sectionRise(0.1)" class="figures">
        <span v-for="figure in figures" :key="figure.label" class="figure">
          <span class="mono-label">{{ figure.label }}</span>
          <b :class="{ 'figure__value--gold': figure.gold }">{{ figure.value }}</b>
        </span>
      </motion.div>

      <!-- Three facets of one statement, so no boxes and no dividers —
           just space. -->
      <div class="goals">
        <motion.div
          v-for="(goal, i) in goals"
          :key="goal.title"
          v-bind="cardStagger(i)"
          class="goal"
        >
          <p class="goal__mark">{{ t(MARKS[i] ?? MARKS[0]) }}</p>
          <h3 class="goal__title">{{ goal.title }}</h3>
          <p class="goal__text">{{ goal.description }}</p>
        </motion.div>
      </div>
    </div>
  </section>
</template>

<style lang="scss" scoped>
.manifesto {
  // Tight above, generous below. The hero's sequence ends immediately
  // before this, so a grand pause here reads as a gap rather than as air.
  padding-block: clamp(52px, 5.5vw, 96px) clamp(100px, 13vw, 200px);
}

.manifesto__body {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: clamp(30px, 6vw, 110px);
  margin-top: clamp(30px, 4vw, 52px);

  .lede {
    max-width: 48ch;
  }
}

.figures {
  display: flex;
  align-items: baseline;
  padding-top: 26px;
  margin-top: clamp(56px, 8vw, 116px);
  border-top: 1px solid var(--line);
  flex-wrap: wrap;
  gap: clamp(22px, 4vw, 54px);
}

.figure {
  display: flex;
  gap: 10px;
  align-items: baseline;

  b {
    color: var(--text-hi);
    font-weight: 500;
    font-size: 1.15rem;
    font-family: var(--font-mono);
    font-variant-numeric: tabular-nums;
  }
}

.figure__value--gold {
  color: var(--gold-500) !important;
}

.goals {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: clamp(28px, 5vw, 84px);
  margin-top: clamp(60px, 8vw, 120px);
}

.goal__mark {
  color: var(--gold-500);
  font-size: 10.5px;
  font-family: var(--font-mono);
  letter-spacing: 0.15em;
  text-transform: uppercase;
}

.goal__title {
  margin: 14px 0 10px;
  font-size: var(--fs-h3);
  letter-spacing: -0.02em;
}

.goal__text {
  max-width: 34ch;
  color: var(--text-mid);
  font-size: 15px;
}

@media (width < 992px) {
  .manifesto__body,
  .goals {
    grid-template-columns: 1fr;
  }

  .goals {
    gap: 34px;
  }
}
</style>
