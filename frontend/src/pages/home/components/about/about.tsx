import * as runtime from 'react/jsx-runtime';
import { useQuery } from '@tanstack/react-query';
import { Card, SimpleGrid, Stack, Text, Title } from '@mantine/core';
import { motion } from 'motion/react';

import { api } from '@/api';
import type { ViewResource } from '@/types';
import { useTranslation, type TranslationKey } from '@/i18n';

import { Reveal } from '../reveal';

import {
  ABOUT_GOALS_COLLECTION_ID,
  ABOUT_PROJECTS_COLLECTION_ID,
  ABOUT_VIEW_RESOURCE_ID,
} from './constants';
import type { AboutItem, AboutItemResource } from './types';

interface CardItem {
  title: string;
  description: string;
}

const PROJECT_FALLBACK: { titleKey: TranslationKey; descKey: TranslationKey }[] = [
  { titleKey: 'about.project1.title', descKey: 'about.project1.description' },
  { titleKey: 'about.project2.title', descKey: 'about.project2.description' },
  { titleKey: 'about.project3.title', descKey: 'about.project3.description' },
];

const GOAL_FALLBACK: { titleKey: TranslationKey; descKey: TranslationKey }[] = [
  { titleKey: 'about.goal1.title', descKey: 'about.goal1.description' },
  { titleKey: 'about.goal2.title', descKey: 'about.goal2.description' },
  { titleKey: 'about.goal3.title', descKey: 'about.goal3.description' },
];

function Subtitle({ children }: { children: React.ReactNode }) {
  return (
    <Title order={3} fz='24px' fw='600' tt='uppercase' lh='1.2' c='primary'>
      {children}
    </Title>
  );
}

function CardGrid({ items }: { items: CardItem[] }) {
  return (
    <SimpleGrid cols={{ base: 1, sm: 2, md: 3 }} spacing='md'>
      {items.map((item, idx) => (
        <motion.div
          key={`${item.title}-${idx}`}
          initial={{ opacity: 0, y: 28, scale: 0.96 }}
          whileInView={{ opacity: 1, y: 0, scale: 1 }}
          viewport={{ once: true, amount: 0.2 }}
          transition={{
            duration: 0.5,
            delay: idx * 0.08,
            ease: [0.22, 1, 0.36, 1],
          }}
          whileHover={{ y: -4, transition: { duration: 0.2 } }}
        >
          <Card
            padding='lg'
            radius='md'
            bg='var(--card-bg)'
            style={{
              border: '1px solid var(--card-border-accent)',
              height: '100%',
            }}
          >
            <Stack gap='xs'>
              <Text fz='18px' fw='600'>
                {item.title}
              </Text>
              <Text fz='14px' c='dimmed' lh='1.5'>
                {item.description}
              </Text>
            </Stack>
          </Card>
        </motion.div>
      ))}
    </SimpleGrid>
  );
}

function useCollectionItems(collectionId: string) {
  return useQuery<AboutItemResource[]>({
    queryKey: ['about-collection', collectionId],
    queryFn: () =>
      api.request({ url: '/resource', query: { collectionId } }),
    enabled: Boolean(collectionId),
  });
}

function extractWhoWeAreText(content: string | null | undefined): string | null {
  if (!content) return null;
  // Strip MDX/JSX tags and pick the first non-empty paragraph as plain text.
  const cleaned = content
    .replace(/<[^>]+>/g, '')
    .replace(/import .*/g, '')
    .replace(/export .*/g, '')
    .trim();
  const firstParagraph = cleaned.split(/\n\s*\n/).find((p) => p.trim().length);
  return firstParagraph?.trim() ?? null;
}

export function About() {
  const { t } = useTranslation();

  const { data: viewData } = useQuery({
    queryKey: ['about-view', ABOUT_VIEW_RESOURCE_ID],
    queryFn: async () => {
      const view = await api.request<ViewResource>({
        url: `/resource/${ABOUT_VIEW_RESOURCE_ID}`,
      });
      if (!view) return null;
      const text = extractWhoWeAreText(view.payload.content);
      try {
        const { evaluate } = await import('@mdx-js/mdx');
        const { default: MDXContent } = await evaluate(
          view.payload.content,
          runtime,
        );
        return { text, MDXContent };
      } catch {
        return { text, MDXContent: null };
      }
    },
  });

  const { data: projects } = useCollectionItems(ABOUT_PROJECTS_COLLECTION_ID);
  const { data: goals } = useCollectionItems(ABOUT_GOALS_COLLECTION_ID);

  const projectItems: CardItem[] = projects?.length
    ? projects.map((r) => r.payload satisfies AboutItem)
    : PROJECT_FALLBACK.map(({ titleKey, descKey }) => ({
        title: t(titleKey),
        description: t(descKey),
      }));

  const goalItems: CardItem[] = goals?.length
    ? goals.map((r) => r.payload satisfies AboutItem)
    : GOAL_FALLBACK.map(({ titleKey, descKey }) => ({
        title: t(titleKey),
        description: t(descKey),
      }));

  const whoWeAreBody = viewData?.text ?? t('about.whoWeAreBody');

  return (
    <Stack gap='40px'>
      <Stack gap='16px'>
        <Reveal duration={0.3} y={8}>
          <Subtitle>{t('about.whoWeAre')}</Subtitle>
        </Reveal>
        <Reveal duration={0.35} delay={0.05} y={10}>
          <Text fz='16px' lh='1.6'>
            {whoWeAreBody}
          </Text>
        </Reveal>
      </Stack>

      <Stack gap='16px'>
        <Reveal duration={0.3} y={8}>
          <Subtitle>{t('about.projects')}</Subtitle>
        </Reveal>
        <CardGrid items={projectItems} />
      </Stack>

      <Stack gap='16px'>
        <Reveal duration={0.3} y={8}>
          <Subtitle>{t('about.goals')}</Subtitle>
        </Reveal>
        <CardGrid items={goalItems} />
      </Stack>
    </Stack>
  );
}
