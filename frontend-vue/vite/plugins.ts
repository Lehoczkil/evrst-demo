import strip from '@rollup/plugin-strip';
import vue from '@vitejs/plugin-vue';
import { visualizer } from 'rollup-plugin-visualizer';
import UnoCSS from 'unocss/vite';
import AutoImport from 'unplugin-auto-import/vite';
import Components from 'unplugin-vue-components/vite';
import type { Plugin } from 'vite';
import vueDevTools from 'vite-plugin-vue-devtools';

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
      {
        from: 'vue-formify',
        imports: ['useForm', 'useInput'],
      },
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
