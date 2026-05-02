const pendingErrorRequest = new Set();

const ApiRequestError = (error: any) => {
  const configStore = useConfigStore();
  if (pendingErrorRequest.has(error.url ?? error)) {
    return;
  }

  pendingErrorRequest.add(error);

  if (!configStore.tooManyRequest && error.status === 429) {
    configStore.tooManyRequest = true;
  }

  if (error.status) {
    configStore.requestStatus = error.status;
  }

  configStore.loading = false;
  configStore.requestCount = Math.max(0, configStore.requestCount - 1);

  return error;
};

export default ApiRequestError;
