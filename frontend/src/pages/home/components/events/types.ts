import type { Resource } from '@/types';

export type EventStatus = 'DRAFT' | 'PUBLISHED';

export interface Event {
  title: string;
  content: string;
  date?: string;          // legacy single date — superseded by start_at/end_at
  start_at?: string;      // ISO datetime
  end_at?: string;        // ISO datetime
  status: EventStatus;
  image?: string;
}

export type EventResource = Resource<Event>;
