<script setup lang="ts">
import { motion } from 'motion-v';

/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
const { t } = useI18n();

const titleLines = ['Escape', 'Velocity', 'Rocketry', 'Student', 'Team'];

const rocketInfo = [
  { labelKey: 'rocket.height', value: '95 cm' },
  { labelKey: 'rocket.diameter', value: '7.65 cm' },
  { labelKey: 'rocket.thrust', value: '700 N' },
  { labelKey: 'rocket.mass', value: '3.5 kg' },
];

const ease: [number, number, number, number] = [0.22, 1, 0.36, 1];
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
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
  <section class="relative z-10 w-full h-100vh p-16px sm:p-32px">
    <div
      class="container flex flex-col items-start justify-between gap-32px h-full pt-80px md:(flex-row items-center pt-0)"
    >
      <div class="w-full md:flex-[0_0_50%]">
        <h1
          class="m-0 flex flex-col items-start uppercase text-primary fs-[clamp(1.125rem,5.5vw,3rem)] lh-[1.16] font-[var(--font-family-headline)]"
        >
          <motion.span
            v-for="(word, i) in titleLines"
            :key="word"
            class="pr-16px"
            :initial="{ opacity: 0, y: 12 }"
            :animate="{ opacity: 1, y: 0 }"
            :transition="{ duration: 0.4, delay: i * 0.06, ease }"
          >
            {{ word }}
          </motion.span>
        </h1>
      </div>

      <div
        class="hidden md:flex w-full max-w-420px flex-col gap-12px md:flex-basis-[40%]"
      >
        <motion.div
          v-for="(item, i) in rocketInfo"
          :key="item.labelKey"
          class="flex items-center gap-12px"
          :initial="{ opacity: 0, x: 16 }"
          :animate="{ opacity: 1, x: 0 }"
          :transition="{ duration: 0.4, delay: 0.15 + i * 0.06, ease }"
        >
          <hr class="flex-grow border-0 border-t border-cardBorder" />
          <div
            class="w-160px py-12px px-16px border border-cardBorder rounded-4px bg-cardBg text-center"
          >
            <div class="font-500 fs-16px">{{ item.value }}</div>
            <div class="text-[var(--color-dimmed)] fs-12px uppercase">
              {{ t(item.labelKey) }}
            </div>
          </div>
        </motion.div>
      </div>
    </div>
  </section>
</template>

<style lang="scss" scoped></style>
