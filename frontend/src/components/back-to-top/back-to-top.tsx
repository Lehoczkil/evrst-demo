import { ActionIcon } from '@mantine/core';
import { LuChevronUp } from 'react-icons/lu';

import { useTranslation } from '@/i18n';

export function BackToTop() {
  const { t } = useTranslation();

  return (
    <ActionIcon
      aria-label={t('button.backToTop')}
      size='lg'
      variant='outline'
      radius='xl'
      onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
      style={{
        position: 'fixed',
        right: '24px',
        bottom: '48px',
        zIndex: 100,
      }}
    >
      <LuChevronUp />
    </ActionIcon>
  );
}
