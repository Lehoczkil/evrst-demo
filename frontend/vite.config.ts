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
