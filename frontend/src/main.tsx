import { createRoot } from 'react-dom/client';
import { MantineProvider } from '@mantine/core';
import { ModalsProvider } from '@mantine/modals';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { RouterProvider } from 'react-router/dom';

import { getRouter } from './router';
import theme from './theme';
import { I18nProvider, LocaleSync } from './i18n';

import '@mantine/core/styles.css';
import './styles/globals.css';

const queryClient = new QueryClient();
const router = getRouter(queryClient);

createRoot(document.getElementById('root') as HTMLElement).render(
  <MantineProvider defaultColorScheme='dark' theme={theme}>
    <ModalsProvider>
      <I18nProvider>
        <QueryClientProvider client={queryClient}>
          <LocaleSync />
          <RouterProvider router={router} />
        </QueryClientProvider>
      </I18nProvider>
    </ModalsProvider>
  </MantineProvider>,
);
