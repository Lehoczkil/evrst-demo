<script setup lang="ts">
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
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div class="events">
    <div v-for="event in visibleEvents" :key="event.id" class="events__card">
      <div class="events__image-wrap">
        <ResponsiveImage
          :path="imagePathFor(event)"
          :src="imagePathFor(event) ? undefined : imageFor(event)"
          :alt="event.payload.title"
          :width="480"
          aspect-ratio="16 / 9"
        />
      </div>
      <div class="events__title">{{ event.payload.title }}</div>
      <div class="events__date">{{ formatRange(event) }}</div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import 'breakpoints';

.events {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;

  @include media-up(sm) {
    grid-template-columns: repeat(2, 1fr);
  }

  @include media-up(md) {
    grid-template-columns: repeat(3, 1fr);
  }

  &__card {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 16px;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 8px;
    cursor: pointer;
    transition: transform 150ms ease;

    &:hover {
      transform: scale(1.01);
    }
  }

  &__image-wrap {
    overflow: hidden;
    border-radius: 4px;
  }

  &__title {
    font-size: 16px;
    font-weight: 500;
    color: var(--color-bright);
  }

  &__date {
    font-size: 12px;
    color: var(--color-dimmed);
    text-transform: uppercase;
  }
}
</style>
