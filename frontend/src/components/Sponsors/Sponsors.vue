<script setup lang="ts">
import Avatar from 'primevue/avatar';
import { CmsRequests, type SponsorResource } from '@/services/requests/CmsRequests';
import { imgUrl, pathFromStorageUrl } from '@/lib/imgUrl';

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
/  METHODS
---------------------------------------------*/
const logoSrc = (logo: string | undefined) => {
  if (!logo) return undefined;
  // SVGs are passed straight to /api/img — the backend short-circuits
  // them to the original bytes since vector resize is meaningless.
  const path = pathFromStorageUrl(logo);
  if (!path) return logo;
  if (/\.svg($|\?)/i.test(path)) return logo;
  return imgUrl(path, { width: 96, format: 'webp', fit: 'contain' });
};
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
        :image="logoSrc(sponsor.payload.logo)"
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
    width: 300px;
    padding: 8px 16px;
    border: 1px solid var(--card-border);
    border-radius: 4px;
    background: var(--card-bg);
    gap: 12px;
  }

  &__meta {
    min-width: 0;
    flex: 1;
  }

  &__name {
    font-weight: 600;
    font-size: 14px;
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  &__year {
    color: var(--color-primary);
    font-size: 12px;
    line-height: 1.2;
  }
}
</style>
