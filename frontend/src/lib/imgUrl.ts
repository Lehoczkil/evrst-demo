export type ImgFormat = 'avif' | 'webp' | 'jpg' | 'png' | 'original';
export type ImgFit = 'cover' | 'contain' | 'inside';

export interface ImgOpts {
  width?: number;
  height?: number;
  format?: ImgFormat;
  quality?: number;
  dpr?: number;
  fit?: ImgFit;
}

/**
 * Build a URL for the backend's /api/img transform endpoint.
 *
 * `path` is the relative path under the public disk — e.g.
 * "team-members/abc.png", "sponsors/foo/logo.svg". Anything that
 * already looks like a full URL or data URI is returned untouched
 * so callers can blindly pipe API payload values through.
 */
export const imgUrl = (path: string, opts: ImgOpts = {}): string => {
  if (!path) return path;
  if (/^(https?:|data:|blob:)/i.test(path)) return path;

  const base = (import.meta.env.VITE_API_URL ?? '').replace(/\/+$/, '');
  const params = new URLSearchParams();
  params.set('path', path.replace(/^\/+/, ''));
  if (opts.width) params.set('w', String(opts.width));
  if (opts.height) params.set('h', String(opts.height));
  if (opts.format) params.set('f', opts.format);
  if (opts.quality) params.set('q', String(opts.quality));
  if (opts.dpr && opts.dpr > 1) params.set('dpr', String(opts.dpr));
  if (opts.fit) params.set('fit', opts.fit);

  return `${base}/img?${params.toString()}`;
};

/**
 * Strip the public-disk URL prefix from a value the API returned.
 * Works for absolute URLs ("https://example.com/storage/foo.png")
 * and for the relative form Laravel emits in dev ("/storage/foo.png").
 * Anything that doesn't look like a /storage/ URL is returned as-is —
 * callers can feed that result straight into <ResponsiveImage path=…>.
 */
export const pathFromStorageUrl = (value: string | null | undefined): string | null => {
  if (!value) return null;
  if (/^data:/i.test(value)) return null;

  const match = value.match(/\/storage\/(.+)$/);
  if (match) return match[1];

  return value;
};
