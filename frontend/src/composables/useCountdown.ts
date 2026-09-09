import { computed, onBeforeUnmount, onMounted, ref, watch, type Ref } from 'vue';

/*
  Time remaining until a target, ticking once a second.

  No `T−` prefix anywhere it is rendered: it was there in the first draft
  and it read as mission-control cosplay over a student team's next bench
  test. The units carry the meaning.
*/

export type Remaining = {
  days: number;
  hours: number;
  minutes: number;
  seconds: number;
};

export const useCountdown = (target: Ref<Date | null | undefined>) => {
  const now = ref(Date.now());
  let timer: ReturnType<typeof setInterval> | null = null;

  const stop = () => {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
  };

  const start = () => {
    stop();
    now.value = Date.now();
    if (target.value) {
      timer = setInterval(() => {
        now.value = Date.now();
      }, 1000);
    }
  };

  /** Null when there is no target, or it has passed. */
  const remaining = computed<Remaining | null>(() => {
    const at = target.value?.getTime();
    if (!at) {
      return null;
    }
    const ms = at - now.value;
    if (ms <= 0) {
      return null;
    }
    const total = Math.floor(ms / 1000);

    return {
      days: Math.floor(total / 86400),
      hours: Math.floor(total / 3600) % 24,
      minutes: Math.floor(total / 60) % 60,
      seconds: total % 60,
    };
  });

  // Stop ticking the moment there is nothing left to count.
  watch(remaining, (value) => {
    if (!value) {
      stop();
    }
  });

  watch(target, start);

  onMounted(start);
  onBeforeUnmount(stop);

  return { remaining };
};
