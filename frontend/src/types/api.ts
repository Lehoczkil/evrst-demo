export interface ApiObject {
  id: string;
  url: string;
  key: string | null;
}

export interface Resource<TPayload = unknown> {
  id: string;
  payload: TPayload;
  objects?: ApiObject[];
  createdAt: string;
  updatedAt: string | null;
}

export interface ResourceWithObjects<TPayload> extends Resource<TPayload> {
  objects: ApiObject[];
}

export interface Page<TData = unknown> {
  title: string;
  content: string | null;
  name?: string;
  data?: TData;
  queries?: { [property: string]: { type: 'collection'; id: string } };
}

export interface View {
  name: string;
  content: string;
}

export type ViewResource = Resource<View>;
export type PageResource<TData = unknown> = Resource<Page<TData>>;
export type PageResourceWithObjects<TData = unknown> = ResourceWithObjects<Page<TData>>;
