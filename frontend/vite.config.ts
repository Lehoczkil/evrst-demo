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
    target: 'es2015',
    sourcemap: true,
    manifest: true,
    chunkSizeWarningLimit: 800,
    reportCompressedSize: false,
    rollupOptions: {
      output: {
        manualChunks: {
          three: ['three'],
          'vue-router': ['vue-router'],
          pinia: ['pinia'],
          primevue: ['primevue/config'],
          '@primevue/themes': ['@primevue/themes', '@primevue/themes/aura'],
          'vue-formify': ['vue-formify'],
          'motion-v': ['motion-v'],
        },
      },
    },
  },
  optimizeDeps: {
    exclude: ['x----x----x'],
  },
});
