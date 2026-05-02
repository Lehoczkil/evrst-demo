<script setup lang="ts">
import Avatar from 'primevue/avatar';
import { TeamRequests, type TeamGroup, type TeamMember } from '@/services/requests/TeamRequests';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t, locale } = useI18n();

const { data: members } = useQuery<TeamMember[]>({
  key: ['team-members', locale],
  request: () => TeamRequests.members(locale.value as string),
});

const { data: groups } = useQuery<TeamGroup[]>({
  key: ['team-groups', locale],
  request: () => TeamRequests.groups(locale.value as string),
});
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const orderedGroups = computed(() => {
  const grouped = new Map<string, TeamMember[]>();
  for (const member of members.value ?? []) {
    const slug = member.groups.find((g) => g.is_primary)?.slug ?? 'ungrouped';
    const bucket = grouped.get(slug) ?? [];
    bucket.push(member);
    grouped.set(slug, bucket);
  }

  const out: { slug: string; name: string; members: TeamMember[] }[] = [];
  for (const group of groups.value ?? []) {
    const bucket = grouped.get(group.slug);
    if (bucket && bucket.length > 0) {
      out.push({ slug: group.slug, name: group.name, members: bucket });
      grouped.delete(group.slug);
    }
  }
  for (const [slug, bucket] of grouped) {
    out.push({ slug, name: '', members: bucket });
  }
  return out;
});

const initials = (name: string) => {
  const parts = name.trim().split(/\s+/);
  return parts
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase() ?? '')
    .join('');
};
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div class="team">
    <div v-for="group in orderedGroups" :key="group.slug" class="team__group">
      <div class="team__group-name">{{ group.name }}</div>
      <div class="team__cards">
        <div v-for="member in group.members" :key="member.id" class="team__card">
          <Avatar
            v-if="member.photo_url"
            :image="member.photo_url"
            shape="circle"
            size="xlarge"
            class="team__avatar"
          />
          <Avatar v-else shape="circle" size="xlarge" :label="initials(member.name)" class="team__avatar" />
          <div class="team__name">{{ member.name }}</div>
          <div class="team__degree">
            {{ member.degree || member.groups.find((g) => g.is_primary)?.name }}
          </div>
        </div>
      </div>
    </div>

    <div class="team__cta">
      <SectionButton to="/join-us">{{ t('team.joinUs') }}</SectionButton>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import 'breakpoints';

.team {
  display: flex;
  flex-direction: column;
  gap: 32px;
  align-items: center;

  &__group {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
  }

  &__group-name {
    font-size: 14px;
    text-transform: uppercase;
    color: var(--color-dimmed);
    font-weight: 500;
    letter-spacing: 0.08em;
  }

  &__cards {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 16px;
    width: 100%;
  }

  &__card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px;
    width: 200px;
    height: 220px;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 8px;

    @include media-down(sm) {
      width: 100%;
    }
  }

  &__avatar {
    margin-bottom: 4px;
  }

  &__name {
    font-size: 14px;
    font-weight: 600;
    text-align: center;
    line-height: 1.2;
  }

  &__degree {
    font-size: 12px;
    color: var(--color-dimmed);
    text-align: center;
    line-height: 1.2;
  }

  &__cta {
    margin-top: 16px;
  }
}
</style>
