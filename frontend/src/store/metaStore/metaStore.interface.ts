import type { Language } from '@/translations';

export type MetaType = {
  title?: string;
  description?: string;
  ogTitle?: string;
  ogDescription?: string;
  ogImage?: string;
  ogType?: string;
  theme?: string;
  /**
   * Left undefined a page is indexable. The 404 sets "noindex, follow":
   * the SPA is served by `try_files … /index.html`, so an unknown path
   * answers 200 with the not-found page — a soft 404 that Google would
   * otherwise index as a real page.
   */
  robots?: string | null;
  /** Route name, used to derive canonical + hreflang. */
  routeName?: string;
  alternates?: Array<{ lang: Language; path: string }>;
};
