import { api } from '@/api';
import type { PageResourceWithObjects } from '@/types';

interface HomePageData {
  rocket?: {
    scale?: number | [number, number, number];
    position?: [number, number, number];
  };
}

export const loader = async () => {
  return await api.request<PageResourceWithObjects<HomePageData>>({
    url: '/resource/f8e49c86-d46a-4720-8f26-3d01499b13c4',
    query: { include: 'objects' },
  });
};
