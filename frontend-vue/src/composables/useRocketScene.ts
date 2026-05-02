import {
  AmbientLight,
  DirectionalLight,
  PerspectiveCamera,
  Scene,
  WebGLRenderer,
  type Group,
} from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import type { Ref } from 'vue';

export interface RocketSceneOptions {
  url: string;
  scale?: number | [number, number, number];
  position?: [number, number, number];
}

export const useRocketScene = (
  canvasRef: Ref<HTMLCanvasElement | null>,
  options: RocketSceneOptions,
) => {
  let renderer: WebGLRenderer | null = null;
  let scene: Scene | null = null;
  let camera: PerspectiveCamera | null = null;
  let rocket: Group | null = null;
  let frameId: number | null = null;
  let resizeObserver: ResizeObserver | null = null;

  const setup = async () => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    const parent = canvas.parentElement;
    const w = parent?.clientWidth ?? canvas.clientWidth;
    const h = parent?.clientHeight ?? canvas.clientHeight;

    renderer = new WebGLRenderer({ canvas, antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(w, h, false);

    scene = new Scene();

    camera = new PerspectiveCamera(75, w / h, 0.1, 100);
    camera.position.set(0, 0, 4);

    const ambient = new AmbientLight(0xffffff, 0.5);
    scene.add(ambient);

    const dir = new DirectionalLight(0xffffff, 3.2);
    dir.position.set(0, 20, 16);
    scene.add(dir);

    if (options.url) {
      try {
        const loader = new GLTFLoader();
        const gltf = await loader.loadAsync(options.url);
        rocket = gltf.scene;
        if (typeof options.scale === 'number') {
          rocket.scale.setScalar(options.scale);
        } else if (Array.isArray(options.scale)) {
          rocket.scale.set(...options.scale);
        }
        if (options.position) rocket.position.set(...options.position);
        scene.add(rocket);
      } catch (err) {
        console.error('[useRocketScene] failed to load GLB', err);
      }
    }

    const tick = () => {
      if (!renderer || !scene || !camera) return;
      if (rocket) rocket.rotation.y += 0.005;
      renderer.render(scene, camera);
      frameId = requestAnimationFrame(tick);
    };
    tick();

    if (parent) {
      resizeObserver = new ResizeObserver(() => {
        if (!renderer || !camera) return;
        const newW = parent.clientWidth;
        const newH = parent.clientHeight;
        camera.aspect = newW / newH;
        camera.updateProjectionMatrix();
        renderer.setSize(newW, newH, false);
      });
      resizeObserver.observe(parent);
    }
  };

  const teardown = () => {
    if (frameId !== null) cancelAnimationFrame(frameId);
    resizeObserver?.disconnect();
    renderer?.dispose();
    renderer = null;
    scene = null;
    camera = null;
    rocket = null;
  };

  onMounted(() => void setup());
  onBeforeUnmount(() => teardown());

  return { teardown };
};
