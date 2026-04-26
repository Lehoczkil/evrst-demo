import { Box, Stack, Text, Title } from '@mantine/core';
import { Link } from 'react-router';
import { LuArrowLeft } from 'react-icons/lu';

import { SectionButton } from '@/components/section-button';
import { useTranslation, type TranslationKey } from '@/i18n';

interface Props {
  titleKey: TranslationKey;
  bodyKey: TranslationKey;
}

export function PlaceholderPage({ titleKey, bodyKey }: Props) {
  const { t } = useTranslation();

  return (
    <Box
      style={{ gridColumn: '1 / -1', minHeight: 'calc(100vh - 220px)' }}
      py='xl'
    >
      <Stack gap='lg' align='start'>
        <SectionButton
          component={Link}
          to='/'
          leftSection={<LuArrowLeft />}
        >
          {t('button.back')}
        </SectionButton>
        <Title order={1} tt='uppercase' fz='clamp(32px, 7vw, 56px)' lh='1.05'>
          {t(titleKey)}
        </Title>
        <Text c='primary' tt='uppercase' fz='14px' fw='600'>
          {t('placeholder.comingSoon')}
        </Text>
        <Text fz='16px' c='dimmed' lh='1.6' maw={640}>
          {t(bodyKey)}
        </Text>
      </Stack>
    </Box>
  );
}
