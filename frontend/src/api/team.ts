import { api } from './api';

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

export function fetchTeamMembers(lang: string): Promise<TeamMember[]> {
  return api.request<TeamMember[]>({
    url: '/team/members',
    query: { lang },
  });
}

export function fetchTeamGroups(lang: string): Promise<TeamGroup[]> {
  return api.request<TeamGroup[]>({
    url: '/team/groups',
    query: { lang },
  });
}
