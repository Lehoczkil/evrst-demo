import path from 'path';
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  define: {
    __APP_VERSION__: JSON.stringify(process.env.npm_package_version),
  },
  resolve: {
    alias: [
      {
        find: '@',
        replacement: path.resolve(__dirname, 'src'),
      },
    ],
  },
  // Proxy /storage to the Laravel dev server so the SPA fetches uploaded
  // assets (rocket GLB, sponsor logos, drawings, etc.) from its own
  // origin. `php artisan serve` serves /storage as static files which
  // bypasses Laravel middleware, so CORS headers never get added — this
  // proxy sidesteps that. Production gets absolute URLs from the API
  // and relies on the deploy's CORS config.
  server: {
    host: '0.0.0.0',
    proxy: {
      '/storage': {
        target: process.env.VITE_DEV_BACKEND_URL ?? 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: 'build',
    chunkSizeWarningLimit: 800,
    rollupOptions: {
      treeshake: true,
      output: {
        manualChunks: {
          three: ['three'],
          '@mdx-js/mdx': ['@mdx-js/mdx'],
        },
      },
    },
  },
});
