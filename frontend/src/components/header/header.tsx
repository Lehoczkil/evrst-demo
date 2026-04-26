import { useEffect, useState } from 'react';
import { Link } from 'react-router';
import {
  Anchor,
  Box,
  Burger,
  Drawer,
  Flex,
  Group,
  SegmentedControl,
  Stack,
} from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';

import { MENU_ITEMS } from '@/constants';
import { Container } from '@/components/container';
import { EvrstLogo } from '@/components/evrst-logo';
import { useTranslation, type Language } from '@/i18n';

import classes from './header.module.css';

interface HeaderProps {
  withNavigation?: boolean;
}

export function Header(props: HeaderProps) {
  const { withNavigation = true } = props;
  const { language, setLanguage, t } = useTranslation();
  const [scrolled, setScrolled] = useState(false);
  const [drawerOpen, { open: openDrawer, close: closeDrawer }] =
    useDisclosure(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  const className = [classes.root, scrolled && classes.scrolled]
    .filter(Boolean)
    .join(' ');

  const languageSwitch = (
    <SegmentedControl
      value={language}
      onChange={(value) => setLanguage(value as Language)}
      data={[
        { value: 'en', label: 'EN' },
        { value: 'hu', label: 'HU' },
      ]}
      styles={{
        root: { height: '32px', minHeight: '32px' },
        label: {
          paddingTop: 0,
          paddingBottom: 0,
          height: '28px',
          display: 'flex',
          alignItems: 'center',
          fontSize: '12px',
        },
        indicator: { height: '28px' },
      }}
    />
  );

  return (
    <header className={className}>
      <Container className={classes.inner}>
        <Flex justify='space-between' align='center' gap='md'>
          <Box
            className={`${classes.column} ${classes.logoColumn}`}
            style={{ justifyContent: 'flex-start' }}
          >
            <span className={classes.logoWrap}>
              <EvrstLogo />
            </span>
          </Box>

          <Box
            className={classes.column}
            style={{ justifyContent: 'center' }}
            visibleFrom='md'
          >
            {withNavigation && (
              <Group component='nav' tt='uppercase' wrap='nowrap' gap='xl'>
                {MENU_ITEMS.map((item) => (
                  <Anchor
                    key={item.labelKey}
                    component={Link}
                    to={item.url}
                    underline='hover'
                    c='white'
                    className={classes.navLink}
                  >
                    {t(item.labelKey)}
                  </Anchor>
                ))}
              </Group>
            )}
          </Box>

          <Box
            className={classes.column}
            style={{ justifyContent: 'flex-end', gap: '12px' }}
          >
            <Box visibleFrom='md'>{languageSwitch}</Box>
            {withNavigation && (
              <Burger
                opened={drawerOpen}
                onClick={openDrawer}
                hiddenFrom='md'
                size='sm'
                color='white'
                aria-label='Toggle navigation'
              />
            )}
          </Box>
        </Flex>
      </Container>

      <Drawer
        opened={drawerOpen}
        onClose={closeDrawer}
        position='right'
        size='75%'
        hiddenFrom='md'
        withCloseButton
        title={null}
        styles={{
          content: { backgroundColor: 'var(--bg-dark)' },
          header: { backgroundColor: 'var(--bg-dark)' },
        }}
      >
        <Stack gap='lg'>
          <Group component='nav' tt='uppercase' gap='md'>
            <Stack gap='sm' style={{ width: '100%' }}>
              {MENU_ITEMS.map((item) => (
                <Anchor
                  key={item.labelKey}
                  component={Link}
                  to={item.url}
                  onClick={closeDrawer}
                  underline='never'
                  c='white'
                  fz='18px'
                  fw='500'
                >
                  {t(item.labelKey)}
                </Anchor>
              ))}
            </Stack>
          </Group>
          {languageSwitch}
        </Stack>
      </Drawer>
    </header>
  );
}
