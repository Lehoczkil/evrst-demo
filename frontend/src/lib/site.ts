/*
  Site-wide constants that are NOT copy.

  The team's email lives here rather than in the message files for two
  reasons. The blunt one: `@` is vue-i18n's linked-message sigil, so
  `evrstrocket@gmail.com` as a message value throws
  `Invalid linked format` at compile time and takes every component that
  reads it down with it — which is how this file came to exist.

  The better one: an email address is not translatable content. Putting
  it in two locale files means two places to change it and one chance to
  change only one of them.
*/
export const CONTACT_EMAIL = 'evrstrocket@gmail.com';

/** The university's public site, linked from the footer. */
export const UNIVERSITY_URL = 'https://uni-obuda.hu';
