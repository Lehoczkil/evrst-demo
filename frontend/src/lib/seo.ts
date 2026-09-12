/*
  Head management for the SPA.

  Two things live here: the site-level constants a crawler needs in
  absolute form, and the DOM sync that writes them into <head>.

  The sync MUTATES the tags index.html already ships instead of appending
  new ones, and that is the whole point of the file. The previous approach
  teleported a second <title> and a second og:* set into the head on every
  navigation, which does not work: the document title is defined as the
  text of the FIRST <title> element, so the static default always won and
  no page ever changed the tab. Social scrapers pick the first og:title
  the same way. Duplicated description tags are a ranking smell on top.
*/

import { LANGUAGES, type Language } from '@/translations';
import { ROUTE_PATHS } from '@/composables/useLanguage';

/**
 * The canonical origin, no trailing slash.
 *
 * Hardcoded to production rather than read from `window.location`, because
 * that is what a canonical URL is for: `www.evrst.hu` and `evrst.hu` both
 * answer 200, and a self-referential canonical would tell Google they are
 * two sites. `VITE_SITE_URL` lets a staging deploy point at itself.
 */
export const SITE_URL = (import.meta.env.VITE_SITE_URL ?? 'https://evrst.hu').replace(/\/+$/, '');

export const SITE_NAME = 'Escape Velocity Rocketry Student Team';

/** 1200x630, generated from the wordmark. Absolute — scrapers don't resolve relative paths. */
export const SHARE_IMAGE = `${SITE_URL}/share.jpg`;

/** Open Graph wants a full locale, not a bare language code. */
export const OG_LOCALE: Record<Language, string> = { hu: 'hu_HU', en: 'en_GB' };

export const absoluteUrl = (path: string): string => `${SITE_URL}${path.startsWith('/') ? path : `/${path}`}`;

/**
 * Every locale's spelling of a named route — `/csatlakozz` and `/join-us`
 * are the same page, so each has to name the other as its alternate or
 * they compete as duplicate content.
 */
export const alternatesFor = (routeName: string): Array<{ lang: Language; path: string }> =>
  LANGUAGES.map((lang) => ({ lang, path: ROUTE_PATHS[lang][routeName] })).filter(
    (entry): entry is { lang: Language; path: string } => Boolean(entry.path),
  );

export type HeadMeta = {
  title?: string;
  description?: string;
  theme?: string;
  ogTitle?: string;
  ogDescription?: string;
  ogImage?: string;
  ogType?: string;
  ogUrl?: string;
  ogLocale?: string;
  canonical?: string;
  /** Omit for the default (indexable). "noindex, follow" for the 404. */
  robots?: string;
  alternates?: Array<{ lang: Language; path: string }>;
};

/** Tags this module owns get marked, so a rewrite can clear the previous set. */
const OWNED = 'data-seo';

const upsertMeta = (attr: 'name' | 'property', key: string, content?: string): void => {
  const existing = document.head.querySelector<HTMLMetaElement>(`meta[${attr}="${key}"]`);

  if (!content) {
    // Only remove what we created — never a tag index.html shipped.
    if (existing?.hasAttribute(OWNED)) existing.remove();
    return;
  }

  if (existing) {
    existing.setAttribute('content', content);
    return;
  }

  const el = document.createElement('meta');
  el.setAttribute(attr, key);
  el.setAttribute(OWNED, '');
  el.setAttribute('content', content);
  document.head.appendChild(el);
};

const upsertCanonical = (href?: string): void => {
  if (!href) return;

  const existing = document.head.querySelector<HTMLLinkElement>('link[rel="canonical"]');
  if (existing) {
    existing.setAttribute('href', href);
    return;
  }

  const el = document.createElement('link');
  el.setAttribute('rel', 'canonical');
  el.setAttribute(OWNED, '');
  el.setAttribute('href', href);
  document.head.appendChild(el);
};

const setAlternates = (alternates: HeadMeta['alternates']): void => {
  /*
    Clears the STATIC hreflang links from index.html too, not just the ones
    this module added. Those describe `/` — correct before the bundle runs,
    wrong the moment you are on /csatlakozz — and leaving them in place
    gave the document two conflicting `hreflang="hu"` links, each naming a
    different URL. There is no sensible merge; the live route wins.
  */
  document.head
    .querySelectorAll('link[rel="alternate"][hreflang]')
    .forEach((el) => el.remove());

  if (!alternates?.length) return;

  const append = (hreflang: string, path: string) => {
    const el = document.createElement('link');
    el.setAttribute('rel', 'alternate');
    el.setAttribute('hreflang', hreflang);
    el.setAttribute('href', absoluteUrl(path));
    el.setAttribute(OWNED, '');
    document.head.appendChild(el);
  };

  alternates.forEach(({ lang, path }) => append(lang, path));

  // x-default is the copy served to a visitor whose language we don't
  // match. Hungarian: this is a Hungarian university team.
  const fallback = alternates.find((entry) => entry.lang === 'hu') ?? alternates[0];
  append('x-default', fallback.path);
};

/** Write the whole set into <head>. Safe to call on every navigation. */
export const applyHead = (meta: HeadMeta): void => {
  if (typeof document === 'undefined') return;

  if (meta.title && document.title !== meta.title) {
    document.title = meta.title;
  }

  upsertMeta('name', 'description', meta.description);
  upsertMeta('name', 'theme-color', meta.theme);
  upsertMeta('name', 'robots', meta.robots
    ?? 'index, follow, max-image-preview:large, max-snippet:-1');

  upsertMeta('property', 'og:site_name', SITE_NAME);
  upsertMeta('property', 'og:type', meta.ogType ?? 'website');
  upsertMeta('property', 'og:url', meta.ogUrl);
  upsertMeta('property', 'og:title', meta.ogTitle ?? meta.title);
  upsertMeta('property', 'og:description', meta.ogDescription ?? meta.description);
  upsertMeta('property', 'og:image', meta.ogImage ?? SHARE_IMAGE);
  upsertMeta('property', 'og:locale', meta.ogLocale);
  upsertMeta(
    'property',
    'og:locale:alternate',
    meta.ogLocale
      ? Object.values(OG_LOCALE).find((value) => value !== meta.ogLocale)
      : undefined,
  );

  upsertMeta('name', 'twitter:card', 'summary_large_image');
  upsertMeta('name', 'twitter:title', meta.ogTitle ?? meta.title);
  upsertMeta('name', 'twitter:description', meta.ogDescription ?? meta.description);
  upsertMeta('name', 'twitter:image', meta.ogImage ?? SHARE_IMAGE);

  upsertCanonical(meta.canonical);
  setAlternates(meta.alternates);
};
