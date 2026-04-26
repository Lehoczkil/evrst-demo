import { colorsTuple, createTheme } from '@mantine/core';

const theme = createTheme({
  fontSmoothing: true,
  fontFamily: 'Space Grotesk',
  fontFamilyMonospace: 'IBM Plex Mono',
  headings: { fontFamily: 'Panchang-Bold' },
  scale: 1.2,
  defaultRadius: 0,
  primaryColor: 'primary',
  colors: { primary: colorsTuple('#f2ac3c') },
});

export default theme;
