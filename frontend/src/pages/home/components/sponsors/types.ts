import type { Resource } from '@/types';

export interface Sponsor {
  name: string;
  description: string;
  year: number | null;
  url: string;
  logo?: string;
}

export type SponsorResource = Resource<Sponsor>;
