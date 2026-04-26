import {
  AspectRatio,
  Card,
  Container,
  Image,
  SimpleGrid,
  Stack,
  Text,
} from '@mantine/core';
import { modals } from '@mantine/modals';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/api';

import { eventsSelector } from './utils';
import type { EventResource } from './types';
import classes from './events.module.css';

const EVENT_COLLECTION_ID = `36b42185-3a49-43ee-ba79-5cc73075b0d2`;

export function Events() {
  const { data: events } = useQuery({
    queryKey: ['events'],
    queryFn: () => {
      // past=true → only events that have already happened. Future ones
      // live on the admin calendar.
      return api.request<EventResource[]>({
        url: '/resource',
        query: {
          collectionId: EVENT_COLLECTION_ID,
          include: 'objects',
          past: true,
        },
      });
    },
    select: eventsSelector,
  });

  const formatRange = (event: EventResource) => {
    const { start_at, end_at, date } = event.payload;
    if (start_at) {
      const start = new Date(start_at);
      const startLabel = start.toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
      });
      if (end_at) {
        const end = new Date(end_at);
        const sameDay = start.toDateString() === end.toDateString();
        const endLabel = end.toLocaleString(undefined, {
          dateStyle: sameDay ? undefined : 'medium',
          timeStyle: 'short',
        });
        return `${startLabel} — ${endLabel}`;
      }
      return startLabel;
    }
    return date ?? '';
  };

  const cards = events?.map((event) => {
    const imageUrl = event.payload.image ?? event.objects?.at(0)?.url;

    const image = (
      <AspectRatio ratio={16 / 9}>
        <Image src={imageUrl} alt={event.payload.title} />
      </AspectRatio>
    );

    const handleClick = () => {
      modals.open({
        size: 'calc(var(--modal-size-xl) * 1.25)',
        title: event.payload.title,
        overlayProps: {
          backgroundOpacity: 0.5,
          blur: 4,
        },
        children: image,
      });
    };

    return (
      <Card
        key={event.id}
        p='md'
        className={classes.card}
        onClick={handleClick}
      >
        <Stack gap='xs'>
          {image}
          <div>
            <Text className={classes.title}>{event.payload.title}</Text>
            <Text className={classes.date} c='dimmed' size='xs'>
              {formatRange(event)}
            </Text>
          </div>
        </Stack>
      </Card>
    );
  });

  return (
    <Container py='xl' size='xl' px={0}>
      <SimpleGrid cols={{ base: 1, sm: 2, md: 3 }} spacing={{ base: 'md', sm: 'xl' }}>
        {cards}
      </SimpleGrid>
    </Container>
  );
}
