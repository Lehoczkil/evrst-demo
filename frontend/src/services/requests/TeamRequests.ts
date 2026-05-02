import { api } from '@/lib/http/Api';

export interface TeamMemberGroupRef {
  id: number;
  slug: string;
  name: string;
  is_primary: boolean;
}

export interface TeamMember {
  id: number;
  name: string;
  degree: string | null;
  photo_url: string | null;
  groups: TeamMemberGroupRef[];
  position: number;
}

export interface TeamGroup {
  id: number;
  slug: string;
  name: string;
  kind: string | null;
  position: number;
}

export const TeamRequests = {
  members: (lang: string) =>
    api.get<TeamMember[]>('/team/members', { params: { lang } as any }),
  groups: (lang: string) =>
    api.get<TeamGroup[]>('/team/groups', { params: { lang } as any }),
};
