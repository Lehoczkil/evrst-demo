import type { ReactNode } from "react";
import { Flex, Group, Stack, Title, Box } from "@mantine/core";
import { motion } from "motion/react";

import { Reveal } from "./reveal";

interface Props {
  id: string;
  index: number;
  title: string;
  right?: ReactNode;
  children: ReactNode;
}

export function Section(props: Props) {
  const number = `0${props.index + 1}`.slice(-2);

  return (
    <Box component="section" id={props.id}>
      <Stack gap="32px">
        <Flex
          justify="space-between"
          align={{ base: "flex-start", sm: "center" }}
          direction={{ base: "column", sm: "row" }}
          gap="md"
        >
          <Group align="end" gap="xs">
            <motion.span
              initial={{ opacity: 0, x: -32 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true, amount: 0.1 }}
              transition={{
                duration: 0.6,
                ease: [0.22, 1, 0.36, 1],
              }}
              style={{
                fontSize: "clamp(28px, 6vw, 40px)",
                lineHeight: 1,
                textTransform: "uppercase",
                fontWeight: 500,
                userSelect: "none",
                pointerEvents: "none",
                background:
                  "linear-gradient(to right, transparent, var(--mantine-primary-color-filled))",
                color: "transparent",
                backgroundClip: "text",
                WebkitBackgroundClip: "text",
                display: "inline-block",
              }}
            >
              {number}
            </motion.span>
            <motion.div
              initial={{ opacity: 0, y: 16 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, amount: 0.1 }}
              transition={{
                duration: 0.55,
                delay: 0.1,
                ease: [0.22, 1, 0.36, 1],
              }}
            >
              <Title
                order={2}
                fz="clamp(28px, 6vw, 40px)"
                tt="uppercase"
                lh="1"
              >
                {props.title}
              </Title>
            </motion.div>
          </Group>
          {props.right && (
            <Reveal duration={0.4} delay={0.2} y={0}>
              <Box>{props.right}</Box>
            </Reveal>
          )}
        </Flex>

        <Reveal duration={0.5} delay={0.15} y={20}>
          {props.children}
        </Reveal>
      </Stack>
    </Box>
  );
}
