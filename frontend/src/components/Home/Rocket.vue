<script setup lang="ts">
import { motion } from 'motion-v';
import { TeamRequests, type TeamMember } from '@/services/requests/TeamRequests';
import { cardStagger, sectionRise } from './anims';

/*
  A dimensioned drawing sheet, and the starfield comes back.

  The layout is SYMMETRIC on purpose: three specs right-aligned in the
  left column, the drawing on the centre axis, three specs left-aligned in
  the right column — the object dimensioned from both sides, the way it
  would be on a real sheet. The first arrangement (table left, everything
  else right) read as thrown together because it was.

  The section is `ink-0` with its own parallaxed starfield: it is the one
  section besides the hero that is set in space, and it should look like
  it. The drift is on a `view()` timeline, so it is tied to this section
  crossing the viewport rather than to the page's absolute scroll.

  The subsystem table at the bottom is the one place the hardware and the
  roster meet — every subsystem names the team group that owns it, with
  that group's real headcount.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t, tm, locale } = useI18n();

const { data: members } = useQuery<TeamMember[]>({
  key: ['team-members', locale],
  request: () => TeamRequests.members(locale.value as string),
  cache: true,
  staleTime: 300,
});

/*
  Which group owns which subsystem. The slugs are the real
  team_member_groups slugs, so the counts below are the actual roster's.
*/
const SUBSYSTEMS = [
  { key: 'avionics', slug: 'elektronika' },
  { key: 'software', slug: 'szoftver' },
  { key: 'propulsion', slug: 'hajtomu' },
  { key: 'structures', slug: 'vaz-aerodinamika' },
] as const;
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/** How many members hold this group as their primary position. */
const headcount = (slug: string) => (members.value ?? []).filter(
  (member) => member.groups.some((group) => group.slug === slug && group.is_primary),
).length;
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/*
  Specs from the message files.

  This is open question §14/2 in the plan: they could equally live in a
  CMS row so the team can edit them without a deploy. i18n for now,
  because the labels have to be translated either way and splitting the
  label from its value across two systems is worse than either.
*/
const specs = computed(() => {
  const raw = tm('rocket.specs') as unknown[];

  return (Array.isArray(raw) ? raw : []) as { label: string; value: string; unit?: string }[];
});

const leftSpecs = computed(() => specs.value.slice(0, 3));
const rightSpecs = computed(() => specs.value.slice(3, 6));

const subsystems = computed(() => SUBSYSTEMS.map((row) => {
  const count = headcount(row.slug);

  return {
    key: row.key,
    name: t(`rocket.subsystem.${row.key}`),
    // The group's own localised name comes from the roster; the count is
    // omitted when the roster has not loaded rather than showing 0.
    owner: t(`team.group.${row.slug}`),
    count: count || null,
  };
}));
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <SectionShell
    id="rocket"
    :eyebrow="t('rocket.eyebrow')"
    :title="t('rocket.title')"
    class="rocket-section"
  >
    <template #right>
      <span class="pill pill--building">{{ t('rocket.status') }}</span>
    </template>

    <!--
      In the bleed slot, not the default one: both of these are full-bleed
      and the default slot is inside the container, which cropped the
      field to the reading measure and pulled the glow's centre off the
      viewport's.
    -->
    <template #bleed>
      <Starfield class="rocket-section__stars" :opacity="0.55" />
      <div class="rocket-section__glow" aria-hidden="true" />
    </template>

    <div class="sheet">
      <dl class="sheet__col sheet__col--left">
        <motion.div
          v-for="(spec, i) in leftSpecs"
          :key="spec.label"
          v-bind="cardStagger(i)"
          class="spec"
        >
          <dt>{{ spec.label }}</dt>
          <dd>{{ spec.value }}<b v-if="spec.unit"> {{ spec.unit }}</b></dd>
        </motion.div>
      </dl>

      <motion.div v-bind="sectionRise()">
        <RocketBlueprint :height="t('rocket.dimHeight')" :diameter="t('rocket.dimDiameter')" />
      </motion.div>

      <dl class="sheet__col sheet__col--right">
        <motion.div
          v-for="(spec, i) in rightSpecs"
          :key="spec.label"
          v-bind="cardStagger(i)"
          class="spec"
        >
          <dt>{{ spec.label }}</dt>
          <dd>{{ spec.value }}<b v-if="spec.unit"> {{ spec.unit }}</b></dd>
        </motion.div>
      </dl>
    </div>

    <p class="mono-label sheet__caption">{{ t('rocket.subsystemHeading') }}</p>
    <div class="subsystems">
      <motion.div
        v-for="(row, i) in subsystems"
        :key="row.key"
        v-bind="cardStagger(i)"
        class="subsystem"
      >
        <b>{{ row.name }}</b>
        <span>{{ row.owner }}<template v-if="row.count"> · {{ row.count }}</template></span>
      </motion.div>
    </div>
  </SectionShell>
</template>

<style lang="scss" scoped>
.rocket-section {
  // The two bleed layers are oversized on purpose; this is what keeps
  // them from giving the page a sideways scrollbar.
  overflow: hidden;
}

.rocket-section__stars {
  // Oversized so the drift never pulls the field's edge into frame. The
  // horizontal overscan matters as much as the vertical now that the
  // layer spans the viewport rather than the container.
  inset: -14% -2% !important;
  will-change: transform;
}

/*
  Tied to this section crossing the viewport rather than to the page's
  absolute scroll — `view()` is what a section-level parallax wants, where
  `scroll(root)` is what a pinned stage wants.
*/
@supports (animation-timeline: view()) {
  .rocket-section__stars {
    animation: rocket-drift linear both;
    animation-timeline: view();
    animation-range: cover 0% cover 100%;
  }
}

@keyframes rocket-drift {
  from {
    transform: translateY(-9%);
  }

  to {
    transform: translateY(9%);
  }
}

.rocket-section__glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(90% 55% at 50% 100%, rgb(241 171 60 / 9%), transparent 62%);
  pointer-events: none;
}

.sheet {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: clamp(16px, 3.4vw, 56px);
  align-items: center;
  margin-top: clamp(40px, 6vw, 76px);
}

.sheet__col {
  display: grid;
  gap: 2px;
  margin: 0;
}

.sheet__col--left {
  text-align: right;
}

.spec {
  padding: 13px 0;
  border-bottom: 1px solid var(--line);

  dt {
    color: var(--text-low);
    font-weight: 500;
    font-size: 10.5px;
    font-family: var(--font-mono);
    letter-spacing: 0.14em;
    text-transform: uppercase;
  }

  dd {
    margin: 6px 0 0;
    color: var(--text-hi);
    font-size: clamp(15px, 1.7vw, 19px);
    font-family: var(--font-mono);
    font-variant-numeric: tabular-nums;
  }

  // The unit in gold so the number reads first.
  b {
    color: var(--gold-500);
    font-weight: 500;
  }
}

.sheet__caption {
  display: block;
  margin-top: clamp(40px, 5vw, 72px);
}

.subsystems {
  display: grid;
  padding-top: 26px;
  margin-top: 26px;
  border-top: 1px solid var(--line);
  grid-template-columns: repeat(4, 1fr);
  gap: clamp(18px, 3vw, 44px);

  b {
    display: block;
    font-weight: 600;
    font-size: 15px;
    letter-spacing: -0.012em;
  }

  span {
    display: block;
    margin-top: 7px;
    color: var(--gold-500);
    font-size: 10.5px;
    font-family: var(--font-mono);
    letter-spacing: 0.11em;
    text-transform: uppercase;
  }
}

@media (width < 1200px) {
  .subsystems {
    grid-template-columns: 1fr 1fr;
    row-gap: 26px;
  }
}

@media (width < 992px) {
  .sheet {
    grid-template-columns: 1fr;
  }

  .sheet__col--left {
    text-align: left;
  }

  // The drawing leads on a narrow screen: it is the thing the section is
  // about, and reading three numbers before seeing the object is
  // backwards.
  .sheet > div:nth-child(2) {
    order: -1;
  }
}

/*
  The starfield's drift is decoration on a section that reads perfectly
  still. CSS animations cannot see MotionConfig, so every one of them
  needs its own guard.
*/
@media (prefers-reduced-motion: reduce) {
  .rocket-section__stars {
    animation: none;
    transform: none;
  }
}
</style>
