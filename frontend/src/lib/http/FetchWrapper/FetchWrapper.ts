import type { FetchConfig, InterceptorType } from './FetchWrapper.interface';
import { InterceptorManager } from './InterceptorManager';

const deepCopy = <T>(val: T): T => JSON.parse(JSON.stringify(val));

const buildQuery = (params: Record<string, unknown>): string => {
  const search = new URLSearchParams();
  const append = (key: string, value: unknown) => {
    if (value === undefined || value === null) return;
    if (Array.isArray(value)) {
      value.forEach((v, i) => append(`${key}[${i}]`, v));
      return;
    }
    if (typeof value === 'object') {
      for (const [k, v] of Object.entries(value)) append(`${key}[${k}]`, v);
      return;
    }
    search.append(key, String(value));
  };
  for (const [k, v] of Object.entries(params)) append(k, v);
  return search.toString();
};

class FetchWrapper {
  public interceptors: InterceptorType = {
    request: new InterceptorManager() as InterceptorType['request'],
    response: new InterceptorManager(),
  };

  defaultConfig: FetchConfig;

  constructor(_defaultConfig?: FetchConfig) {
    this.defaultConfig = { ..._defaultConfig };
  }

  public async get<T>(url: RequestInfo | URL, config: FetchConfig = {}) {
    config = { ...deepCopy(this.defaultConfig), ...config };
    config.url = url.toString();

    return this.request<T>(url, config);
  }

  public async post<T = unknown>(url: RequestInfo | URL, config: FetchConfig = {}) {
    config = { ...deepCopy(this.defaultConfig), ...config };
    config.url = url.toString();
    if (config.body !== undefined && config.body !== null) {
      config.body = JSON.stringify(config.body);
    }
    config.method = 'POST';

    return this.request<T>(url, config);
  }

  public async put<T = unknown>(url: RequestInfo | URL, config: FetchConfig = {}) {
    config = { ...deepCopy(this.defaultConfig), ...config };
    config.url = url.toString();
    if (config.body !== undefined && config.body !== null) {
      config.body = JSON.stringify(config.body);
    }
    config.method = 'PUT';

    return this.request<T>(url, config);
  }

  public async delete<T = unknown>(url: RequestInfo | URL, config: FetchConfig = {}) {
    config = { ...deepCopy(this.defaultConfig), ...config };
    config.url = url.toString();
    config.method = 'DELETE';

    return this.request<T>(url, config);
  }

  private async request<TResponse>(
    url: RequestInfo | URL,
    config: FetchConfig = {},
  ): Promise<TResponse> {
    await this.interceptors.request.forEach(config);

    let finalUrl = url.toString();
    const queryString = config.params ? buildQuery(config.params as Record<string, unknown>) : '';

    if (config.baseUrl) {
      const base = config.baseUrl.toString().replace(/\/$/, '');
      const path = finalUrl.startsWith('/') ? finalUrl : `/${finalUrl}`;
      finalUrl = `${base}${path}${queryString ? `?${queryString}` : ''}`;
    } else if (queryString) {
      finalUrl = `${finalUrl}?${queryString}`;
    }

    const response = await fetch(finalUrl, config as RequestInit);

    if (response.ok) {
      let data: unknown;
      const type = config.type ?? 'json';
      if (type === 'json') {
        const text = await response.text();
        data = text ? JSON.parse(text) : null;
      } else if (type === 'blob') {
        data = await response.blob();
      } else if (type === 'text') {
        data = await response.text();
      } else if (type === 'arrayBuffer') {
        data = await response.arrayBuffer();
      }

      await this.interceptors.response.forEach({ headers: response.headers, data } as TResponse);

      return data as TResponse;
    }

    let error: unknown;
    try {
      error = await response.json();
    } catch {
      error = { status: response.status, message: response.statusText };
    }

    await this.interceptors.request.forEach(error as FetchConfig, true);
    await this.interceptors.response.forEach(error as TResponse, true);

    return Promise.reject(error);
  }
}

export default FetchWrapper;
