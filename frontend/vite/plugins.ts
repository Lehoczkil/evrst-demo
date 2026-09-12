import strip from '@rollup/plugin-strip';
import vue from '@vitejs/plugin-vue';
import { visualizer } from 'rollup-plugin-visualizer';
import UnoCSS from 'unocss/vite';
import webfontDownload from 'vite-plugin-webfont-dl';
import AutoImport from 'unplugin-auto-import/vite';
import Components from 'unplugin-vue-components/vite';
import type { Plugin } from 'vite';
import vueDevTools from 'vite-plugin-vue-devtools';
import { sitemap } from './sitemap';
import { stripHtmlComments } from './stripHtmlComments';

const plugins: Plugin[] = [
  vue(),
  vueDevTools(),
  AutoImport({
    defaultExportByFilename: false,
    include: [/\.[tj]sx?$/, /\.vue$/, /\.vue\?vue/],
    imports: [
      'vue',
      'vue-router',
      'vue-i18n',
      'pinia',
    ],
    dirs: [
      './src/store/**/**',
      './src/composables/**/**',
      './src/helpers/**',
      './src/services/**/**',
    ],
    vueTemplate: true,
    dts: './src/auto-imports.d.ts',
    eslintrc: {
      enabled: false,
    },
  }),
  Components({
    dirs: ['./src/components/**/**'],
    deep: true,
    dts: './src/components.d.ts',
    directives: true,
    allowOverrides: false,
    include: [/\.vue$/, /\.vue\?vue/],
    exclude: [/[\\/]node_modules[\\/]/, /[\\/]\.git[\\/]/],
  }),
  /*
    Rewrites the Google Fonts <link> in index.html to self-hosted copies at
    build time. Without it Space Grotesk and IBM Plex Mono are a
    third-party request on every page load, and their CSS is a
    render-blocking round trip the browser cannot start early.
  */
  webfontDownload(),

  /* sitemap.xml, generated from the route table — see ./sitemap.ts. */
  sitemap(),

  /* index.html's comments are for whoever edits it, not for visitors. */
  stripHtmlComments(),

  UnoCSS(),
  visualizer({
    emitFile: true,
    filename: '_stats.html',
    brotliSize: true,
  }),
];

if (process.env.NODE_ENV === 'production') {
  plugins.push(
    strip({
      include: '**/*.{js,ts}',
    }),
  );
}

export default plugins;
