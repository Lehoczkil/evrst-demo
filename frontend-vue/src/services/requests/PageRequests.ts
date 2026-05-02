import { api } from '@/lib/http/Api';
import type { PageResource } from '@/types/api';

export const PageRequests = {
  bySlug: (slug: string) =>
    api.get<PageResource[]>('/resource', {
      params: {
        collectionId: import.meta.env.VITE_PAGES_COLLECTION_ID,
        where: { payload: { path: ['name'], equals: slug } },
      } as any,
    }),
};
