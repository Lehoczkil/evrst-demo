import { api } from '@/lib/http/Api';

export interface MemberApplicationPayload {
  email: string;
  name: string;
  university: string;
  education: string;
  faculty: string;
  why: string;
  hours: string;
  languages: string[];
  department: string;
  tasks: string;
  skills: string;
}

export const MemberApplicationRequests = {
  submit: (data: MemberApplicationPayload) =>
    api.post<{ id: string }>('/member-applications', { body: data as any }),
};
