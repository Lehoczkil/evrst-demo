import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useConfigStore = defineStore('configStore', () => {
  const loading = ref<boolean>(false);
  const requestCount = ref<number>(0);
  const tooManyRequest = ref<boolean>(false);
  const requestStatus = ref<number | undefined>(undefined);

  return {
    loading,
    requestCount,
    tooManyRequest,
    requestStatus,
  };
});
