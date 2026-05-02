import type { FetchConfig } from '../FetchWrapper/FetchWrapper.interface';

const ApiRequestInterceptor = (config: FetchConfig) => {
  const configStore = useConfigStore();
  configStore.loading = true;
  configStore.requestCount += 1;

  config.headers = {
    Accept: 'application/json',
    ...config.headers,
  };

  return config;
};

export default ApiRequestInterceptor;
