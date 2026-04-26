import type { Resource } from '@/types';

export interface AboutItem {
  title: string;
  description: string;
}

export type AboutItemResource = Resource<AboutItem>;
