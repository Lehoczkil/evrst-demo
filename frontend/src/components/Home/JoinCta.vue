<script setup lang="ts">
import { CONTACT_EMAIL } from '@/lib/site';
import { motion } from 'motion-v';
import { TeamRequests, type TeamGroup } from '@/services/requests/TeamRequests';
import { cardStagger, sectionRise } from './anims';

/*
  The two things an applicant actually needs: that non-engineers are
  wanted, and where the work is.

  The "where we need people" list is honest about its own limitation — the
  schema has no open-position field (plan §14/6), so the groups are real
  and the counts are not shown. Listing the disciplines without inventing
  vacancy numbers is the version that is true.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t, locale } = useI18n();

const { data: groups } = useQuery<TeamGroup[]>({
  key: ['team-groups', locale],
  request: () => TeamRequests.groups(locale.value as string),
  cache: true,
  staleTime: 300,
});
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
const disciplines = computed(() => (groups.value ?? []).filter(
  // Management is not what a new member applies to.
  (group) => !group.slug.includes('menedzser'),
));
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <SectionShell id="join" :eyebrow="t('join.eyebrow')" :title="t('join.title')">
    <div class="join">
      <motion.div v-bind="sectionRise()">
        <p class="lede">{{ t('join.lede') }}</p>
        <div class="cta-row">
          <RouterLink to="/join-us" class="btn">{{ t('join.cta') }}</RouterLink>
          <a class="btn btn--ghost" :href="`mailto:${CONTACT_EMAIL}`">
            {{ t('join.question') }}
          </a>
        </div>
      </motion.div>

      <div v-if="disciplines.length">
        <p class="mono-label join__heading">{{ t('join.disciplines') }}</p>
        <div class="needs">
          <motion.div
            v-for="(group, i) in disciplines"
            :key="group.slug"
            v-bind="cardStagger(i, 0.03)"
            class="need"
          >
            <b>{{ group.name }}</b>
          </motion.div>
        </div>
      </div>
    </div>
  </SectionShell>
</template>

<style lang="scss" scoped>
.join {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: clamp(36px, 6vw, 100px);
  align-items: start;
  margin-top: clamp(40px, 5vw, 72px);
}

.join__heading {
  display: block;
}

.cta-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 30px;
}

.needs {
  margin-top: 20px;
  border-top: 1px solid var(--line);
}

.need {
  display: flex;
  gap: 16px;
  align-items: baseline;
  justify-content: space-between;
  padding: 14px 0;
  border-bottom: 1px solid var(--line);

  b {
    font-weight: 500;
    font-size: 15px;
    letter-spacing: -0.012em;
  }
}

@media (width < 992px) {
  .join {
    grid-template-columns: 1fr;
  }
}
</style>
