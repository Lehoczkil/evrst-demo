import {
  Title,
  Text,
  Box,
  Flex,
  Paper,
  Stack,
  Group,
  ThemeIcon,
  Divider,
} from '@mantine/core';
import {
  LuArrowUpFromLine,
  LuDiameter,
  LuMoveVertical,
  LuWeight,
} from 'react-icons/lu';
import { motion } from 'motion/react';

import { Container } from '@/components/container';
import { useTranslation, type TranslationKey } from '@/i18n';

import classes from './hero.module.css';

const ROCKET_INFO: {
  labelKey: TranslationKey;
  value: string;
  icon: typeof LuMoveVertical;
}[] = [
  { labelKey: 'rocket.height', value: '95 cm', icon: LuMoveVertical },
  { labelKey: 'rocket.diameter', value: '7.65 cm', icon: LuDiameter },
  { labelKey: 'rocket.thrust', value: '700 N', icon: LuArrowUpFromLine },
  { labelKey: 'rocket.mass', value: '3.5 kg', icon: LuWeight },
];

const TITLE_LINES = [
  'Escape',
  'Velocity',
  'Rocketry',
  'Student',
  'Team',
] as const;

export function Hero() {
  const { t } = useTranslation();

  return (
    <Box w='100%' h='100vh' p={{ base: 'sm', sm: 'xl' }}>
      <Container h='100%'>
        <Flex
          h='100%'
          align={{ base: 'flex-start', md: 'center' }}
          justify='space-between'
          gap='xl'
          direction={{ base: 'column', md: 'row' }}
          pt={{ base: '80px', md: 0 }}
        >
          <Box
            className={classes.titleColumn}
          >
            <Title
              order={1}
              c='primary'
              tt='uppercase'
              lh='1.16'
              style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'start',
                fontSize: 'clamp(1.125rem, 5.5vw, 3rem)',
              }}
            >
              {TITLE_LINES.map((word, i) => (
                <motion.span
                  key={word}
                  initial={{ opacity: 0, y: 12 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{
                    duration: 0.4,
                    delay: i * 0.06,
                    ease: [0.22, 1, 0.36, 1],
                  }}
                  style={{ paddingRight: 'var(--mantine-spacing-md)' }}
                >
                  {word}
                </motion.span>
              ))}
            </Title>
          </Box>

          <Box
            style={{
              flexBasis: '40%',
              minWidth: 0,
              width: '100%',
              maxWidth: '420px',
            }}
            visibleFrom='md'
          >
            <Stack gap='sm'>
              {ROCKET_INFO.map((item, i) => {
                const Icon = item.icon;

                return (
                  <motion.div
                    key={item.labelKey}
                    initial={{ opacity: 0, x: 16 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{
                      duration: 0.4,
                      delay: 0.15 + i * 0.06,
                      ease: [0.22, 1, 0.36, 1],
                    }}
                  >
                    <Group align='center' gap='sm'>
                      <Divider style={{ flexGrow: 1 }} />
                      <Paper
                        w='160px'
                        py='sm'
                        bg='var(--card-bg)'
                        style={{ borderColor: 'var(--card-border)' }}
                        withBorder
                      >
                        <Flex justify='center'>
                          <Group gap='xs'>
                            <ThemeIcon variant='transparent'>
                              <Icon
                                color='var(--mantine-primary-color-filled)'
                                size='calc(var(--mantine-font-size-xl) * 1.25)'
                              />
                            </ThemeIcon>
                            <div>
                              <Text fw='500'>{item.value}</Text>
                              <Text c='dimmed' size='xs' tt='uppercase'>
                                {t(item.labelKey)}
                              </Text>
                            </div>
                          </Group>
                        </Flex>
                      </Paper>
                    </Group>
                  </motion.div>
                );
              })}
            </Stack>
          </Box>
        </Flex>
      </Container>
    </Box>
  );
}
