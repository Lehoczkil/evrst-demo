<script setup lang="ts">
import { motion } from 'motion-v';
import { format } from 'date-fns';
import { hu, enGB } from 'date-fns/locale';
import { EventRequests, soonest, startOf } from '@/services/requests/EventRequests';
import type { EventResource } from '@/services/requests/CmsRequests';
import { cardStagger } from './anims';

/*
  Split by tense, because the API splits them that way.

  `?past=true` is a different query on the backend — an indexed comparison
  over the promoted end_at / start_at columns — so the two halves get two
  requests and, more to the point, two FORMS. A reader scanning for "what
  is next" and a reader scanning for "what have they actually done" want
  different objects:

    upcoming → a bordered day/month block in gold display type, the title,
               one line of description, the venue
    past     → a condensed log, one hairline row each, title left and date
               right, both dimmed

  Same data, two jobs.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t, locale } = useI18n();

const { data: all, status, fetch: refetch } = useQuery<EventResource[]>({
  key: ['events', locale],
  request: () => EventRequests.all(locale.value as string),
  cache: true,
  staleTime: 120,
});

const { data: pastRows } = useQuery<EventResource[]>({
  key: ['events-past', locale],
  request: () => EventRequests.past(locale.value as string),
  cache: true,
  staleTime: 300,
});
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/** date-fns needs the locale object, not the tag. */
const dateLocale = () => (locale.value === 'hu' ? hu : enGB);

const dayOf = (event: EventResource) => {
  const at = startOf(event);

  return at ? format(at, 'dd') : '';
};

/*
  A three-letter month, uppercased. date-fns' `LLL` is the standalone form
  — Hungarian inflects the month name after a day number, and the
  standalone is what belongs in a date block on its own.
*/
const monthOf = (event: EventResource) => {
  const at = startOf(event);

  return at ? format(at, 'LLL', { locale: dateLocale() }).replace('.', '').toUpperCase() : '';
};

const fullDate = (event: EventResource) => {
  const at = startOf(event);
  if (at) {
    return format(at, 'P', { locale: dateLocale() });
  }

  // Legacy rows carry only a free-text payload.date, which is not
  // datetime-comparable and cannot be reformatted. Passed through as-is.
  return event.payload.date ?? '';
};
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const upcoming = computed(() => {
  const now = Date.now();

  return (all.value ?? [])
    .map((event) => ({ event, at: startOf(event) }))
    .filter((row) => !!row.at && row.at.getTime() > now)
    .sort((a, b) => (a.at as Date).getTime() - (b.at as Date).getTime())
    .map((row) => row.event);
});

const past = computed(() => (pastRows.value ?? []).slice(0, 6));

const next = computed(() => soonest(all.value));

const eyebrow = computed(() => (next.value
  ? `${t('events.next')}: ${fullDate(next.value)}`
  : t('events.eyebrowNone')));

const isLoading = computed(() => status.value === 'PENDING' && !all.value?.length);
const hasNothing = computed(() => !upcoming.value.length && !past.value.length && !isLoading.value);
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <SectionShell id="events" :eyebrow="eyebrow" :title="t('events.title')">
    <FetchError v-if="status === 'FAILED' && !all?.length" @retry="refetch()" />
    <Skeleton v-else-if="isLoading" :rows="3" height="110px" />
    <EmptyNote v-else-if="hasNothing">{{ t('events.empty') }}</EmptyNote>

    <div v-else class="events">
      <div>
        <p class="mono-label">{{ t('events.upcoming') }}</p>
        <EmptyNote v-if="!upcoming.length">{{ t('events.noUpcoming') }}</EmptyNote>
        <motion.article
          v-for="(event, i) in upcoming"
          :key="event.id"
          v-bind="cardStagger(i)"
          class="event"
        >
          <span class="event__date">
            <b>{{ dayOf(event) }}</b>
            <span>{{ monthOf(event) }}</span>
          </span>
          <div>
            <h3>{{ event.payload.title }}</h3>
            <p v-if="event.payload.content">{{ event.payload.content }}</p>
          </div>
        </motion.article>
      </div>

      <div>
        <p class="mono-label">{{ t('events.log') }}</p>
        <EmptyNote v-if="!past.length">{{ t('events.noPast') }}</EmptyNote>
        <div v-else class="log">
          <div v-for="event in past" :key="event.id" class="log__row">
            <b>{{ event.payload.title }}</b>
            <span>{{ fullDate(event) }}</span>
          </div>
        </div>
      </div>
    </div>
  </SectionShell>
</template>

<style lang="scss" scoped>
.events {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  gap: clamp(30px, 5vw, 78px);
  align-items: start;
  margin-top: clamp(32px, 4vw, 50px);
}

.event {
  display: grid;
  grid-template-columns: 68px 1fr;
  gap: clamp(16px, 2.2vw, 28px);
  align-items: start;
  padding: 22px 0;
  border-top: 1px solid var(--line);

  &:last-of-type {
    border-bottom: 1px solid var(--line);
  }

  h3 {
    font-weight: 600;
    font-size: clamp(16px, 1.5vw, 18.5px);
    line-height: 1.32;
    font-family: var(--font-body);
    letter-spacing: -0.012em;
  }

  p {
    max-width: 50ch;
    margin-top: 7px;
    color: var(--text-mid);
    font-size: 14.5px;
  }
}

.event__date {
  padding: 8px 0 9px;
  border: 1px solid var(--line);
  border-radius: var(--radius);
  text-align: center;

  b {
    display: block;
    color: var(--gold-500);
    font-weight: 700;
    font-size: 1.5rem;
    line-height: 1;
    font-family: var(--font-display);
    font-variant-numeric: tabular-nums;
  }

  span {
    display: block;
    margin-top: 5px;
    color: var(--text-low);
    font-size: 9.5px;
    font-family: var(--font-mono);
    letter-spacing: 0.14em;
    text-transform: uppercase;
  }
}

.log {
  margin-top: 14px;
  border-top: 1px solid var(--line);
}

.log__row {
  display: flex;
  gap: 16px;
  align-items: baseline;
  justify-content: space-between;
  padding: 13px 0;
  border-bottom: 1px solid var(--line);

  b {
    color: var(--text-mid);
    font-weight: 500;
    font-size: 14.5px;
  }

  span {
    color: var(--text-low);
    font-size: 11.5px;
    font-family: var(--font-mono);
    font-variant-numeric: tabular-nums;
    letter-spacing: 0.06em;
    white-space: nowrap;
  }
}

@media (width < 992px) {
  .events {
    grid-template-columns: 1fr;
  }
}
</style>
