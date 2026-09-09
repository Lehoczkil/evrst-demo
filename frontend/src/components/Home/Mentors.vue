<script setup lang="ts">
import { CmsRequests, type MentorResource } from '@/services/requests/CmsRequests';
import { imgUrl, pathFromStorageUrl } from '@/lib/imgUrl';

/*
  A sub-band of the team section, divided by a rule rather than given a
  section of its own.

  Two people do not need a heading, an eyebrow and 200px of padding. It
  renders nothing at all when the collection is empty — a mentors band
  with no mentors is not an empty state worth explaining, it is a band
  that should not be there.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t, locale } = useI18n();

const { data: mentors } = useQuery<MentorResource[]>({
  key: ['mentors', locale],
  request: () => CmsRequests.mentors(),
  cache: true,
  staleTime: 300,
});
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const photoFor = (mentor: MentorResource) => {
  const raw = mentor.payload.photo ?? mentor.objects?.find((o) => o.key === 'photo')?.url;
  if (!raw) {
    return undefined;
  }
  const path = pathFromStorageUrl(raw);
  // SVGs pass through untouched — /api/img does the same, and rasterising
  // a vector to 72px is strictly worse than serving it.
  if (!path || /\.svg($|\?)/i.test(path)) {
    return raw;
  }

  return imgUrl(path, { width: 72, format: 'webp', fit: 'cover' });
};

/** The domain only: a mentor's mailbox is not ours to publish. */
const domainOf = (email: string | undefined) => email?.split('@')[1] ?? '';
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div v-if="mentors?.length" class="mentors-band">
    <p class="eyebrow">{{ t('team.mentors', { count: mentors.length }) }}</p>
    <div class="mentors">
      <div v-for="mentor in mentors" :key="mentor.id" class="mentor">
        <img
          v-if="photoFor(mentor)"
          :src="photoFor(mentor)"
          :alt="mentor.payload.name"
          loading="lazy"
        />
        <div>
          <b>{{ mentor.payload.name }}</b>
          <span v-if="mentor.payload.email">{{ domainOf(mentor.payload.email) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.mentors-band {
  display: grid;
  align-items: baseline;
  padding-top: clamp(30px, 3.6vw, 44px);
  margin-top: clamp(56px, 6vw, 96px);
  border-top: 1px solid var(--paper-line);
  grid-template-columns: auto 1fr;
  gap: clamp(22px, 4vw, 60px);
}

.mentors {
  display: flex;
  flex-wrap: wrap;
  gap: clamp(24px, 4vw, 56px);
}

.mentor {
  display: flex;
  gap: 12px;
  align-items: center;

  img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
  }

  b {
    display: block;
    font-weight: 600;
    font-size: 16px;
    letter-spacing: -0.014em;
  }

  span {
    display: block;
    margin-top: 4px;
    color: var(--on-paper-mid);
    font-size: 10.5px;
    font-family: var(--font-mono);
    letter-spacing: 0.08em;
  }
}

@media (width < 992px) {
  .mentors-band {
    grid-template-columns: 1fr;
  }
}
</style>
