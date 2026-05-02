import transformerVariantGroup from '@unocss/transformer-variant-group';
import transformerDirectives from '@unocss/transformer-directives';
import { defineConfig, presetWind3 } from 'unocss';

export default defineConfig({
  presets: [presetWind3()],
  transformers: [transformerVariantGroup(), transformerDirectives()],
  theme: {
    breakpoints: {
      sm: '576px',
      md: '768px',
      lg: '992px',
      xl: '1200px',
      xxl: '1366px',
    },
    colors: {
      primary: 'var(--color-primary)',
      bgDark: 'var(--bg-dark)',
      bgGray: 'var(--bg-gray)',
      cardBg: 'var(--card-bg)',
      cardBorder: 'var(--card-border)',
      cardBorderAccent: 'var(--card-border-accent)',
    },
  },
  rules: [
    [/^fs-?(?:-?(.+))?$/, ([, size]) => ({ 'font-size': `${size}` })],
    [/^lh-?(?:-?(.+))?$/, ([, size]) => ({ 'line-height': `${size}` })],
    [/^ls-?(?:-?(.+))?$/, ([, size]) => ({ 'letter-spacing': `${size.replace(/\[|\]/g, '')}` })],
  ],
  shortcuts: {
    container: 'w-full max-w-[1440px] mx-auto px-16px',
  },
});
