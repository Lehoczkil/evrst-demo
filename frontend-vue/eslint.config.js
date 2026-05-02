import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import pluginVue from 'eslint-plugin-vue';
import vueParser from 'vue-eslint-parser';
import tsParser from '@typescript-eslint/parser';

export default [
  js.configs.recommended,
  ...pluginVue.configs['flat/strongly-recommended'],
  ...pluginVue.configs['flat/recommended'],
  ...tseslint.configs.recommended,
  {
    languageOptions: {
      parser: vueParser,
      parserOptions: {
        parser: tsParser,
        project: ['tsconfig.json', 'tsconfig.node.json'],
        extraFileExtensions: ['vue'],
      },
    },
    files: ['src/**/*.{ts,js,vue}'],
    ignores: ['/**/*.d.ts'],
    rules: {
      'comma-dangle': ['error', 'always-multiline'],
      eqeqeq: ['error', 'always'],
      'no-console': 0,
      'no-unused-vars': 0,
      'no-undef': 0,
      'prefer-const': 'warn',
      quotes: ['error', 'single'],
      semi: ['error', 'always'],
      'vue/component-name-in-template-casing': ['error'],
      'vue/multi-word-component-names': 0,
      'vue/no-v-html': 0,
      'vue/html-self-closing': 0,
      'vue/require-direct-export': 0,
      'vue/max-attributes-per-line': 0,
      'vue/singleline-html-element-content-newline': 0,
      'vue/html-indent': 0,
      'vue/html-closing-bracket-newline': 0,
      'vue/attributes-order': 0,
      '@typescript-eslint/no-explicit-any': 0,
      '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
      '@typescript-eslint/no-unused-expressions': 0,
    },
  },
  {
    ignores: [
      'node_modules/**',
      'build/**',
      'dist/**',
      'src/auto-imports.d.ts',
      'src/components.d.ts',
      '*.config.js',
      '*.config.ts',
      'bin/**',
    ],
  },
];
