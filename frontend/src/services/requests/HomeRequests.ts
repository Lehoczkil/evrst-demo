import { api } from '@/lib/http/Api';
import type { PageResourceWithObjects } from '@/types/api';

export interface HomePageData {
  rocket?: {
    scale?: number | [number, number, number];
    position?: [number, number, number];
  };
}

export const HomeRequests = {
  page: () =>
    api.get<PageResourceWithObjects<HomePageData>>(
      `/resource/${import.meta.env.VITE_HOME_RESOURCE_ID}`,
      { params: { include: 'objects' } as any },
    ),
};
