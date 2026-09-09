/*
  The Hungarian URL for every named route.

  Real HU paths, shareable and indexable — the point of localized routing
  rather than a `?lang=` query. The keys are the route NAMES, and both
  locales must declare the same set: useLanguage looks a path up by name
  in one map and swaps in the other's.
*/
export default {
  home: '/',
  joinUs: '/csatlakozz',
} as const;
