<script setup lang="ts">
import Avatar from 'primevue/avatar';
import { motion } from 'motion-v';
import { CmsRequests, type MentorResource } from '@/services/requests/CmsRequests';
import { imgUrl, pathFromStorageUrl } from '@/lib/imgUrl';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { data: mentors } = useQuery<MentorResource[]>({
  key: ['mentors'],
  request: () => CmsRequests.mentors(),
});

const ease: [number, number, number, number] = [0.22, 1, 0.36, 1];
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const photoFor = (m: MentorResource) => {
  const raw = m.payload.photo ?? m.objects?.find((o) => o.key === 'photo')?.url ?? undefined;
  if (!raw) return undefined;
  const path = pathFromStorageUrl(raw);
  if (!path) return raw;
  if (/\.svg($|\?)/i.test(path)) return raw;
  return imgUrl(path, { width: 160, format: 'webp', fit: 'cover' });
};

const initials = (name: string) =>
  name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase() ?? '')
    .join('');
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div class="flex flex-wrap gap-16px justify-center">
    <motion.div
      v-for="(mentor, idx) in mentors ?? []"
      :key="mentor.id"
      class="flex flex-col items-center justify-center gap-8px w-full p-20px border border-cardBorder rounded-8px bg-cardBg sm:(w-280px h-260px min-h-260px)"
      :initial="{ opacity: 0, y: 24, scale: 0.94 }"
      :while-in-view="{ opacity: 1, y: 0, scale: 1 }"
      :in-view-options="{ once: true, amount: 0.2 }"
      :while-hover="{ y: -6 }"
      :transition="{ duration: 0.45, delay: idx * 0.07, ease }"
    >
      <Avatar
        :image="photoFor(mentor)"
        :label="initials(mentor.payload.name)"
        shape="circle"
        size="xlarge"
      />
      <div class="font-600 fs-15px lh-[1.2] text-center">
        {{ mentor.payload.name }}
      </div>
      <a
        :href="`mailto:${mentor.payload.email}`"
        class="text-[var(--color-dimmed)] fs-12px no-underline break-all text-center hover:text-primary"
      >
        {{ mentor.payload.email }}
      </a>
    </motion.div>
  </div>
</template>

<style lang="scss" scoped></style>
