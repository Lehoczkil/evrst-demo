import { api } from '@/lib/http/Api';
import type { Resource } from '@/types/api';

export interface SponsorPayload {
  name: string;
  description: string;
  year: number | null;
  url: string;
  logo?: string;
}

export interface MentorPayload {
  name: string;
  email: string;
  photo?: string;
}

export interface EventPayload {
  title: string;
  content: string;
  date?: string;
  start_at?: string;
  end_at?: string;
  status: 'DRAFT' | 'PUBLISHED';
  image?: string;
}

export interface AboutItemPayload {
  title: string;
  description: string;
}

export type SponsorResource = Resource<SponsorPayload>;
export type MentorResource = Resource<MentorPayload>;
export type EventResource = Resource<EventPayload>;
export type AboutItemResource = Resource<AboutItemPayload>;

export const CmsRequests = {
  collection: <T = unknown>(collectionId: string, params?: Record<string, unknown>) =>
    api.get<Resource<T>[]>('/resource', {
      params: { collectionId, ...(params ?? {}) } as any,
    }),
  sponsors: () =>
    api.get<SponsorResource[]>('/resource', {
      params: { collectionId: import.meta.env.VITE_SPONSORS_COLLECTION_ID } as any,
    }),
  mentors: () =>
    api.get<MentorResource[]>('/resource', {
      params: {
        collectionId: import.meta.env.VITE_MENTORS_COLLECTION_ID,
        include: 'objects',
      } as any,
    }),
  events: () =>
    api.get<EventResource[]>('/resource', {
      params: {
        collectionId: import.meta.env.VITE_EVENTS_COLLECTION_ID,
        include: 'objects',
        past: true,
      } as any,
    }),
  aboutProjects: () =>
    api.get<AboutItemResource[]>('/resource', {
      params: { collectionId: import.meta.env.VITE_ABOUT_PROJECTS_COLLECTION_ID } as any,
    }),
  aboutGoals: () =>
    api.get<AboutItemResource[]>('/resource', {
      params: { collectionId: import.meta.env.VITE_ABOUT_GOALS_COLLECTION_ID } as any,
    }),
};
