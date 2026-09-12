import type { Plugin } from 'vite';
import huRoutes from '../src/translations/hu/routes';
import enRoutes from '../src/translations/en/routes';

/*
  Emits sitemap.xml into the build.

  Generated rather than committed, because the source of truth is the route
  table: `/csatlakozz` and `/join-us` are the same page spelled two ways,
  and a hand-written sitemap goes stale the first time someone adds a
  route. Each entry carries the other locale's spelling as an
  `xhtml:link`, which is how a search engine is told these are
  translations of one page rather than duplicates competing with each
  other.

  CMS pages are deliberately NOT listed. They are matched by the
  `/:slug(.*)*` catch-all against `payload.name`, and the only row in the
  collection today is `home` — which is data for the home page, not a page
  at `/home`. Listing the collection wholesale would submit URLs that are
  duplicates of `/`. If routable CMS pages become a real feature, this is
  the place to add them, from an endpoint that knows which ones route.
*/

const LOCALES = { hu: huRoutes, en: enRoutes } as const;

type Locale = keyof typeof LOCALES;

const xml = (siteUrl: string): string => {
  const names = new Set<string>(
    Object.values(LOCALES).flatMap((routes) => Object.keys(routes)),
  );

  const entries = [...names].flatMap((name) => {
    const spellings = (Object.keys(LOCALES) as Locale[])
      .map((lang) => ({ lang, path: (LOCALES[lang] as Record<string, string>)[name] }))
      .filter((entry) => Boolean(entry.path));

    const alternates = [
      ...spellings.map(
        ({ lang, path }) =>
          `    <xhtml:link rel="alternate" hreflang="${lang}" href="${siteUrl}${path}" />`,
      ),
      `    <xhtml:link rel="alternate" hreflang="x-default" href="${siteUrl}${
        spellings.find((entry) => entry.lang === 'hu')?.path ?? spellings[0].path
      }" />`,
    ].join('\n');

    // One <url> per distinct path — `/` is spelled the same in both
    // locales and must not be listed twice.
    return [...new Set(spellings.map((entry) => entry.path))].map(
      (path) =>
        `  <url>\n    <loc>${siteUrl}${path}</loc>\n${alternates}\n` +
        `    <changefreq>weekly</changefreq>\n` +
        `    <priority>${path === '/' ? '1.0' : '0.8'}</priority>\n  </url>`,
    );
  });

  return [
    '<?xml version="1.0" encoding="UTF-8"?>',
    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"',
    '        xmlns:xhtml="http://www.w3.org/1999/xhtml">',
    ...entries,
    '</urlset>',
    '',
  ].join('\n');
};

const siteUrl = () => (process.env.VITE_SITE_URL ?? 'https://evrst.hu').replace(/\/+$/, '');

export const sitemap = (): Plugin => ({
  name: 'evrst-sitemap',

  generateBundle() {
    this.emitFile({ type: 'asset', fileName: 'sitemap.xml', source: xml(siteUrl()) });
  },

  /*
    Serve it in dev too. Not a convenience: without this the file exists
    only in a production build, so nothing — no test, no local check of
    the robots.txt reference — can see it until it is already deployed.
  */
  configureServer(server) {
    server.middlewares.use((req, res, next) => {
      if (req.url?.split('?')[0] !== '/sitemap.xml') return next();

      res.setHeader('Content-Type', 'application/xml');
      res.end(xml(siteUrl()));
    });
  },
});

export default sitemap;
