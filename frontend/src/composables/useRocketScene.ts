import {
  AdditiveBlending,
  AmbientLight,
  BufferGeometry,
  DirectionalLight,
  Float32BufferAttribute,
  PerspectiveCamera,
  Points,
  PointsMaterial,
  Scene,
  WebGLRenderer,
  type Group,
} from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';
import type { Ref } from 'vue';

export interface RocketSceneOptions {
  url: string;
  scale?: number | [number, number, number];
  position?: [number, number, number];
}

const STAR_COUNT = 220;
const STAR_RADIUS = 16;
const STAR_DEPTH = 120;

const buildStarfield = () => {
  const positions = new Float32Array(STAR_COUNT * 3);
  for (let i = 0; i < STAR_COUNT; i += 1) {
    const r = STAR_RADIUS + Math.random() * STAR_DEPTH;
    const theta = Math.random() * Math.PI * 2;
    const phi = Math.acos(2 * Math.random() - 1);
    positions[i * 3] = r * Math.sin(phi) * Math.cos(theta);
    positions[i * 3 + 1] = r * Math.sin(phi) * Math.sin(theta);
    positions[i * 3 + 2] = r * Math.cos(phi);
  }
  const geometry = new BufferGeometry();
  geometry.setAttribute('position', new Float32BufferAttribute(positions, 3));
  const material = new PointsMaterial({
    color: 0xffffff,
    size: 0.08,
    sizeAttenuation: true,
    transparent: true,
    opacity: 0.85,
    depthWrite: false,
    blending: AdditiveBlending,
  });
  return new Points(geometry, material);
};

export const useRocketScene = (
  canvasRef: Ref<HTMLCanvasElement | null>,
  options: RocketSceneOptions,
) => {
  let renderer: WebGLRenderer | null = null;
  let scene: Scene | null = null;
  let camera: PerspectiveCamera | null = null;
  let controls: OrbitControls | null = null;
  let stars: Points | null = null;
  let rocket: Group | null = null;
  let frameId: number | null = null;
  let resizeObserver: ResizeObserver | null = null;
  let disposed = false;

  const measure = (canvas: HTMLCanvasElement) => {
    const parent = canvas.parentElement;
    const w = parent?.clientWidth || canvas.clientWidth || window.innerWidth;
    const h = parent?.clientHeight || canvas.clientHeight || window.innerHeight;
    return { w: Math.max(1, w), h: Math.max(1, h) };
  };

  const setup = async () => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    const { w, h } = measure(canvas);

    renderer = new WebGLRenderer({ canvas, antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(w, h, false);
    renderer.setClearColor(0x000000, 0);

    scene = new Scene();

    camera = new PerspectiveCamera(75, w / h, 0.1, 200);
    camera.position.set(0, 0, 4);
    scene.add(camera);

    scene.add(new AmbientLight(0xffffff, 0.5));
    const dir = new DirectionalLight(0xffffff, 3.2);
    dir.position.set(0, 20, 16);
    scene.add(dir);

    stars = buildStarfield();
    scene.add(stars);

    controls = new OrbitControls(camera, canvas);
    controls.enablePan = false;
    controls.enableZoom = false;
    controls.enableDamping = true;
    controls.autoRotate = true;
    controls.autoRotateSpeed = 1.0;
    controls.minPolarAngle = Math.PI / 2;
    controls.maxPolarAngle = Math.PI / 2;

    if (options.url) {
      try {
        const loader = new GLTFLoader();
        const gltf = await loader.loadAsync(options.url);
        if (disposed || !scene) return;
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
      if (disposed || !renderer || !scene || !camera) return;
      controls?.update();
      if (stars) stars.rotation.y += 0.0006;
      renderer.render(scene, camera);
      frameId = requestAnimationFrame(tick);
    };
    tick();

    const parent = canvas.parentElement;
    if (parent) {
      resizeObserver = new ResizeObserver(() => {
        if (disposed || !renderer || !camera) return;
        const { w: nw, h: nh } = measure(canvas);
        camera.aspect = nw / nh;
        camera.updateProjectionMatrix();
        renderer.setSize(nw, nh, false);
      });
      resizeObserver.observe(parent);
    }
  };

  const teardown = () => {
    disposed = true;
    if (frameId !== null) cancelAnimationFrame(frameId);
    resizeObserver?.disconnect();
    controls?.dispose();
    if (stars) {
      stars.geometry.dispose();
      (stars.material as PointsMaterial).dispose();
    }
    renderer?.dispose();
    renderer = null;
    scene = null;
    camera = null;
    controls = null;
    stars = null;
    rocket = null;
  };

  onMounted(() => void setup());
  onBeforeUnmount(() => teardown());

  return { teardown };
};
