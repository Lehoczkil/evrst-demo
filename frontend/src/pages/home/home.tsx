import { lazy, Suspense, useEffect, type ReactNode } from 'react';
import { Link, useLocation, useLoaderData } from 'react-router';
import { LuArrowRight, LuArrowUpRight } from 'react-icons/lu';
import { Box } from '@mantine/core';

import { BackToTop } from '@/components/back-to-top';
import { Container } from '@/components/container';
import { Footer } from '@/components/footer';
import { Header } from '@/components/header';
import { SectionButton } from '@/components/section-button';
import { useTranslation } from '@/i18n';

import {
  Hero,
  NoiseFilter,
  Section,
  About,
  Events,
  Mentors,
  Sponsors,
  TeamMembers,
  EvrstMarquee,
  Outro,
  DiagonalDivider,
} from './components';

const RocketScene = lazy(() => import('./components/rocket-scene'));

import { loader } from './loader';

interface SectionWrapProps {
  bg: 'dark' | 'gray';
  children: ReactNode;
  withGridOverlay?: boolean;
  containerless?: boolean;
}

function SectionWrap({
  bg,
  children,
  withGridOverlay,
  containerless,
}: SectionWrapProps) {
  const bgVar = bg === 'dark' ? 'var(--bg-dark)' : 'var(--bg-gray)';

  return (
    <Box
      bg={bgVar}
      pos='relative'
      style={{
        paddingBlock: '40px',
        overflow: withGridOverlay ? 'visible' : 'hidden',
      }}
    >
      {withGridOverlay && (
        <Box
          pos='absolute'
          style={{
            left: 0,
            right: 0,
            top: '50%',
            height: 'calc(50% + 80px)',
            backgroundImage: "url('/grid.svg')",
            backgroundRepeat: 'no-repeat',
            backgroundSize: 'cover',
            backgroundPosition: 'center',
            pointerEvents: 'none',
            opacity: 0.4,
            zIndex: 0,
          }}
        />
      )}
      {containerless ? (
        <Box pos='relative' style={{ zIndex: 1 }}>
          {children}
        </Box>
      ) : (
        <Container pos='relative' style={{ zIndex: 1 }}>
          {children}
        </Container>
      )}
    </Box>
  );
}

function Home() {
  const page = useLoaderData<typeof loader>();
  const { t } = useTranslation();
  const { hash } = useLocation();

  const rocketObject = page.objects.find((o) => o.key === 'rocket');
  const rocketScale = page.payload.data?.rocket?.scale;
  const rocketPosition = page.payload.data?.rocket?.position;

  useEffect(() => {
    if (!hash) return;
    const id = hash.slice(1);
    let cancelled = false;
    const tryScroll = (attempt = 0) => {
      if (cancelled) return;
      const target = document.getElementById(id);
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
      }
      if (attempt < 20) {
        window.setTimeout(() => tryScroll(attempt + 1), 60);
      }
    };
    tryScroll();
    return () => {
      cancelled = true;
    };
  }, [hash]);

  return (
    <>
      <NoiseFilter />

      <Header />

      <Hero />

      <Suspense fallback={null}>
        <RocketScene
          rocketUrl={rocketObject?.url}
          scale={rocketScale}
          position={rocketPosition}
        />
      </Suspense>

      <Box h={{ base: '32px', sm: '93px' }} />

      <SectionWrap bg='dark'>
        <Section
          id='events'
          title={t('section.events')}
          index={0}
          right={
            <SectionButton
              rightSection={<LuArrowRight />}
              component={Link}
              to='/events'
            >
              {t('button.viewMore')}
            </SectionButton>
          }
        >
          <Events />
        </Section>
      </SectionWrap>

      <DiagonalDivider topColor='var(--bg-dark)' bottomColor='var(--bg-gray)' />

      <SectionWrap bg='gray'>
        <Section
          id='about'
          title={t('section.about')}
          index={1}
          right={
            <SectionButton
              rightSection={<LuArrowRight />}
              component={Link}
              to='/about'
            >
              {t('button.viewMore')}
            </SectionButton>
          }
        >
          <About />
        </Section>
      </SectionWrap>

      <DiagonalDivider topColor='var(--bg-gray)' bottomColor='var(--bg-dark)' />

      <SectionWrap bg='dark'>
        <Section
          id='team'
          title={t('section.team')}
          index={2}
          right={
            <SectionButton
              rightSection={<LuArrowRight />}
              component={Link}
              to='/team'
            >
              {t('button.viewMore')}
            </SectionButton>
          }
        >
          <TeamMembers />
        </Section>
      </SectionWrap>

      <SectionWrap bg='dark' withGridOverlay>
        <Section id='mentors' title={t('section.mentors')} index={3}>
          <Mentors />
        </Section>
      </SectionWrap>

      <DiagonalDivider topColor='var(--bg-dark)' bottomColor='var(--bg-gray)' />

      <SectionWrap bg='gray'>
        <Section
          id='sponsors'
          title={t('section.sponsors')}
          index={4}
          right={
            <SectionButton
              rightSection={<LuArrowUpRight />}
              component='a'
              href='mailto:evrstrocket@gmail.com?subject=Sponsorship Inquiry'
            >
              {t('button.becomeSponsor')}
            </SectionButton>
          }
        >
          <Sponsors />
        </Section>
      </SectionWrap>

      <EvrstMarquee />

      <Outro />

      <Footer />

      <BackToTop />
    </>
  );
}

export default Home;
