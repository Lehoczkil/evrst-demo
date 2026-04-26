import { Marquee, Text } from "@mantine/core";

export function EvrstMarquee() {
  return (
    <Marquee
      bg="primary"
      fadeEdges={false}
      c="var(--mantine-color-body)"
      styles={{
        root: { height: "32px", userSelect: "none", pointerEvents: "none" },
      }}
    >
      {"Escape Velocity Rocketry Student Team ×".split(" ").map((item, i) => (
        <Text key={i} tt="uppercase" ff="heading">
          {item}
        </Text>
      ))}
    </Marquee>
  );
}
