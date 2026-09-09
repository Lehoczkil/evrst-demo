import { api } from '@/lib/http/Api';
import type { EventResource } from './CmsRequests';

/*
  Events, split by tense.

  `GET /api/resource?...&past=true` is a different query on the backend,
  so the two halves are two requests rather than one fetch filtered twice
  — the past filter is an indexed comparison on the promoted `end_at` /
  `start_at` columns, and reproducing it here would mean shipping the
  same three-branch predicate to the client.

  Upcoming has no server-side flag (there is no `?upcoming`), so `all()`
  is filtered client-side by `soonest()` below. That is one comparison
  over a handful of rows, not a re-implementation.
*/
export const EventRequests = {
  all: (lang?: string) =>
    api.get<EventResource[]>('/resource', {
      params: {
        collectionId: import.meta.env.VITE_EVENTS_COLLECTION_ID,
        include: 'objects',
        ...(lang ? { lang } : {}),
      } as Record<string, unknown>,
    }),

  past: (lang?: string) =>
    api.get<EventResource[]>('/resource', {
      params: {
        collectionId: import.meta.env.VITE_EVENTS_COLLECTION_ID,
        include: 'objects',
        past: true,
        ...(lang ? { lang } : {}),
      } as Record<string, unknown>,
    }),
};

/** The parsed start of an event, or null when it has no usable date. */
export const startOf = (event: EventResource): Date | null => {
  const raw = event.payload?.start_at;
  if (!raw) {
    return null;
  }
  const date = new Date(raw);

  return Number.isNaN(date.getTime()) ? null : date;
};

/**
 * The soonest event still ahead of us, or null.
 *
 * Null is a real answer and the caller must handle it: the hero's
 * countdown renders nothing rather than an empty clock, because
 * `— : — : —` is worse than no clock at all.
 */
export const soonest = (events: EventResource[] | null | undefined): EventResource | null => {
  if (!events?.length) {
    return null;
  }
  const now = Date.now();

  return events
    .map((event) => ({ event, at: startOf(event) }))
    .filter((row): row is { event: EventResource; at: Date } => !!row.at && row.at.getTime() > now)
    .sort((a, b) => a.at.getTime() - b.at.getTime())[0]?.event ?? null;
};
