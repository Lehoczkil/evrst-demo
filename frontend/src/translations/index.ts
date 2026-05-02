import en from './en';
import hu from './hu';

export default { en, hu };

export const LANGUAGES = ['en', 'hu'] as const;
export type Language = (typeof LANGUAGES)[number];
