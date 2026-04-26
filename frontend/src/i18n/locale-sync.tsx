import { useEffect } from 'react';
import { useQueryClient } from '@tanstack/react-query';

import { api } from '@/api';
import { useTranslation } from './i18n';

/**
 * Keeps the API client + react-query cache in sync with the current UI
 * language. Mount inside QueryClientProvider + I18nProvider.
 */
export function LocaleSync() {
  const { language } = useTranslation();
  const queryClient = useQueryClient();

  useEffect(() => {
    api.setLanguage(language);
    queryClient.invalidateQueries();
  }, [language, queryClient]);

  return null;
}
