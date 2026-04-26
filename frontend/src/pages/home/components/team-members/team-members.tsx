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
import { api } from '@/api';
import { useTranslation } from '@/i18n';

import { motion } from 'motion/react';

import { SectionButton } from '@/components/section-button';

import { Reveal } from '../reveal';

import type { TeamMemberResource } from './types';
import { TEAM_MEMBERS_COLLECTION_ID } from './constants';
import classes from './team-members.module.css';

interface MemberCardProps {
  member: TeamMemberResource;
}

function MemberCard({ member }: MemberCardProps) {
  const photo =
    member.payload.photo ??
    member.objects?.find((o) => o.key === 'photo')?.url;
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
          src={photo}
          name={member.payload.name}
          size={80}
          radius='50%'
          color='primary'
        />
        <Text fz='14px' fw='600' ta='center' lh='1.2' lineClamp={2}>
          {member.payload.name}
        </Text>
        <Text fz='12px' c='dimmed' ta='center' lh='1.2' lineClamp={2}>
          {member.payload.degree || member.payload.main_position?.payload?.name}
        </Text>
      </Stack>
    </Card>
  );
}

export function TeamMembers() {
  const { t } = useTranslation();
  const { data: members } = useQuery<TeamMemberResource[]>({
    queryKey: ['team'],
    queryFn: () => {
      return api.request({
        url: '/resource',
        query: { collectionId: TEAM_MEMBERS_COLLECTION_ID },
      });
    },
  });

  const grouped = (members ?? []).reduce<
    Record<string, { name: string; members: TeamMemberResource[] }>
  >((acc, member) => {
    const groupId = member.payload.main_position?.id ?? 'ungrouped';
    const groupName = member.payload.main_position?.payload?.name ?? '';
    if (!acc[groupId]) acc[groupId] = { name: groupName, members: [] };
    acc[groupId].members.push(member);
    return acc;
  }, {});

  const groups = Object.values(grouped);

  return (
    <Stack gap='40px' align='center'>
      <Stack gap='32px' w='100%' align='center'>
        {groups.map((group, idx) => (
          <Box key={`${group.name}-${idx}`} style={{ width: '100%' }}>
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
