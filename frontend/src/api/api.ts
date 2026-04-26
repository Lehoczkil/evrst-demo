type QueryValue =
  | string
  | number
  | boolean
  | null
  | undefined
  | QueryValue[]
  | { [key: string]: QueryValue };

interface RequestConfig {
  url: string;
  method?: 'get' | 'post';
  query?: Record<string, QueryValue>;
  headers?: Record<string, string>;
  body?: unknown;
}

let currentLanguage = (() => {
  if (typeof window === 'undefined') return 'en';
  try {
    return window.localStorage.getItem('evrst:language') || 'en';
  } catch {
    return 'en';
  }
})();

function appendQuery(
  params: URLSearchParams,
  key: string,
  value: QueryValue,
): void {
  if (value === undefined || value === null) return;
  if (Array.isArray(value)) {
    value.forEach((entry, index) => appendQuery(params, `${key}[${index}]`, entry));
    return;
  }
  if (typeof value === 'object') {
    for (const [childKey, childValue] of Object.entries(value)) {
      appendQuery(params, `${key}[${childKey}]`, childValue);
    }
    return;
  }
  params.append(key, String(value));
}

function stringifyQuery(query: RequestConfig['query']): string {
  if (!query) return '';
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(query)) {
    appendQuery(params, key, value);
  }
  const search = params.toString();
  return search ? `?${search}` : '';
}

export const api = {
  setLanguage(lang: string) {
    currentLanguage = lang;
  },
  getLanguage() {
    return currentLanguage;
  },
  async request<T>(config: RequestConfig) {
    const { method = 'get', url, headers, body } = config;

    const hasBody = body !== undefined && method !== 'get';

    const response = await fetch(
      `${import.meta.env.VITE_API_URL}${url}${stringifyQuery(config.query)}`,
      {
        method,
        headers: {
          'X-Lang': currentLanguage,
          Accept: 'application/json',
          ...(hasBody ? { 'Content-Type': 'application/json' } : {}),
          ...headers,
        },
        ...(hasBody ? { body: JSON.stringify(body) } : {}),
      },
    );

    if (!response.ok) {
      throw new Error(`API ${method.toUpperCase()} ${url} failed: ${response.status}`);
    }

    const text = await response.text();
    return (text ? JSON.parse(text) : null) as T;
  },
};
