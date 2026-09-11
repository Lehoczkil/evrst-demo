import { computed, onBeforeUnmount, reactive, ref, unref, watch } from 'vue';
import { fetcher } from '@/helpers/fetcher';
import type { CacheData, useQueryType } from './types';
import { cacheStorage, secondsBetweenDates, stringify } from './utils';

const pendingRequests = ref(new Set<string>());

const defaultValues: Partial<useQueryType<any, any>> = {
  key: Math.floor(Math.random() * Date.now()).toString(),
  enabled: true,
  cache: false,
  useCacheApi: false,
  staleTime: 0,
  refetchTime: Infinity,
};

export const useQuery = <T, K = void>(query: useQueryType<T, K>) => {
  const queryData: useQueryType<T, K> = reactive({
    ...defaultValues,
    ...query,
  }) as useQueryType<T, K>;

  const processQueue = async (_data?: K) => {
    let revalidate = true;
    const cacheKey = stringify(queryData.key);

    if (pendingRequests.value.has(cacheKey)) return;

    cacheStorage.requestStatus.set(cacheKey, 'PENDING');

    if (!queryData.request) return;

    pendingRequests.value.add(cacheKey);

    /*
      Read the cache BEFORE the placeholder below is written.

      The placeholder is an entry with `data: undefined`, and both cache
      branches used to be reached after it existed — so `hasCache()` was
      true and `meta.time` was `Date.now()` on the very first run of every
      query. Every `cache: true` query therefore decided its own empty
      placeholder was a warm hit and returned without ever fetching, which
      left the whole page on its skeletons. A hit is an entry that
      actually carries data.
    */
    const warm = cacheStorage.getCache(cacheKey);
    const isHit = warm?.data !== undefined;

    if (!warm) {
      const initial: CacheData = {
        data: undefined,
        meta: { time: Date.now(), cacheState: 'PENDING', loading: true },
      };
      cacheStorage.setCache(cacheKey, initial);
    } else {
      cacheStorage.setLoadingState(cacheKey, true);
    }

    if (queryData.cache && queryData.refetchTime !== Infinity) {
      const cache = isHit ? warm : undefined;
      if (cache) {
        const elapsed = secondsBetweenDates(cache.meta.time, Date.now());
        if (elapsed < (queryData.staleTime as number)) {
          cacheStorage.setCacheState(cacheKey, 'FRESH');
          cacheStorage.requestStatus.set(cacheKey, 'SUCCESS');
          revalidate = false;
        } else if (elapsed <= (queryData.refetchTime as number)) {
          cacheStorage.setCacheState(cacheKey, 'STALE');
        } else {
          cacheStorage.setCacheState(cacheKey, 'REVALIDATE');
          cacheStorage.removeResponseData(cacheKey);
        }
      }
    } else if (queryData.cache && isHit) {
      // `refetchTime: Infinity` — fetched once, then held for the session.
      cacheStorage.setCacheState(cacheKey, 'FRESH');
      cacheStorage.requestStatus.set(cacheKey, 'SUCCESS');
      revalidate = false;
    }

    if (!revalidate) {
      pendingRequests.value.delete(cacheKey);
      cacheStorage.setLoadingState(cacheKey, false);
      queryData.onSuccess?.(data.value);
      queryData.onFinally?.();
      return;
    }

    const { data: response, error } = await fetcher(
      queryData.request(_data as K),
    );

    pendingRequests.value.delete(cacheKey);

    if (response !== undefined) {
      const value: CacheData = {
        data: response,
        meta: { time: Date.now(), cacheState: 'FRESH', loading: false },
      };
      cacheStorage.setCache(cacheKey, value);
      if (queryData.cache && queryData.useCacheApi) {
        cacheStorage.saveCache(cacheKey, value);
      }
      cacheStorage.requestStatus.set(cacheKey, 'SUCCESS');
      queryData.onSuccess?.(response as T);
      queryData.onFinally?.();
      return response as T;
    }

    if (error) {
      cacheStorage.setLoadingState(cacheKey, false);
      cacheStorage.requestStatus.set(cacheKey, 'FAILED');
      queryData.onError?.(error);
      queryData.onFinally?.();
      throw error;
    }
  };

  const fetch = (payload?: K) => processQueue(payload);

  const data = computed<T>(() => {
    const cacheKey = stringify(queryData.key);
    return cacheStorage.getCache(cacheKey)?.data as T;
  });

  const status = computed(() => {
    const cacheKey = stringify(queryData.key);
    return cacheStorage.requestStatus.get(cacheKey) ?? 'IDLE';
  });

  const cacheState = computed(() => {
    const cacheKey = stringify(queryData.key);
    return cacheStorage.getCache(cacheKey)?.meta.cacheState;
  });

  const isLoading = computed(() => {
    const cacheKey = stringify(queryData.key);
    return (
      cacheStorage.getCache(cacheKey)?.meta.loading ||
      pendingRequests.value.has(cacheKey)
    );
  });

  watch(
    () => queryData.key,
    () => {
      if (unref(queryData.enabled)) fetch();
    },
    { deep: true },
  );

  watch(
    () => queryData.enabled,
    (curr) => {
      if (unref(curr)) fetch();
    },
    { deep: true },
  );

  const initialize = async () => {
    if (unref(queryData.enabled)) {
      if (queryData.cache && queryData.useCacheApi) {
        await cacheStorage.fillFromCacheAPI(stringify(queryData.key));
      }
      fetch();
    }
  };

  initialize();

  onBeforeUnmount(() => {
    const cacheKey = stringify(queryData.key);
    if (!queryData.cache) cacheStorage.removeCache(cacheKey);
    cacheStorage.requestStatus.delete(cacheKey);
  });

  return {
    data,
    status,
    isLoading,
    cacheState,
    fetch,
  };
};
