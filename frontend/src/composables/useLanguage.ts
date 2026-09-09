import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import huRoutes from '@/translations/hu/routes';
import enRoutes from '@/translations/en/routes';
import { LANGUAGES, type Language } from '@/translations';

/*
  Localized route paths, ported from feat.agency's useLanguage.

  `/csatlakozz` and `/join-us` are BOTH real routes pointing at the same
  component. Switching language rewrites the current URL to the other
  locale's spelling of the same named route, and a first load resolves the
  locale FROM the path — so a shared /csatlakozz link opens in Hungarian
  whatever the visitor's stored choice was.

  That last part is the whole reason the path outranks localStorage: the
  person who sent the link chose the language, and a stored preference
  from a previous visit should not silently override what they shared.
*/

export const ROUTE_PATHS: Record<Language, Record<string, string>> = {
  hu: huRoutes,
  en: enRoutes,
};

/** Every path any locale spells this route as. */
export const pathsFor = (name: string) => [
  ...new Set(LANGUAGES.map((lang) => ROUTE_PATHS[lang][name]).filter(Boolean)),
];

/**
 * Which locale a path belongs to — or undefined when it is spelled the
 * same in every locale, which carries no signal. `/` is the obvious case:
 * it is the home path in both, so it says nothing about language.
 */
export const localeFromPath = (path: string): Language | undefined => {
  for (const lang of LANGUAGES) {
    for (const [name, localised] of Object.entries(ROUTE_PATHS[lang])) {
      if (localised !== path && !path.startsWith(`${localised}/`)) {
        continue;
      }
      const sharedWithAnother = LANGUAGES.some(
        (other) => other !== lang && ROUTE_PATHS[other][name] === localised,
      );
      if (!sharedWithAnother) {
        return lang;
      }
    }
  }

  return undefined;
};

export const useLanguage = () => {
  const router = useRouter();
  const route = useRoute();
  const { locale, setLocale } = useLocale();

  /** The route name of the current path, in whichever locale spells it. */
  const currentName = computed(() => {
    for (const lang of LANGUAGES) {
      for (const [name, localised] of Object.entries(ROUTE_PATHS[lang])) {
        if (localised === route.path) {
          return name;
        }
      }
    }

    return undefined;
  });

  /**
   * Switch language, and rewrite the URL to the new locale's spelling of
   * the route we are on.
   *
   * `replace`, not `push`: changing language is not a navigation the back
   * button should have to undo one step at a time.
   */
  const switchTo = async (next: Language) => {
    if (next === locale.value) {
      return;
    }
    setLocale(next);

    const name = currentName.value;
    const target = name ? ROUTE_PATHS[next][name] : undefined;
    if (target && target !== route.path) {
      await router.replace({ path: target, hash: route.hash });
    }
  };

  return { locale, switchTo, currentName };
};
