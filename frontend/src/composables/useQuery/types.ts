import type { ComputedRef, Ref } from 'vue';

export type QueryKey = string | (string | number | object | Ref)[];

export type useQueryType<T, K> = {
  key?: QueryKey | undefined;
  enabled?: ComputedRef<boolean> | Ref<boolean> | boolean;
  cache?: boolean;
  request?: (data: K, ...args: any[]) => Promise<T>;
  onError?: (error: any) => void;
  onSuccess?: (data: T) => void;
  onFinally?: () => void;
  staleTime?: number;
  refetchTime?: number;
  useCacheApi?: boolean;
};

export type RequestQueue = {
  key: string;
  request: (data?: any) => Promise<any | void>;
};

export type RequestState = 'IDLE' | 'PENDING' | 'FAILED' | 'SUCCESS';

export type CacheData<T = unknown> = {
  data: T;
  meta: {
    time: number;
    cacheState?: CacheState;
    loading: boolean;
  };
};

export type CacheState = 'FRESH' | 'STALE' | 'REVALIDATE' | 'PENDING';
