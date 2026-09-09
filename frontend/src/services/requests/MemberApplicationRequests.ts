import { api } from '@/lib/http/Api';
import type { ApplicationAnswers, ApplicationFormSchema } from '@/types/applicationForm';

export const MemberApplicationRequests = {
  /**
   * The form definition — sections, questions and choices. Editable in the
   * admin panel, which is why the page asks for it instead of hardcoding it.
   */
  form: (lang: string) =>
    api.get<ApplicationFormSchema>('/application-form', { params: { lang } as any }),

  submit: (data: ApplicationAnswers) =>
    api.post<{ id: string }>('/member-applications', { body: data as any }),
};
