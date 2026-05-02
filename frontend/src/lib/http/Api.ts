import FetchWrapper from './FetchWrapper/FetchWrapper';
import ApiRequestError from './interceptors/ApiRequestError';
import ApiResponseError from './interceptors/ApiResponseError';
import ApiRequestInterceptor from './interceptors/ApiRequestInterceptor';
import ApiResponseInterceptor from './interceptors/ApiResponseInterceptor';
import LanguageRequestInterceptor from './interceptors/LanguageRequestInterceptor';

const api = new FetchWrapper({
  baseUrl: import.meta.env.VITE_API_URL,
  type: 'json',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

api.interceptors.request.use(ApiRequestInterceptor, ApiRequestError);
api.interceptors.request.use(LanguageRequestInterceptor, ApiRequestError);
api.interceptors.response.use(ApiResponseInterceptor, ApiResponseError);

export { api };
