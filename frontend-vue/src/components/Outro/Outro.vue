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
  <section class="outro">
    <div class="container outro__inner">
      <div class="outro__brand">
        <div class="outro__brand-text">
          <div class="outro__powered">{{ t('outro.poweredBy') }}</div>
          <div class="outro__university">Obuda University</div>
        </div>
      </div>
      <div class="outro__groups">
        <div v-for="g in groups" :key="g.title" class="outro__group">
          <div class="outro__group-title">{{ g.title }}</div>
          <a
            v-for="(link, i) in g.links"
            :key="i"
            :href="link.url"
            :target="(link as { target?: string }).target"
            class="outro__link"
          >
            {{ link.label }}
          </a>
        </div>
      </div>
    </div>
  </section>
</template>

<style lang="scss" scoped>
@import 'breakpoints';

.outro {
  &__inner {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 32px;
    flex-wrap: wrap;
    padding-top: 64px;
    padding-bottom: 64px;

    @include media-down(sm) {
      flex-direction: column;
    }
  }

  &__brand {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  &__powered {
    font-size: 11px;
    color: var(--color-dimmed);
  }

  &__university {
    font-size: 14px;
    font-weight: 500;
    color: var(--color-bright);
    line-height: 1.2;
  }

  &__groups {
    display: flex;
    gap: 32px;
    flex-wrap: wrap;
  }

  &__group {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  &__group-title {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--color-bright);
  }

  &__link {
    font-size: 12px;
    color: var(--color-dimmed);
    text-decoration: none;

    &:hover {
      color: var(--color-primary);
    }
  }
}
</style>
