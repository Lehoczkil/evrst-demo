<script setup lang="ts">
import Avatar from 'primevue/avatar';
import { motion } from 'motion-v';
import { TeamRequests, type TeamGroup, type TeamMember } from '@/services/requests/TeamRequests';
import { imgUrl } from '@/lib/imgUrl';

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

const ease: [number, number, number, number] = [0.22, 1, 0.36, 1];
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const initials = (name: string) => {
  const parts = name.trim().split(/\s+/);
  return parts
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase() ?? '')
    .join('');
};

const avatarSrc = (member: TeamMember) => {
  if (member.photo_path) {
    return imgUrl(member.photo_path, { width: 96, format: 'webp', fit: 'cover' });
  }
  return member.photo_url ?? undefined;
};
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
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div class="flex flex-col gap-40px items-center">
    <div class="flex flex-col gap-32px w-full items-center">
      <div
        v-for="group in orderedGroups"
        :key="group.slug"
        class="w-full flex flex-col items-center gap-12px"
      >
        <div class="text-[var(--color-dimmed)] font-500 fs-14px uppercase ls-[0.08em]">
          {{ group.name }}
        </div>
        <div class="flex flex-wrap justify-center gap-16px w-full">
          <motion.div
            v-for="(member, mIdx) in group.members"
            :key="member.id"
            class="flex flex-col items-center gap-8px p-16px w-full sm:(w-200px h-220px) bg-cardBg border border-cardBorder rounded-8px"
            :initial="{ opacity: 0, y: 24, scale: 0.92 }"
            :while-in-view="{ opacity: 1, y: 0, scale: 1 }"
            :in-view-options="{ once: true, amount: 0.2 }"
            :while-hover="{ y: -6 }"
            :transition="{ duration: 0.45, delay: mIdx * 0.05, ease }"
          >
            <Avatar
              v-if="member.photo_path || member.photo_url"
              :image="avatarSrc(member)"
              shape="circle"
              size="xlarge"
              class="mb-4px"
            />
            <Avatar
              v-else
              shape="circle"
              size="xlarge"
              :label="initials(member.name)"
              class="mb-4px"
            />
            <div class="font-600 fs-14px lh-[1.2] text-center">
              {{ member.name }}
            </div>
            <div class="text-[var(--color-dimmed)] fs-12px lh-[1.2] text-center">
              {{ member.degree || member.groups.find((g) => g.is_primary)?.name }}
            </div>
          </motion.div>
        </div>
      </div>
    </div>

    <Reveal :duration="0.3" :y="8">
      <div class="flex justify-center mt-16px">
        <SectionButton to="/join-us">{{ t('team.joinUs') }}</SectionButton>
      </div>
    </Reveal>
  </div>
</template>

<style lang="scss" scoped></style>
