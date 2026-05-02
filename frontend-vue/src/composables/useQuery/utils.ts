import { isRef, reactive } from 'vue';
import type { CacheData, CacheState, QueryKey, RequestState } from './types';

const cacheAvailable = typeof self !== 'undefined' && 'caches' in self;

const getCacheStore = async () => caches.open('evrst-api');

const setCacheValue = async (key: string, value: unknown) => {
  const cache = await getCacheStore();
  await cache.put(key, new Response(JSON.stringify(value)));
};

const getCacheValue = async (key: string) => {
  const cache = await getCacheStore();
  const match = await cache.match(key);
  return match?.clone().json();
};

export const stringify = (key: QueryKey | undefined): string => {
  if (typeof key === 'string') return key;
  if (!Array.isArray(key)) return '';
  const out: unknown[] = [];
  for (const k of key) {
    out.push(isRef(k) ? k.value : k);
  }
  return JSON.stringify(out);
};

export const secondsBetweenDates = (a: number, b: number): number =>
  Math.floor(Math.abs(b - a) / 1000);

export const cacheStorage = reactive({
  cache: new Map<string, CacheData>(),
  requestStatus: new Map<string, RequestState>(),
  getCache(key: string) {
    return this.cache.get(key);
  },
  setCache<T>(key: string, value: CacheData<T>) {
    return this.cache.set(key, value);
  },
  async fillFromCacheAPI(key: string) {
    if (!cacheAvailable) return;
    const result = await getCacheValue(key);
    if (result) this.cache.set(key, result);
  },
  async saveCache<T>(key: string, value: CacheData<T>) {
    if (cacheAvailable) await setCacheValue(key, value);
  },
  hasCache(key: string) {
    return this.cache.has(key);
  },
  removeCache(key: string) {
    return this.cache.delete(key);
  },
  setCacheState(key: string, state: CacheState) {
    const tmp = this.cache.get(key);
    if (tmp) {
      this.setCache(key, { ...tmp, meta: { ...tmp.meta, cacheState: state } });
    }
  },
  setLoadingState(key: string, state: boolean) {
    const tmp = this.cache.get(key);
    if (tmp) {
      this.setCache(key, { ...tmp, meta: { ...tmp.meta, loading: state } });
    }
  },
  removeResponseData(key: string) {
    const tmp = this.cache.get(key);
    if (tmp) {
      this.setCache(key, { meta: tmp.meta, data: undefined });
    }
  },
});
