import { Suspense } from 'react';
import { Outlet } from 'react-router';
import { Center, Flex, Loader } from '@mantine/core';

import { BackToTop } from '@/components/back-to-top';
import { Container } from '@/components/container';
import { Footer } from '@/components/footer';
import { Header } from '@/components/header';

export function Layout() {
  return (
    <Suspense
      fallback={
        <Center w='100vw' h='100vh'>
          <Loader type='dots' />
        </Center>
      }
    >
      <Flex className='layout' direction='column' style={{ height: '100%' }}>
        <Header />

        <main style={{ flexGrow: 1, paddingTop: '96px' }}>
          <Container grid>
            <Outlet />
          </Container>
        </main>

        <Footer />
      </Flex>

      <BackToTop />
    </Suspense>
  );
}
