<script setup lang="ts">
import Avatar from 'primevue/avatar';
import { CmsRequests, type MentorResource } from '@/services/requests/CmsRequests';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { data: mentors } = useQuery<MentorResource[]>({
  key: ['mentors'],
  request: () => CmsRequests.mentors(),
});
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const photoFor = (m: MentorResource) =>
  m.payload.photo ?? m.objects?.find((o) => o.key === 'photo')?.url ?? undefined;

const initials = (name: string) =>
  name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase() ?? '')
    .join('');
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div class="mentors">
    <div v-for="mentor in mentors ?? []" :key="mentor.id" class="mentors__card">
      <Avatar
        :image="photoFor(mentor)"
        :label="initials(mentor.payload.name)"
        shape="circle"
        size="xlarge"
      />
      <div class="mentors__name">{{ mentor.payload.name }}</div>
      <a :href="`mailto:${mentor.payload.email}`" class="mentors__email">
        {{ mentor.payload.email }}
      </a>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import 'breakpoints';

.mentors {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  justify-content: center;

  &__card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 20px;
    width: 280px;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 8px;

    @include media-down(sm) {
      width: 100%;
    }
  }

  &__name {
    font-size: 15px;
    font-weight: 600;
    text-align: center;
    line-height: 1.2;
  }

  &__email {
    font-size: 12px;
    color: var(--color-dimmed);
    text-decoration: none;
    word-break: break-all;
    text-align: center;

    &:hover {
      color: var(--color-primary);
    }
  }
}
</style>
