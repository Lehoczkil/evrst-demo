import { onBeforeUnmount, onMounted, ref, type Ref } from 'vue';

/*
  "Is this block far enough off screen that its ambient motion can stop?"

  The home page is about nine viewports tall and two of its pieces run
  animations with no resting state — the hero's comets, beacon and engine
  flame, and the marquee's 6890px-wide track. Left alone those keep
  ticking for the whole page, and the cost is not the tick: it is that an
  element with a running animation stays promoted to its own compositor
  layer, so the marquee alone holds a texture several viewports wide
  while the reader is somewhere else entirely. On a desktop that is
  invisible; on a mid-range phone it is memory taken from the things
  actually on screen.

  `animation-play-state: paused` is what the caller does with this, and
  pausing is what lets the browser drop the promotion. It only reaches
  CSS animations — motion-v's are WAAPI and unaffected — which is the
  right scope: the CSS ones are the infinite loops.

  The margin is deliberate. Pausing exactly at the viewport edge means a
  reader scrolling slowly sits on the boundary and the observer flaps; a
  viewport of slack means motion has always been running for a while
  before any of it is visible.
*/
export const useOffscreenIdle = (target: Ref<HTMLElement | null>) => {
  /** True once the element is comfortably out of view. */
  const idle = ref(false);

  let observer: IntersectionObserver | null = null;

  onMounted(() => {
    const el = target.value;
    if (!el || typeof IntersectionObserver === 'undefined') {
      return;
    }

    observer = new IntersectionObserver(
      ([entry]) => {
        idle.value = !entry.isIntersecting;
      },
      { rootMargin: '100% 0px' },
    );
    observer.observe(el);
  });

  onBeforeUnmount(() => {
    observer?.disconnect();
    observer = null;
  });

  return { idle };
};
