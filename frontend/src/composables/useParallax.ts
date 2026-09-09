import { onBeforeUnmount, onMounted, ref, type Ref } from 'vue';

/*
  The hero's scroll progress — the FALLBACK path only.

  The primary path is CSS: `animation-timeline: scroll(root block)` with a
  per-layer `animation-range`, which the browser drives off the scroll
  position itself. No JS, no listener, no per-frame measurement, and it
  keeps its rate while the main thread is busy. See HeroParallax.vue.

  This exists for browsers without it. Where the CSS path is available
  nothing below is called, so the listener is not merely unused — it is
  never attached.

  Both paths read the same `depth` out of Hero/layers.ts and the same
  `--pin` length, so there is one source of truth for the geometry and two
  ways of playing it.
*/

/** Does this browser drive animations off the scroll position on its own? */
export const CSS_SCROLL_DRIVEN =
  typeof CSS !== 'undefined' &&
  typeof CSS.supports === 'function' &&
  CSS.supports('animation-timeline', 'scroll()');

export const useParallax = (
  stageRef: Ref<HTMLElement | null>,
  paneRef: Ref<HTMLElement | null>,
) => {
  /** 0 while the hero is at rest, 1 once the pin has been scrolled out. */
  const progress = ref(0);

  /** The pin's length in px — `stage height − viewport`. */
  const pin = ref(1);

  let ticking = false;

  const measure = () => {
    const stage = stageRef.value;
    const pane = paneRef.value;
    if (!stage || !pane) {
      return;
    }
    pin.value = Math.max(1, stage.offsetHeight - pane.offsetHeight);
  };

  const read = () => {
    ticking = false;
    const stage = stageRef.value;
    if (!stage) {
      return;
    }
    // The stage's own top, so the hero behaves the same whether or not it
    // is the first thing on the page.
    const scrolled = -stage.getBoundingClientRect().top;
    progress.value = Math.min(1, Math.max(0, scrolled / pin.value));
  };

  const schedule = () => {
    if (ticking) {
      return;
    }
    ticking = true;
    requestAnimationFrame(read);
  };

  const onResize = () => {
    measure();
    schedule();
  };

  onMounted(() => {
    measure();
    read();
    if (CSS_SCROLL_DRIVEN) {
      // The CSS owns every layer's transform. Measuring here would be
      // work nothing reads.
      return;
    }
    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', onResize, { passive: true });
  });

  onBeforeUnmount(() => {
    window.removeEventListener('scroll', schedule);
    window.removeEventListener('resize', onResize);
  });

  return { progress, pin, measure };
};
