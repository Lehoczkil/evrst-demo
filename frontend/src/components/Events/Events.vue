<script setup lang="ts">
import { motion } from 'motion-v';
import { CmsRequests, type EventResource } from '@/services/requests/CmsRequests';
import { pathFromStorageUrl } from '@/lib/imgUrl';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { data: events } = useQuery<EventResource[]>({
  key: ['events'],
  request: () => CmsRequests.events(),
});

const ease: [number, number, number, number] = [0.22, 1, 0.36, 1];
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const formatRange = (event: EventResource) => {
  const { start_at, end_at, date } = event.payload;
  if (start_at) {
    const start = new Date(start_at);
    const startLabel = start.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
    if (end_at) {
      const end = new Date(end_at);
      const sameDay = start.toDateString() === end.toDateString();
      const endLabel = end.toLocaleString(undefined, {
        dateStyle: sameDay ? undefined : 'medium',
        timeStyle: 'short',
      });
      return `${startLabel} — ${endLabel}`;
    }
    return startLabel;
  }
  return date ?? '';
};

const imageFor = (event: EventResource) =>
  event.payload.image ?? event.objects?.[0]?.url ?? '';

const imagePathFor = (event: EventResource) => pathFromStorageUrl(imageFor(event)) ?? undefined;
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const visibleEvents = computed(() => {
  return (events.value ?? [])
    .filter(
      (e) =>
        e.payload?.status !== 'DRAFT' &&
        (e.payload?.image || e.objects?.length),
    )
    .sort((a, b) => {
      const ta = new Date(a.payload.start_at ?? a.payload.date ?? 0).getTime();
      const tb = new Date(b.payload.start_at ?? b.payload.date ?? 0).getTime();
      return tb - ta;
    });
});
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div class="grid grid-cols-1 gap-24px sm:grid-cols-2 md:grid-cols-3">
    <motion.div
      v-for="(event, idx) in visibleEvents"
      :key="event.id"
      class="flex flex-col gap-8px p-16px border border-cardBorder rounded-8px bg-cardBg cursor-pointer"
      :initial="{ opacity: 0, y: 24, scale: 0.96 }"
      :while-in-view="{ opacity: 1, y: 0, scale: 1 }"
      :in-view-options="{ once: true, amount: 0.2 }"
      :while-hover="{ y: -4 }"
      :transition="{ duration: 0.45, delay: idx * 0.06, ease }"
    >
      <div class="overflow-hidden rounded-4px">
        <ResponsiveImage
          :path="imagePathFor(event)"
          :src="imagePathFor(event) ? undefined : imageFor(event)"
          :alt="event.payload.title"
          :width="480"
          aspect-ratio="16 / 9"
        />
      </div>
      <div class="text-[var(--color-bright)] font-500 fs-16px">
        {{ event.payload.title }}
      </div>
      <div class="text-[var(--color-dimmed)] fs-12px uppercase">
        {{ formatRange(event) }}
      </div>
    </motion.div>
  </div>
</template>

<style lang="scss" scoped></style>
