const ApiResponseError = (error: any) => {
  const configStore = useConfigStore();
  configStore.loading = false;
  return Promise.reject(error);
};

export default ApiResponseError;
