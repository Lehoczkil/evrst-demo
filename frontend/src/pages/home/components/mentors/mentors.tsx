import { useQuery } from '@tanstack/react-query';
import { Anchor, Avatar, Card, Flex, Stack, Text } from '@mantine/core';
import { motion } from 'motion/react';

import { api } from '@/api';

import { MENTOR_COLLECTION_ID } from './constants';
import type { MentorResource } from './types';
import classes from './mentors.module.css';

export function Mentors() {
  const { data: mentors } = useQuery<MentorResource[]>({
    queryKey: ['mentors'],
    queryFn: () => {
      return api.request({
        url: '/resource',
        query: { collectionId: MENTOR_COLLECTION_ID },
      });
    },
  });

  return (
    <Flex gap='md' justify='center' wrap='wrap'>
      {mentors?.map((mentor, idx) => {
        const photo =
          mentor.payload.photo ??
          mentor.objects?.find((o) => o.key === 'photo')?.url;
        return (
          <motion.div
            key={mentor.id}
            className={classes.cardWrap}
            initial={{ opacity: 0, y: 24, scale: 0.94 }}
            whileInView={{ opacity: 1, y: 0, scale: 1 }}
            viewport={{ once: true, amount: 0.2 }}
            transition={{
              duration: 0.45,
              delay: idx * 0.07,
              ease: [0.22, 1, 0.36, 1],
            }}
            whileHover={{ y: -6, transition: { duration: 0.2 } }}
          >
            <Card
              padding='lg'
              radius='md'
              bg='var(--card-bg)'
              w={{ base: '100%', xs: '280px' }}
              miw={{ base: '100%', xs: '280px' }}
              h={{ base: 'auto', xs: '260px' }}
              mih={{ base: '180px', xs: '260px' }}
              style={{ border: '1px solid var(--card-border)' }}
            >
              <Stack align='center' gap='sm' justify='center' h='100%'>
                <Avatar
                  src={photo}
                  name={mentor.payload.name}
                  size={72}
                  radius='50%'
                  color='primary'
                />
                <Stack gap={2} align='center'>
                  <Text fz={{ base: '14px', sm: '15px' }} fw='600' ta='center' lh='1.2' lineClamp={2}>
                    {mentor.payload.name}
                  </Text>
                  <Anchor
                    href={`mailto:${mentor.payload.email}`}
                    fz='12px'
                    c='dimmed'
                    ta='center'
                    style={{ wordBreak: 'break-all' }}
                  >
                    {mentor.payload.email}
                  </Anchor>
                </Stack>
              </Stack>
            </Card>
          </motion.div>
        );
      })}
    </Flex>
  );
}
