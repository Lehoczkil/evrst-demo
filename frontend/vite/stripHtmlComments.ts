import type { Plugin } from 'vite';

/*
  Drop the HTML comments from index.html in a production build.

  Vite minifies the JS and the CSS, and @vitejs/plugin-vue already strips
  template comments out of compiled components — but nothing touches
  index.html, so every note in its <head> shipped verbatim to every
  visitor. That file carries the longest comments in the project: why the
  fonts are self-hosted, why there is no preconnect, why og:image is
  absolute. Reasoning for whoever edits it next, not for the browser.

  The comments stay in the file, and therefore in git. Only the build
  output loses them, which is the point: the explanation has to survive
  where the next person will look for it.

  `<!--[if …` is left alone — a conditional comment is markup with
  meaning, not prose.
*/
export const stripHtmlComments = (): Plugin => ({
  name: 'evrst-strip-html-comments',
  apply: 'build',

  transformIndexHtml: {
    // After Vite has injected its own tags, so nothing it adds is
    // re-scanned and nothing of ours is dropped before it is read.
    order: 'post',
    handler: (html) =>
      html
        .replace(/^[ \t]*<!--(?!\[if )(?:(?!-->)[\s\S])*?-->[ \t]*\r?\n?/gm, '')
        .replace(/\n{3,}/g, '\n\n'),
  },
});

export default stripHtmlComments;
