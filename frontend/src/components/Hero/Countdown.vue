<script setup lang="ts">
/*
  Time to the next event.

  Real data or nothing: the target is the soonest Event with `start_at` in
  the future, and when there is none this renders nothing at all rather
  than an empty clock. A `—:—:—` promises a launch that is not scheduled.
*/
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
const { target = null, label = '' } = defineProps<{
  target?: Date | null;
  /** What is being counted to, e.g. "Következő: statikus hajtóműteszt". */
  label?: string;
}>();
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();
const targetRef = computed(() => target);
const { remaining } = useCountdown(targetRef);
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
const pad = (n: number) => String(n).padStart(2, '0');
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
  <div v-if="remaining" class="countdown inline-flex flex-col items-center gap-8px">
    <!-- role=timer + aria-live=off: it changes every second, and a screen
         reader announcing that is unusable. The label below carries the
         meaning, and the value is readable on demand. -->
    <span class="clock" role="timer" aria-live="off">
      {{ remaining.days }}<small>{{ t('hero.unitDay') }}</small>
      {{ pad(remaining.hours) }}<small>{{ t('hero.unitHour') }}</small>
      {{ pad(remaining.minutes) }}<small>{{ t('hero.unitMinute') }}</small>
      {{ pad(remaining.seconds) }}<small>{{ t('hero.unitSecond') }}</small>
    </span>
    <span v-if="label" class="mono-label">{{ label }}</span>
  </div>
</template>

<style lang="scss" scoped>
.clock {
  color: var(--gold-500);
  font-weight: 500;
  font-size: clamp(1.35rem, 3vw, 2.1rem);
  font-family: var(--font-mono);
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.05em;

  // The unit sits tight against its number and pushes the next one away,
  // so the four pairs read as four values rather than eight tokens.
  small {
    margin-right: 11px;
    margin-left: 3px;
    color: var(--text-low);
    font-size: 0.45em;
  }
}
</style>
