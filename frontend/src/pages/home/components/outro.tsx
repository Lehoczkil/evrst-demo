import {
  Text,
  Flex,
  Group,
  Avatar,
  Box,
  Stack,
  Anchor,
} from '@mantine/core';

import { Container } from '@/components/container';
import { MENU_ITEMS } from '@/constants';
import { useTranslation, type TranslationKey } from '@/i18n';

import { ObudaLogo } from './obuda-logo';
import classes from './outro.module.css';
import { Link } from 'react-router';

type Link = { label: string; url: string; target?: '_blank' };
type TranslatedLink = { labelKey: TranslationKey; url: string; target?: '_blank' };

interface MenuGroup {
  titleKey: TranslationKey;
  links: (Link | TranslatedLink)[];
}

const menu: MenuGroup[] = [
  {
    titleKey: 'outro.contact',
    links: [
      { label: 'evrstrocket@gmail.com', url: 'mailto:evrstrocket@gmail.com' },
    ],
  },
  {
    titleKey: 'outro.follow',
    links: [
      {
        label: 'Instagram',
        url: 'https://instagram.com/evrst_rocketry',
        target: '_blank',
      },
      {
        label: 'Facebook',
        url: 'https://facebook.com/profile.php?id=61565957147301',
        target: '_blank',
      },
      {
        label: 'Youtube',
        url: 'https://youtube.com/watch?v=dQw4w9WgXcQ',
        target: '_blank',
      },
    ],
  },
  {
    titleKey: 'outro.navigation',
    links: MENU_ITEMS,
  },
];

export function Outro() {
  const { t } = useTranslation();
  const groups = menu.map((group) => {
    return (
      <Box key={group.titleKey}>
        <Stack gap='6px'>
          <Text className={classes.title} fz='12px' fw='600' tt='uppercase'>
            {t(group.titleKey)}
          </Text>
          <Stack gap='2px'>
            {group.links.map((link, index) => (
              <Anchor
                key={index}
                to={link.url}
                component={Link}
                className={classes.link}
                fz='12px'
                target={'target' in link ? link.target : undefined}
              >
                {'labelKey' in link ? t(link.labelKey) : link.label}
              </Anchor>
            ))}
          </Stack>
        </Stack>
      </Box>
    );
  });

  return (
    <section>
      <Container className={classes.inner}>
        <Flex
          justify='space-between'
          align={{ base: 'flex-start', sm: 'start' }}
          direction={{ base: 'column', sm: 'row' }}
          gap='xl'
          wrap='wrap'
        >
          <Group align='center' gap='md'>
            <Avatar radius='none' size='md'>
              <ObudaLogo />
            </Avatar>
            <div>
              <Text c='dimmed' className={classes.description} fz='11px'>
                {t('outro.poweredBy')}
              </Text>
              <Text c='white' fz='14px' fw='500' lh='1.2'>
                Obuda University
              </Text>
            </div>
          </Group>
          <Box className={classes.menuGroups}>{groups}</Box>
        </Flex>
      </Container>
    </section>
  );
}
