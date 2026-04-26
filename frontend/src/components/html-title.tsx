interface Props {
  children: string;
}

export function HtmlTitle(props: Props) {
  return (
    <title>{`${props.children} – Escape Velocity Rocketry Student Team`}</title>
  );
}
