interface Props {
  topColor: string;
  bottomColor: string;
  height?: number;
  /** Vertical drop in pixels of the diagonal across the full width. */
  drop?: number;
  strokeWidth?: number;
}

export function DiagonalDivider({
  topColor,
  bottomColor,
  height = 80,
  drop = 32,
  strokeWidth = 5,
}: Props) {
  const orange = 'var(--mantine-primary-color-filled)';

  const w = 100;
  const half = height / 2;
  const dropHalf = drop / 2;

  // Top polygon: from (0,0) to (w,0) down to (w, half-dropHalf), back to (0, half+dropHalf)
  const topPoints = `0,0 ${w},0 ${w},${half - dropHalf} 0,${half + dropHalf}`;
  const bottomPoints = `0,${half + dropHalf} ${w},${half - dropHalf} ${w},${height} 0,${height}`;

  return (
    <svg
      aria-hidden
      width='100%'
      height={height}
      viewBox={`0 0 ${w} ${height}`}
      preserveAspectRatio='none'
      style={{ display: 'block' }}
    >
      <polygon points={topPoints} fill={topColor} />
      <polygon points={bottomPoints} fill={bottomColor} />
      <line
        x1={0}
        y1={half + dropHalf}
        x2={w}
        y2={half - dropHalf}
        stroke={orange}
        strokeWidth={strokeWidth}
        vectorEffect='non-scaling-stroke'
      />
    </svg>
  );
}
