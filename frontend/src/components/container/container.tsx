import { Box, type BoxProps, type ElementProps } from '@mantine/core';

import classes from './container.module.css';

interface ContainerProps extends BoxProps, ElementProps<'div', keyof BoxProps> {
  grid?: boolean;
}

export function Container({
  grid = false,
  className,
  children,
  ...rest
}: ContainerProps) {
  const classNames = [classes.root, grid && classes.grid, className]
    .filter(Boolean)
    .join(' ');

  return (
    <Box className={classNames} {...rest}>
      {children}
    </Box>
  );
}
