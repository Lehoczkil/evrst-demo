<script setup lang="ts">
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();

const groups = computed(() => [
  {
    title: t('outro.contact'),
    links: [{ label: 'evrstrocket@gmail.com', url: 'mailto:evrstrocket@gmail.com' }],
  },
  {
    title: t('outro.follow'),
    links: [
      { label: 'Instagram', url: 'https://instagram.com/evrst_rocketry', target: '_blank' },
      { label: 'Facebook', url: 'https://facebook.com/profile.php?id=61565957147301', target: '_blank' },
      { label: 'Youtube', url: 'https://youtube.com/watch?v=dQw4w9WgXcQ', target: '_blank' },
    ],
  },
  {
    title: t('outro.navigation'),
    links: [
      { label: t('nav.events'), url: '/#events' },
      { label: t('nav.about'), url: '/#about' },
      { label: t('nav.team'), url: '/#team' },
      { label: t('nav.mentors'), url: '/#mentors' },
      { label: t('nav.sponsors'), url: '/#sponsors' },
      { label: t('nav.joinUs'), url: '/join-us' },
    ],
  },
]);
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <section>
    <div
      class="container flex flex-col sm:(flex-row flex-wrap items-start justify-between) gap-32px py-64px"
    >
      <div class="flex items-center gap-16px">
        <div>
          <div class="text-[var(--color-dimmed)] fs-11px">
            {{ t('outro.poweredBy') }}
          </div>
          <div class="text-[var(--color-bright)] font-500 fs-14px lh-[1.2]">
            Obuda University
          </div>
        </div>
      </div>
      <div class="flex flex-wrap gap-32px">
        <div v-for="g in groups" :key="g.title" class="flex flex-col gap-6px">
          <div
            class="text-[var(--color-bright)] font-600 fs-12px uppercase"
          >
            {{ g.title }}
          </div>
          <a
            v-for="(link, i) in g.links"
            :key="i"
            :href="link.url"
            :target="(link as { target?: string }).target"
            class="text-[var(--color-dimmed)] fs-12px no-underline hover:text-primary"
          >
            {{ link.label }}
          </a>
        </div>
      </div>
    </div>
  </section>
</template>

<style lang="scss" scoped></style>
