<script setup lang="ts">
import Avatar from 'primevue/avatar';
import { CmsRequests, type SponsorResource } from '@/services/requests/CmsRequests';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { data: sponsors } = useQuery<SponsorResource[]>({
  key: ['sponsors'],
  request: () => CmsRequests.sponsors(),
});
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div v-if="sponsors?.length" class="sponsors">
    <div
      v-for="sponsor in sponsors"
      :key="sponsor.id"
      class="sponsors__card"
    >
      <Avatar
        :image="sponsor.payload.logo"
        :label="sponsor.payload.name?.[0]"
        shape="square"
        size="large"
      />
      <div class="sponsors__meta">
        <div class="sponsors__name">{{ sponsor.payload.name }}</div>
        <div v-if="sponsor.payload.year" class="sponsors__year">
          {{ sponsor.payload.year }}
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.sponsors {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  justify-content: center;

  &__card {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 300px;
    padding: 8px 16px;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 4px;
  }

  &__meta {
    min-width: 0;
    flex: 1;
  }

  &__name {
    font-size: 14px;
    font-weight: 600;
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__year {
    font-size: 12px;
    color: var(--color-primary);
    line-height: 1.2;
  }
}
</style>
