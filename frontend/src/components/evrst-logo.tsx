import { Anchor, Image } from '@mantine/core';
import { Link } from 'react-router';

interface Props {
  height?: number | string;
  className?: string;
  style?: React.CSSProperties;
}

export function EvrstLogo({ height, className, style }: Props) {
  return (
    <Anchor component={Link} to='/' style={{ display: 'inline-flex' }}>
      <Image
        h={height}
        src='/evrst_logo.svg'
        className={className}
        style={{
          transition: 'height 200ms ease, margin 200ms ease',
          ...style,
        }}
      />
    </Anchor>
  );
}
