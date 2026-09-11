<script setup lang="ts">
import { motion } from 'motion-v';
import { TeamRequests, type TeamGroup, type TeamMember } from '@/services/requests/TeamRequests';
import { imgUrl } from '@/lib/imgUrl';
import { cardStagger } from './anims';

/*
  The roster, on the page's one inverted surface — and not a table.

  The first attempt was a 1px-gutter grid of member cells. The contrast was
  right and the object was wrong: it read as a spreadsheet. A roster is a
  list of people under the thing they do, so that is what it is now —
  borderless group blocks, a mono group label with its real count, then the
  names as plain type with room to breathe. No cells, no rules, no
  monogram circles.

  The paper surface is not decoration either: it is what makes the ported
  chrome mean anything. The wordmark and the pill invert here, and on an
  all-dark page that whole mechanism would be dead code.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t, locale } = useI18n();

const { data: members, status, fetch: refetch } = useQuery<TeamMember[]>({
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
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*
  A small round photo joins the name once one is uploaded. The layout has
  room for it either way, so a roster with photos for some members and not
  others does not look half-finished.
*/
const photoFor = (member: TeamMember) => (member.photo_path
  ? imgUrl(member.photo_path, { width: 72, format: 'webp', fit: 'cover' })
  : member.photo_url ?? undefined);
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/*
  Grouped by each member's PRIMARY position, in the groups' own order from
  the API — which is the order the team set in the panel, not alphabetical
  and not ours to decide.
*/
const blocks = computed(() => {
  const byslug = new Map<string, TeamMember[]>();
  for (const member of members.value ?? []) {
    const primary = member.groups.find((group) => group.is_primary) ?? member.groups[0];
    const slug = primary?.slug ?? 'ungrouped';
    if (!byslug.has(slug)) {
      byslug.set(slug, []);
    }
    byslug.get(slug)?.push(member);
  }

  const ordered = (groups.value ?? [])
    .map((group) => ({ group, people: byslug.get(group.slug) ?? [] }))
    .filter((block) => block.people.length);

  // Anyone whose primary group is not public still belongs on the page.
  const loose = byslug.get('ungrouped');
  if (loose?.length) {
    ordered.push({
      group: { id: -1, slug: 'ungrouped', name: t('team.other'), kind: null, position: 999 },
      people: loose,
    });
  }

  return ordered;
});

const eyebrow = computed(() => t('team.eyebrow', {
  members: members.value?.length ?? 0,
  groups: blocks.value.length,
}));

const isLoading = computed(() => status.value === 'PENDING' && !members.value?.length);
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <SectionShell id="team" surface="paper" :eyebrow="eyebrow" :title="t('team.title')">
    <template #right>
      <RouterLink to="/join-us" class="btn btn--on-paper">
        {{ t('team.openPositions') }}
      </RouterLink>
    </template>

    <FetchError v-if="status === 'FAILED' && !members?.length" @retry="refetch()" />
    <Skeleton v-else-if="isLoading" :rows="6" height="120px" gap="32px" />
    <EmptyNote v-else-if="!blocks.length">{{ t('team.empty') }}</EmptyNote>

    <div v-else class="groups">
      <motion.div
        v-for="(block, i) in blocks"
        :key="block.group.slug"
        v-bind="cardStagger(i, 0.04)"
        class="group"
      >
        <h3>
          <span>{{ block.group.name }}</span>
          <em>{{ block.people.length }}</em>
        </h3>
        <ul>
          <li v-for="person in block.people" :key="person.id">
            <img v-if="photoFor(person)" :src="photoFor(person)" :alt="person.name" loading="lazy" />
            <span>
              {{ person.name }}
              <small v-if="person.degree">{{ person.degree }}</small>
            </span>
          </li>
        </ul>
      </motion.div>
    </div>

    <Mentors />
  </SectionShell>
</template>

<style lang="scss" scoped>
.groups {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(216px, 1fr));
  gap: clamp(34px, 4.4vw, 66px) clamp(24px, 3.4vw, 52px);
  margin-top: clamp(44px, 5.5vw, 76px);
}

.group {
  h3 {
    display: flex;
    gap: 8px;
    align-items: baseline;
    color: var(--on-paper-mid);
    font-weight: 500;
    font-size: 10.5px;
    font-family: var(--font-mono);
    letter-spacing: 0.16em;
    text-transform: uppercase;
  }

  // The count is what makes this a legend rather than a row of
  // decorative labels.
  em {
    // --gold-700, like the eyebrow: this is a number to be read on the
    // paper surface, not a fill.
    color: var(--gold-700);
    font-style: normal;
    font-variant-numeric: tabular-nums;
  }

  ul {
    display: grid;
    padding: 0;
    margin: 16px 0 0;
    gap: 9px;
    list-style: none;
  }

  li {
    display: flex;
    gap: 10px;
    align-items: center;
    color: var(--on-paper);
    font-size: 16px;
    letter-spacing: -0.014em;
  }

  img {
    flex: 0 0 auto;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
  }

  /*
    A person's role, and it must not look like a group legend.

    It was mono, uppercase and letter-spaced — the same voice as the `h3`
    above it — so "ELECTRICAL ENGINEER" under Penc Máté read as the start
    of a new group rather than as his title, and the two names below it
    looked like they belonged to it. Mono-uppercase is the DATA voice on
    this site (eyebrows, counts, specs); a job title is content, so it is
    set in the body face like the name it belongs to, just smaller and
    quieter.
  */
  small {
    display: block;
    margin-top: 1px;
    color: var(--on-paper-mid);
    font-size: 12.5px;
    letter-spacing: -0.005em;
  }
}
</style>
