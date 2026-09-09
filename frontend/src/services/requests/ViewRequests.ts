import { api } from '@/lib/http/Api';
import type { Resource } from '@/types/api';

export interface ViewPayload {
  name: string;
  title: string;
  content: string;
}

export type ViewResource = Resource<ViewPayload>;

/*
  The `views` collection: long-form copy the team edits in the panel, keyed
  by a `name` in the payload rather than by id.

  The bracket serialiser in FetchWrapper flattens this into
  `where[payload][path][0]=name&where[payload][equals]=about`, which is the
  shape ResourceController::applyWhere understands.
*/
export const ViewRequests = {
  byName: (name: string) =>
    api.get<ViewResource[]>('/resource', {
      params: {
        collectionId: import.meta.env.VITE_VIEWS_COLLECTION_ID,
        where: { payload: { path: ['name'], equals: name } },
      } as Record<string, unknown>,
    }),
};
