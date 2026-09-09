<script setup lang="ts">
import { CONTACT_EMAIL, UNIVERSITY_URL } from '@/lib/site';
/*
  Four columns, one top hairline, and 130px of bottom padding.

  The padding is what makes the pill's exit graceful rather than merely
  necessary: SiteNav detects the footer and slides away over it, but if
  the last row sat right at the bottom edge the pill would cover it for
  the frames before that fires.

  <footer> is also the element SiteNav queries for that detection — one
  per page, and this is it.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();

const SITE_LINKS = [
  { key: 'mission', to: '/#mission' },
  { key: 'rocket', to: '/#rocket' },
  { key: 'programme', to: '/#programme' },
  { key: 'events', to: '/#events' },
] as const;

const TEAM_LINKS = [
  { key: 'team', to: '/#team' },
  { key: 'sponsors', to: '/#sponsors' },
  { key: 'joinUs', to: '/join-us' },
] as const;
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const year = computed(() => new Date().getFullYear());
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <footer class="footer">
    <div class="container footer__grid">
      <div>
        <p class="footer__mark">EVRST</p>
        <p class="footer__addr">
          Escape Velocity Rocketry Student Team<br>
          {{ t('contact.address') }}
        </p>
      </div>

      <div>
        <h4>{{ t('footer.site') }}</h4>
        <ul>
          <li v-for="link in SITE_LINKS" :key="link.key">
            <RouterLink :to="link.to">{{ t(`nav.${link.key}`) }}</RouterLink>
          </li>
        </ul>
      </div>

      <div>
        <h4>{{ t('footer.team') }}</h4>
        <ul>
          <li v-for="link in TEAM_LINKS" :key="link.key">
            <RouterLink :to="link.to">{{ t(`nav.${link.key}`) }}</RouterLink>
          </li>
          <li>
            <!--
              A real href, not a router link: the admin panel is Laravel
              and Filament, served by the same domain but not by this SPA.
            -->
            <a href="/admin">{{ t('footer.admin') }}</a>
          </li>
        </ul>
      </div>

      <div>
        <h4>{{ t('footer.contact') }}</h4>
        <ul>
          <li><a :href="`mailto:${CONTACT_EMAIL}`">{{ CONTACT_EMAIL }}</a></li>
          <li>
            <a :href="UNIVERSITY_URL" target="_blank" rel="noopener noreferrer">
              uni-obuda.hu
            </a>
          </li>
        </ul>
      </div>
    </div>

    <div class="container footer__legal">
      <ObudaLogo class="footer__obuda" />
      <span>© {{ year }} Escape Velocity Rocketry Student Team</span>
    </div>
  </footer>
</template>

<style lang="scss" scoped>
.footer {
  // The pill hides over the footer, but the reserve is what keeps the
  // last row clear in the frames before that happens.
  padding-block: clamp(44px, 5vw, 72px) 130px;
  border-top: 1px solid var(--line);
}

.footer__grid {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr 1fr;
  gap: clamp(26px, 3.4vw, 54px);

  h4 {
    margin: 0 0 14px;
    color: var(--text-low);
    font-weight: 500;
    font-size: 10.5px;
    font-family: var(--font-mono);
    letter-spacing: 0.17em;
    text-transform: uppercase;
  }

  ul {
    display: grid;
    padding: 0;
    margin: 0;
    gap: 9px;
    list-style: none;
  }

  a {
    color: var(--text-mid);
    font-size: 14.5px;
    text-decoration: none;

    &:hover {
      color: var(--gold-500);
    }
  }
}

.footer__mark {
  font-weight: 700;
  font-size: 1.6rem;
  font-family: var(--font-display);
  letter-spacing: -0.035em;
}

.footer__addr {
  max-width: 34ch;
  margin-top: 12px;
  color: var(--text-low);
  font-size: 14px;
}

.footer__legal {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 24px;
  margin-top: clamp(40px, 5vw, 68px);
  border-top: 1px solid var(--line);
  color: var(--text-low);
  font-size: 12.5px;
  font-family: var(--font-mono);
  flex-wrap: wrap;
  gap: 20px;
  letter-spacing: 0.06em;
}

.footer__obuda {
  max-width: 160px;
  opacity: 0.6;
}

@media (width < 1200px) {
  .footer__grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (width < 768px) {
  .footer__grid {
    grid-template-columns: 1fr;
  }
}
</style>
