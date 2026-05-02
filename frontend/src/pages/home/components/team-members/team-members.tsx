import {
  Avatar,
  Box,
  Card,
  Flex,
  Group,
  Stack,
  Text,
} from '@mantine/core';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router';
import {
  fetchTeamGroups,
  fetchTeamMembers,
  type TeamGroup,
  type TeamMember,
} from '@/api';
import { useTranslation } from '@/i18n';

import { motion } from 'motion/react';

import { SectionButton } from '@/components/section-button';

import { Reveal } from '../reveal';

import classes from './team-members.module.css';

interface MemberCardProps {
  member: TeamMember;
}

function MemberCard({ member }: MemberCardProps) {
  const primary = member.groups.find((g) => g.is_primary);
  return (
    <Card
      padding='md'
      radius='md'
      bg='var(--card-bg)'
      withBorder
      w={{ base: '100%', xs: '200px' }}
      miw={{ base: '100%', xs: '200px' }}
      h={{ base: 'auto', xs: '220px' }}
      mih={{ base: '180px', xs: '220px' }}
      style={{ borderColor: 'var(--card-border)' }}
    >
      <Stack align='center' gap='8px' justify='flex-start' h='100%'>
        <Avatar
          src={member.photo_url ?? undefined}
          name={member.name}
          size={80}
          radius='50%'
          color='primary'
        />
        <Text fz='14px' fw='600' ta='center' lh='1.2' lineClamp={2}>
          {member.name}
        </Text>
        <Text fz='12px' c='dimmed' ta='center' lh='1.2' lineClamp={2}>
          {member.degree || primary?.name}
        </Text>
      </Stack>
    </Card>
  );
}

export function TeamMembers() {
  const { t, language } = useTranslation();

  const { data: members } = useQuery<TeamMember[]>({
    queryKey: ['team', 'members', language],
    queryFn: () => fetchTeamMembers(language),
  });

  const { data: groups } = useQuery<TeamGroup[]>({
    queryKey: ['team', 'groups', language],
    queryFn: () => fetchTeamGroups(language),
  });

  const groupedMembers = (members ?? []).reduce<Map<string, TeamMember[]>>(
    (acc, member) => {
      const slug = member.groups.find((g) => g.is_primary)?.slug ?? 'ungrouped';
      const bucket = acc.get(slug) ?? [];
      bucket.push(member);
      acc.set(slug, bucket);
      return acc;
    },
    new Map(),
  );

  const orderedGroups: { slug: string; name: string; members: TeamMember[] }[] =
    [];

  for (const group of groups ?? []) {
    const bucket = groupedMembers.get(group.slug);
    if (bucket && bucket.length > 0) {
      orderedGroups.push({
        slug: group.slug,
        name: group.name,
        members: bucket,
      });
      groupedMembers.delete(group.slug);
    }
  }

  for (const [slug, bucket] of groupedMembers) {
    orderedGroups.push({ slug, name: '', members: bucket });
  }

  return (
    <Stack gap='40px' align='center'>
      <Stack gap='32px' w='100%' align='center'>
        {orderedGroups.map((group) => (
          <Box key={group.slug} style={{ width: '100%' }}>
            <Stack gap='12px' align='center'>
              <Text
                fz='14px'
                tt='uppercase'
                c='dimmed'
                fw='500'
                style={{ letterSpacing: '0.08em' }}
              >
                {group.name}
              </Text>
              <Flex
                wrap='wrap'
                justify='center'
                gap='md'
                style={{ width: '100%' }}
              >
                {group.members.map((member, mIdx) => (
                  <motion.div
                    key={member.id}
                    className={classes.cardWrap}
                    initial={{ opacity: 0, y: 24, scale: 0.92 }}
                    whileInView={{ opacity: 1, y: 0, scale: 1 }}
                    viewport={{ once: true, amount: 0.2 }}
                    transition={{
                      duration: 0.45,
                      delay: mIdx * 0.05,
                      ease: [0.22, 1, 0.36, 1],
                    }}
                    whileHover={{
                      y: -6,
                      transition: { duration: 0.2 },
                    }}
                  >
                    <MemberCard member={member} />
                  </motion.div>
                ))}
              </Flex>
            </Stack>
          </Box>
        ))}
      </Stack>

      <Reveal duration={0.3} y={8}>
        <Group justify='center'>
          <SectionButton component={Link} to='/join-us'>
            {t('team.joinUs')}
          </SectionButton>
        </Group>
      </Reveal>
    </Stack>
  );
}
