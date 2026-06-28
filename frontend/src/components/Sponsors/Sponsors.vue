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
  const path = pathFromStorageUrl(logo);
  if (!path) return logo;
  if (/\.svg($|\?)/i.test(path)) return logo;
  return imgUrl(path, { width: 96, format: 'webp', fit: 'contain' });
};
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const looped = computed(() => {
  const arr = sponsors.value ?? [];
  if (!arr.length) return [];
  return [...arr, ...arr];
});
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div
    v-if="sponsors?.length"
    class="sponsors-marquee w-100vw -ml-[calc(50vw-50%)] overflow-hidden py-8px"
  >
    <div class="sponsors-track flex w-max gap-16px will-change-transform">
      <div
        v-for="(sponsor, i) in looped"
        :key="`${sponsor.id}-${i}`"
        class="flex items-center gap-12px shrink-0 w-300px h-64px py-8px px-16px border border-cardBorder rounded-4px bg-cardBg"
      >
        <Avatar
          :image="logoSrc(sponsor.payload.logo)"
          :label="sponsor.payload.name?.[0]"
          shape="square"
          size="large"
        />
        <div class="min-w-0 flex-1">
          <div class="font-600 fs-14px lh-[1.2] truncate">
            {{ sponsor.payload.name }}
          </div>
          <div v-if="sponsor.payload.year" class="text-primary fs-12px lh-[1.2]">
            {{ sponsor.payload.year }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@keyframes sponsors-scroll {
  from {
    transform: translateX(0);
  }

  to {
    transform: translateX(-50%);
  }
}

.sponsors-marquee {
  mask-image: linear-gradient(
    to right,
    transparent,
    #000 10%,
    #000 90%,
    transparent
  );

  &:hover .sponsors-track {
    animation-play-state: paused;
  }
}

.sponsors-track {
  animation: sponsors-scroll 40s linear infinite;
}
</style>
