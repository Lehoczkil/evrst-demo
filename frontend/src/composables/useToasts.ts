import { readonly, ref } from 'vue';

/*
  A three-line replacement for PrimeVue's ToastService.

  Module-level state on purpose: a toast is raised from wherever something
  finished and rendered by one host near the end of the document, so the
  two cannot be related by props. There is exactly one host (see
  Form/ToastHost.vue).
*/

export type ToastTone = 'info' | 'success' | 'error';

export type Toast = {
  id: number;
  tone: ToastTone;
  text: string;
};

const items = ref<Toast[]>([]);
let nextId = 1;

/** How long a toast stays up. Long enough to read a sentence. */
const LIFE_MS = 4000;

export const useToasts = () => {
  const dismiss = (id: number) => {
    items.value = items.value.filter((item) => item.id !== id);
  };

  const push = (tone: ToastTone, text: string) => {
    const id = nextId;
    nextId += 1;
    items.value = [...items.value, { id, tone, text }];
    window.setTimeout(() => dismiss(id), LIFE_MS);

    return id;
  };

  return {
    toasts: readonly(items),
    push,
    dismiss,
    info: (text: string) => push('info', text),
    success: (text: string) => push('success', text),
    error: (text: string) => push('error', text),
  };
};
