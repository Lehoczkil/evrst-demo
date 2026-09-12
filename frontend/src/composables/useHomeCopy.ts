import { ViewRequests, type HomeCopyPayload } from '@/services/requests/ViewRequests';

/*
  The home page's editable copy: the hero and the rocket sheet.

  Both blocks used to read straight from the message files, so changing a
  headline meant a deploy. They now come from a `views` row the panel
  edits (Site → Home texts), with the bundled translation as a per-string
  fallback.

  Per-string, not per-block, is the whole point: a half-filled form must
  degrade to the current text rather than to a blank hero. The admin can
  rewrite one line and leave the rest alone, and clearing a field restores
  the built-in copy instead of emptying the page.
*/

type LocaleMap = Record<string, string> | string | null | undefined;

export const useHomeCopy = () => {
  const { locale } = useLocale();
  const { t, tm, rt } = useI18n();

  const { data } = useQuery({
    key: ['view', 'home'],
    request: () => ViewRequests.home(),
    cache: true,
  });

  const payload = computed<HomeCopyPayload | null>(() => data.value?.[0]?.payload ?? null);

  /** One locale out of a `{en, hu}` map. Empty strings count as unset. */
  const pick = (value: LocaleMap): string => {
    if (!value) return '';
    if (typeof value === 'string') return value.trim();

    return (value[locale.value as string] ?? '').trim();
  };

  const at = (path: string): unknown =>
    path.split('.').reduce<unknown>(
      (node, key) => (node && typeof node === 'object' ? (node as Record<string, unknown>)[key] : undefined),
      payload.value,
    );

  /**
   * The CMS string at `path`, falling back to the message file under the
   * SAME key — the two are deliberately kept in step so a component names
   * a string once.
   */
  const text = (path: string): string => pick(at(path) as LocaleMap) || t(path);

  /** The scroll statements: `{en, hu}` rows, or the bundled array. */
  const lines = (path: string): string[] => {
    const rows = at(path);

    if (Array.isArray(rows) && rows.length) {
      const picked = rows.map((row) => pick(row as LocaleMap)).filter(Boolean);
      if (picked.length) return picked;
    }

    const raw = tm(path) as unknown[];

    return (Array.isArray(raw) ? raw : []).map((line) => rt(line as Parameters<typeof rt>[0]));
  };

  /** The spec table: label + value + unit, localised, or the bundled rows. */
  const specs = (path: string): Array<{ label: string; value: string; unit?: string }> => {
    const rows = at(path);

    if (Array.isArray(rows) && rows.length) {
      const mapped = rows
        .map((row) => {
          const spec = row as { label?: LocaleMap; value?: string; unit?: LocaleMap };

          return {
            label: pick(spec.label),
            value: (spec.value ?? '').trim(),
            unit: pick(spec.unit) || undefined,
          };
        })
        .filter((spec) => spec.label);

      if (mapped.length) return mapped;
    }

    const raw = tm(path) as unknown[];

    return (Array.isArray(raw) ? raw : []) as Array<{ label: string; value: string; unit?: string }>;
  };

  return { text, lines, specs };
};
