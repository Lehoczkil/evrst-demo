import type { FetchConfig } from '../FetchWrapper/FetchWrapper.interface';

const STORAGE_KEY = 'evrst:language';

const LanguageRequestInterceptor = (config: FetchConfig) => {
  const language = (typeof window !== 'undefined' && localStorage.getItem(STORAGE_KEY)) || 'en';
  config.headers = {
    ...config.headers,
    'X-Lang': language,
  };
  return config;
};

export default LanguageRequestInterceptor;
