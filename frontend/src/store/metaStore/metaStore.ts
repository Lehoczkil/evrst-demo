import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { MetaType } from './metaStore.interface';

export const useMetaStore = defineStore('metaStore', () => {
  const meta = ref<MetaType>({
    title: undefined,
    description: undefined,
    ogTitle: undefined,
    ogDescription: undefined,
    ogImage: undefined,
    theme: '#0c0d0e',
  });

  const setMeta = (newMeta: MetaType) => {
    meta.value = { ...meta.value, ...newMeta };
  };

  return { meta, setMeta };
});
