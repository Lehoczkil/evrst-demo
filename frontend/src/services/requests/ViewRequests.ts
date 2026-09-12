import { api } from '@/lib/http/Api';
import type { Resource } from '@/types/api';

export interface ViewPayload {
  name: string;
  title: string;
  content: string;
}

export type ViewResource = Resource<ViewPayload>;

/** A `{en, hu}` map as the panel stores every translatable string. */
export type LocaleMap = Record<string, string>;

/**
 * The home page's editable copy (Site → Home texts). Every field is
 * optional: the SPA falls back to its bundled translation per string, so
 * a partially filled row is a valid row.
 */
export interface HomeCopyPayload {
  name: string;
  hero?: {
    eyebrow?: LocaleMap;
    title1?: LocaleMap;
    title2?: LocaleMap;
    lede?: LocaleMap;
    ctaJoin?: LocaleMap;
    ctaMission?: LocaleMap;
    says?: LocaleMap[];
  };
  rocket?: {
    eyebrow?: LocaleMap;
    title?: LocaleMap;
    status?: LocaleMap;
    dimHeight?: string;
    dimDiameter?: string;
    specs?: Array<{ label?: LocaleMap; value?: string; unit?: LocaleMap }>;
  };
}

export type HomeCopyResource = Resource<HomeCopyPayload>;

/*
  The `views` collection: long-form copy the team edits in the panel, keyed
  by a `name` in the payload rather than by id.

  The bracket serialiser in FetchWrapper flattens this into
  `where[payload][path][0]=name&where[payload][equals]=about`, which is the
  shape ResourceController::applyWhere understands.
*/
export const ViewRequests = {
  byName: (name: string) =>
    api.get<ViewResource[]>('/resource', {
      params: {
        collectionId: import.meta.env.VITE_VIEWS_COLLECTION_ID,
        where: { payload: { path: ['name'], equals: name } },
      } as Record<string, unknown>,
    }),

  /*
    The hero + rocket copy (Site → Home texts).

    Named 'home-copy', not 'home', on purpose: this lookup filters on
    payload.name alone — VITE_VIEWS_COLLECTION_ID is not defined anywhere,
    so `collectionId` is serialised as "undefined" and the API ignores it —
    and the CMS `pages` collection already holds a row named 'home' that
    sorts ahead of this one and would be returned instead.
  */
  home: () =>
    api.get<HomeCopyResource[]>('/resource', {
      params: {
        collectionId: import.meta.env.VITE_VIEWS_COLLECTION_ID,
        where: { payload: { path: ['name'], equals: 'home-copy' } },
      } as Record<string, unknown>,
    }),
};
