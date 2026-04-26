import { Box, Stack, Title, Space } from '@mantine/core';
import { Link, useLoaderData } from 'react-router';
import { use } from 'react';
import { ErrorBoundary } from 'react-error-boundary';
import { LuArrowLeft } from 'react-icons/lu';

import type { loader } from './loader';
import { HtmlTitle } from '@/components/html-title';
import { SectionButton } from '@/components/section-button';
import { useTranslation } from '@/i18n';

export function DynamicPage() {
  const { t } = useTranslation();
  const { pageLoaderData } = useLoaderData<typeof loader>();

  const { page, queryData, MDXContent } = use(pageLoaderData);

  return (
    <ErrorBoundary fallback={<div>Something went wrong</div>}>
      <HtmlTitle>{page.payload.title}</HtmlTitle>

      <Stack gap='4rem'>
        <Stack component='header' gap='md' align='center'>
          <Title
            order={2}
            fz='clamp(2rem, 7vw, 3.75rem)'
            ff='text'
            ta='center'
          >
            {page.payload.title}
          </Title>
          <SectionButton
            component={Link}
            to='/'
            leftSection={<LuArrowLeft />}
          >
            {t('button.home')}
          </SectionButton>
        </Stack>
        <Box>{MDXContent({ page, queryData })}</Box>
      </Stack>
      <Space h='4rem' />
    </ErrorBoundary>
  );
}
