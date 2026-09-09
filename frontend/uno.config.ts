import transformerVariantGroup from '@unocss/transformer-variant-group';
import { defineConfig, presetWind3 } from 'unocss';

export default defineConfig({
  presets: [presetWind3()],
  transformers: [transformerVariantGroup()],
  theme: {
    // Match the SCSS map in src/styles/_breakpoints.scss. Note that map
    // starts at `xs: 0` — never pass `xs` to media-down(), Sass rejects
    // calc(0 - 1px).
    breakpoints: {
      sm: '576px',
      md: '768px',
      lg: '992px',
      xl: '1200px',
      xxl: '1366px',
      xxxl: '1500px',
    },
    // Every colour points at a token in _tokens.scss. Utilities and SCSS
    // therefore cannot drift apart: there is one definition of gold.
    colors: {
      ink: {
        0: 'var(--ink-0)',
        1: 'var(--ink-1)',
        2: 'var(--ink-2)',
      },
      gold: {
        400: 'var(--gold-400)',
        500: 'var(--gold-500)',
        600: 'var(--gold-600)',
        dim: 'var(--gold-dim)',
      },
      paper: {
        DEFAULT: 'var(--paper)',
        2: 'var(--paper-2)',
        line: 'var(--paper-line)',
      },
      line: {
        DEFAULT: 'var(--line)',
        hi: 'var(--line-hi)',
      },
      txt: {
        hi: 'var(--text-hi)',
        mid: 'var(--text-mid)',
        low: 'var(--text-low)',
        paper: 'var(--on-paper)',
        'paper-mid': 'var(--on-paper-mid)',
      },
      live: 'var(--live)',
      warn: 'var(--warn)',
      error: 'var(--error)',
    },
    fontFamily: {
      display: 'var(--font-display)',
      body: 'var(--font-body)',
      mono: 'var(--font-mono)',
    },
  },
  rules: [
    [/^fs-?(?:-?(.+))?$/, ([, size]) => ({ 'font-size': `${size}` })],
    [/^lh-?(?:-?(.+))?$/, ([, size]) => ({ 'line-height': `${size}` })],
    [/^ls-?(?:-?(.+))?$/, ([, size]) => ({ 'letter-spacing': `${size.replace(/\[|\]/g, '')}` })],
  ],
  shortcuts: {
    container: 'w-full max-w-[var(--container)] mx-auto px-[clamp(16px,4vw,56px)]',
    // The hairline that does the structural work in place of borders and
    // shadows. `rule-paper` is its light-surface counterpart.
    rule: 'h-1px w-full bg-line',
    'rule-paper': 'h-1px w-full bg-paper-line',
  },
});
