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
  /**
   * The domain half only. The API redacts `email` out of the CMS payload
   * (that endpoint is unauthenticated) and hands back the domain, which
   * is the only part this site ever rendered.
   */
  email_domain?: string;
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
  /*
    Projects only, and all optional — goals share this shape and carry
    none of them. Editable in the panel under About → Projects; they used
    to be bundled in the SPA and matched to a row by its position in the
    collection.
  */
  state?: 'flown' | 'building' | 'design';
  years?: string;
  apogee?: string;
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
