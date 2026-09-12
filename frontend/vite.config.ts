import { fileURLToPath, URL } from 'node:url';
import { join } from 'node:path';
import { defineConfig } from 'vite';
import vitePlugins from './vite/plugins';

const backendUrl = process.env.VITE_DEV_BACKEND_URL ?? 'http://127.0.0.1:8000';

export default defineConfig({
  define: {
    __APP_VERSION__: JSON.stringify(process.env.npm_package_version),
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    port: 4200,
    host: true,
    allowedHosts: true,
    proxy: {
      '/api': {
        ws: false,
        target: backendUrl,
        changeOrigin: true,
      },
      '/storage': {
        target: backendUrl,
        changeOrigin: true,
      },
    },
  },
  plugins: vitePlugins,
  css: {
    preprocessorOptions: {
      scss: {
        loadPaths: [
          join(fileURLToPath(new URL('.', import.meta.url)), 'src/styles'),
          join(fileURLToPath(new URL('.', import.meta.url)), 'node_modules'),
        ],
        quietDeps: true,
        logger: {
          warn(message) {
            if (message.includes('Sass @import rules are deprecated')) {
              return;
            }
          },
        },
      },
    },
  },
  build: {
    outDir: 'build',
    assetsDir: 'assets',
    /*
      es2015 forced a regenerator transform onto every async function in
      the app for browsers that have not mattered for years — and the
      chrome's backdrop-filter needs far newer than that anyway.
    */
    target: 'es2020',
    /*
      OFF by default, because a source map ships the entire original source
      in `sourcesContent` — every comment, every note — and is fetchable by
      anyone. It quietly undid the whole point of stripping comments from
      the build: /assets/index-*.js.map was live on production, 476 KB of
      readable source, while index.html had been carefully cleaned.

      Nothing consumed them. There is no Sentry source-map upload step, so
      the maps were pure exposure. Set VITE_SOURCEMAP=true for a local
      `bun run build` when you actually need to read one; the dev server
      has maps regardless.
    */
    sourcemap: process.env.VITE_SOURCEMAP === 'true',
    manifest: true,
    chunkSizeWarningLimit: 800,
    reportCompressedSize: false,
    rollupOptions: {
      output: {
        manualChunks: {
          'vue-router': ['vue-router'],
          pinia: ['pinia'],
          'motion-v': ['motion-v'],
        },
      },
    },
  },
  optimizeDeps: {
    exclude: ['x----x----x'],
  },
});
