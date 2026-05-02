const ApiResponseInterceptor = <T>(response: T) => {
  const configStore = useConfigStore();
  configStore.loading = false;
  configStore.requestCount = Math.max(0, configStore.requestCount - 1);
  return response;
};

export default ApiResponseInterceptor;
