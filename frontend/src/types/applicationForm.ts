/**
 * The join-us form, as served by GET /api/application-form.
 *
 * The questions are editable in the admin panel, so the page renders
 * whatever comes back rather than a fixed set of inputs. Labels arrive
 * already resolved to the requested locale.
 */
export type ApplicationFieldType =
  | 'text'
  | 'email'
  | 'textarea'
  | 'radio'
  | 'select'
  | 'checkbox';

export interface ApplicationFieldOption {
  value: string;
  label: string;
}

export interface ApplicationField {
  key: string;
  type: ApplicationFieldType;
  label: string;
  help: string | null;
  placeholder: string | null;
  required: boolean;
  maxLength: number;
  options: ApplicationFieldOption[];
}

export interface ApplicationSection {
  key: string;
  title: string;
  description: string | null;
  fields: ApplicationField[];
}

export interface ApplicationFormSchema {
  sections: ApplicationSection[];
}

/** What the applicant filled in, keyed by field key. */
export type ApplicationAnswers = Record<string, string | string[]>;
