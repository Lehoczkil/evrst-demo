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
    host: '0.0.0.0',
    proxy: {
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
      },
    },
  },
  build: {
    outDir: 'build',
    chunkSizeWarningLimit: 800,
    sourcemap: true,
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
});
