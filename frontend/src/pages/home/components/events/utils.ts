import type { EventResource } from './types';

const eventTimestamp = (event: EventResource): number => {
  const { start_at, date } = event.payload;
  if (start_at) return new Date(start_at).getTime();
  if (date) return new Date(date).getTime();
  return 0;
};

export function eventsSelector(events: EventResource[]) {
  return events
    .filter(
      (event) =>
        event.payload?.status !== 'DRAFT' &&
        (event.payload?.image || event.objects?.length),
    )
    .sort((a, b) => eventTimestamp(b) - eventTimestamp(a));
}
