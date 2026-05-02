type RequestType = 'json' | 'text' | 'arrayBuffer' | 'blob';

export type FetchConfig<T = unknown> = Omit<RequestInit, 'body' | 'headers'> & {
  body?: T | null;
  headers?: { [key: string]: string };
  baseUrl?: string | URL;
  params?: T;
  url?: string | URL;
  type?: RequestType;
};

export type InterceptorManagerType<T> = {
  use(
    fullfield?: (value: T) => T | Promise<T>,
    reject?: (value: T) => T,
  ): InterceptorHandler<T>;
  forEach: (data: T, failed?: boolean) => Promise<void>;
};

export type InterceptorType<T = unknown> = {
  request: InterceptorManagerType<FetchConfig>;
  response: InterceptorManagerType<T>;
};

export type InterceptorHandler<T> = {
  fullfield: ((value: T) => Promise<T>) | undefined;
  reject: ((value: T) => T) | undefined;
};

export type InterceptorFunction<T> = ((value: T) => Promise<T>) | undefined;
export type InterceptorErrorFunction<T> = ((value: T) => T) | undefined;
