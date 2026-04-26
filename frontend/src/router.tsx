import { createBrowserRouter } from 'react-router';
import type { QueryClient } from '@tanstack/react-query';

import { Layout } from './components/layout';
import { PlaceholderPage } from './pages/placeholder';

export function getRouter(queryClient: QueryClient) {
  return createBrowserRouter([
    {
      path: '/',
      lazy: async () => {
        const { default: Home, loader } = await import('@/pages/home');
        return { Component: Home, loader };
      },
    },
    {
      path: '/',
      Component: Layout,
      children: [
        {
          path: 'events',
          element: (
            <PlaceholderPage
              titleKey='section.events'
              bodyKey='placeholder.events'
            />
          ),
        },
        {
          path: 'about',
          element: (
            <PlaceholderPage
              titleKey='section.about'
              bodyKey='placeholder.about'
            />
          ),
        },
        {
          path: 'team',
          element: (
            <PlaceholderPage
              titleKey='section.team'
              bodyKey='placeholder.team'
            />
          ),
        },
        {
          path: 'join-us',
          lazy: async () => {
            const { JoinUsPage } = await import('./pages/join-us');
            return { Component: JoinUsPage };
          },
        },
        {
          path: ':pageName',
          lazy: async () => {
            const { DynamicPage, loader } = await import('./pages/dynamic-page');
            return {
              Component: DynamicPage,
              loader: (args: Parameters<typeof loader>[0]) => {
                args.context.queryClient = queryClient;
                return loader(args);
              },
            };
          },
        },
      ],
    },
  ]);
}
