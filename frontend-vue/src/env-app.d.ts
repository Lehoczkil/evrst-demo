/// <reference types="vite/client" />

declare const __APP_VERSION__: string;

interface ImportMetaEnv {
  readonly VITE_API_URL: string;
  readonly VITE_DEV_BACKEND_URL?: string;
  readonly VITE_ABOUT_PROJECTS_COLLECTION_ID?: string;
  readonly VITE_ABOUT_GOALS_COLLECTION_ID?: string;
  readonly VITE_SPONSORS_COLLECTION_ID?: string;
  readonly VITE_MENTORS_COLLECTION_ID?: string;
  readonly VITE_EVENTS_COLLECTION_ID?: string;
  readonly VITE_PAGES_COLLECTION_ID?: string;
  readonly VITE_HOME_RESOURCE_ID?: string;
  readonly VITE_SENTRY_DSN?: string;
  readonly VITE_ENV?: string;
  readonly VITE_COMMIT_HASH?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
