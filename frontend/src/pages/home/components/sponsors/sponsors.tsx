import { Avatar, Box, Group, Marquee, Paper, Text } from '@mantine/core';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/api';

import type { SponsorResource } from './types';
import { SPONSOR_COLLECTION_ID } from './constants';

export function Sponsors() {
  const { data: sponsors } = useQuery<SponsorResource[]>({
    queryKey: ['sponsors'],
    queryFn: () => {
      return api.request({
        url: '/resource',
        query: { collectionId: SPONSOR_COLLECTION_ID },
      });
    },
  });

  if (!sponsors?.length) return null;

  return (
    <Box
      style={{
        width: '100vw',
        marginLeft: 'calc(50% - 50vw)',
      }}
    >
      <Marquee
        fadeEdges
        fadeEdgeColor='var(--bg-gray)'
        gap='md'
        duration={40000}
        pauseOnHover
        styles={{ root: { padding: '8px 0' } }}
      >
        {sponsors.map((sponsor) => (
          <Paper
            key={sponsor.id}
            py='8px'
            px='md'
            bg='var(--card-bg)'
            withBorder
            style={{
              width: '300px',
              height: '64px',
              borderColor: 'var(--card-border)',
              position: 'relative',
            }}
          >
            <Group gap='12px' wrap='nowrap' h='100%' align='center'>
              <Avatar
                radius='sm'
                size='md'
                name={sponsor.payload.name}
                src={sponsor.payload.logo}
                styles={{
                  image: { objectFit: 'contain' },
                }}
              />
              <div style={{ minWidth: 0, flex: 1 }}>
                <Text size='sm' fw='600' truncate lh='1.2'>
                  {sponsor.payload.name}
                </Text>
                {sponsor.payload.year && (
                  <Text size='xs' c='primary' lh='1.2'>
                    {sponsor.payload.year}
                  </Text>
                )}
              </div>
            </Group>
          </Paper>
        ))}
      </Marquee>
    </Box>
  );
}
