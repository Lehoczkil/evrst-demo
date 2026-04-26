import { evaluate } from '@mdx-js/mdx';
import * as runtime from 'react/jsx-runtime';
import type { LoaderFunctionArgs } from 'react-router';
import type { QueryClient } from '@tanstack/react-query';

import { api } from '@/api';
import type { PageResource, Resource } from '@/types';

import { PAGE_COLLECTION_ID } from './constants';

type MDXContent = Awaited<ReturnType<typeof evaluate>>['default'];

export interface PageLoaderData {
  page: PageResource;
  MDXContent: MDXContent;
  queryData: Record<string, unknown>;
}

/**
 * @TODO Handle errors with ErrorBoundary
 */
export const loader = (args: LoaderFunctionArgs) => {
  const queryClient = args.context.queryClient as QueryClient;
  const pageName = args.params.pageName;

  if (!pageName) throw Error('Incorrect page name');

  const pageLoaderData = (async () => {
    const page = await fetchPage(queryClient, pageName);

    if (!page) throw Error('Page not found');

    const queryData = await fetchQueries(queryClient, page.payload.queries);

    const { default: MDXContent } = await evaluate(
      page.payload.content || '',
      runtime,
    );

    return { page, queryData, MDXContent };
  })();

  return { pageLoaderData };
};

async function fetchPage(queryClient: QueryClient, pageName: string) {
  const pages = await queryClient.fetchQuery({
    queryKey: [PAGE_COLLECTION_ID, pageName],
    staleTime: Infinity,
    queryFn: () => {
      return api.request<PageResource[]>({
        url: `/resource`,
        query: {
          collectionId: PAGE_COLLECTION_ID,
          where: { payload: { path: ['name'], equals: pageName } },
        },
      });
    },
  });

  return pages.at(0) || null;
}

async function fetchQueries(
  queryClient: QueryClient,
  queries: PageResource['payload']['queries'],
): Promise<PageLoaderData['queryData']> {
  if (!queries) return {};

  const results = await Promise.all(
    Object.entries(queries).map(async ([property, query]) => [
      property,
      await queryClient.fetchQuery({
        queryKey: [query.type, query.id],
        queryFn: () =>
          api.request<Resource<unknown>[]>({
            url: `/resource`,
            query: { collectionId: query.id },
          }),
      }),
    ]),
  );

  return Object.fromEntries(results);
}
