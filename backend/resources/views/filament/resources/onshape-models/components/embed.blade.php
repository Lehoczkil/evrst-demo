{{-- Onshape model viewer. Renders a cached GLB in-place with vanilla
     Three.js (loaded as ES modules from a CDN). When no GLB is cached
     yet we surface the "Open in Onshape" card so the document is
     reachable; the "Export GLB" action populates the GLB and the
     viewer takes over on next render.

     IMPORTANT: the importmap + module script are rendered OUTSIDE the
     @if($hasGlb) branch so window.evrstMountOnshapeViewer is always
     defined on first paint. Livewire DOM diffs do not re-execute
     <script type="module"> tags, so we can't rely on the script
     appearing only when the GLB is ready. --}}

@php
    $glbUrl = $model?->glb_url;
    $openUrl = $model?->embed_url;
    $hasGlb = (bool) $glbUrl;
    $exporting = in_array($model?->glb_status, [\App\Models\OnshapeModel::GLB_QUEUED, \App\Models\OnshapeModel::GLB_RUNNING], true);
    $failed = $model?->glb_status === \App\Models\OnshapeModel::GLB_FAILED;
@endphp

{{-- Always-on viewer bootstrap. Importmap + Three.js mount function. --}}
<script type="importmap">
{
    "imports": {
        "three": "https://unpkg.com/three@0.161.0/build/three.module.js",
        "three/addons/": "https://unpkg.com/three@0.161.0/examples/jsm/"
    }
}
</script>
<script type="module">
    import * as THREE from 'three';
    import { GLTFLoader }   from 'three/addons/loaders/GLTFLoader.js';
    import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

    if (!window.evrstMountOnshapeViewer) {
        window.evrstMountOnshapeViewer = function (wrap, glbUrl) {
            if (!wrap || !glbUrl) return;
            // Wait until the wrap actually has dimensions — Filament
            // sometimes paints the section behind a collapsed parent.
            // Fall back to sensible defaults if we still see 0x0.
            let w = wrap.clientWidth;
            let h = wrap.clientHeight;
            if (w === 0 || h === 0) {
                requestAnimationFrame(() => window.evrstMountOnshapeViewer(wrap, glbUrl));
                return;
            }
            w = w || 600;
            h = h || 540;

            // Tear down any prior renderer on the same wrap.
            if (wrap.__evrstViewer) {
                try { wrap.__evrstViewer.dispose(); } catch (e) {}
            }

            const scene = new THREE.Scene();
            scene.background = null;
            const camera = new THREE.PerspectiveCamera(45, w / Math.max(1, h), 0.01, 10000);
            camera.position.set(2, 1.5, 3);

            const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            renderer.setSize(w, h);
            renderer.outputColorSpace = THREE.SRGBColorSpace;
            wrap.innerHTML = '';
            wrap.appendChild(renderer.domElement);

            scene.add(new THREE.HemisphereLight(0xffffff, 0x222233, 1.0));
            const sun = new THREE.DirectionalLight(0xffffff, 1.4);
            sun.position.set(5, 10, 7);
            scene.add(sun);

            const controls = new OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.08;

            const loader = new GLTFLoader();
            let raf;
            loader.load(
                glbUrl,
                (gltf) => {
                    scene.add(gltf.scene);
                    const box = new THREE.Box3().setFromObject(gltf.scene);
                    const size = box.getSize(new THREE.Vector3());
                    const center = box.getCenter(new THREE.Vector3());
                    const maxDim = Math.max(size.x, size.y, size.z) || 1;
                    const distance = maxDim * 1.6;
                    camera.near = maxDim / 1000;
                    camera.far = maxDim * 100;
                    camera.position.copy(center).add(new THREE.Vector3(distance, distance * 0.6, distance));
                    camera.updateProjectionMatrix();
                    controls.target.copy(center);
                    controls.update();
                },
                undefined,
                (err) => { console.error('GLB load failed', err); },
            );

            const tick = () => {
                raf = requestAnimationFrame(tick);
                controls.update();
                renderer.render(scene, camera);
            };
            tick();

            const onResize = () => {
                const nw = wrap.clientWidth, nh = wrap.clientHeight;
                if (nw === 0 || nh === 0) return;
                camera.aspect = nw / nh;
                camera.updateProjectionMatrix();
                renderer.setSize(nw, nh);
            };
            window.addEventListener('resize', onResize);

            wrap.__evrstViewer = {
                dispose() {
                    cancelAnimationFrame(raf);
                    window.removeEventListener('resize', onResize);
                    controls.dispose();
                    renderer.dispose();
                    scene.traverse((obj) => {
                        if (obj.geometry?.dispose) obj.geometry.dispose();
                        if (obj.material) {
                            const m = Array.isArray(obj.material) ? obj.material : [obj.material];
                            m.forEach((mm) => mm.dispose && mm.dispose());
                        }
                    });
                    wrap.innerHTML = '';
                },
            };
        };
    }
</script>

@if ($hasGlb)
    <div
        class="bg-white dark:bg-gray-900 dark:border dark:border-white/10"
        style="
            position: relative;
            border-radius: .75rem;
            border: 1px solid rgba(15,23,42,.08);
            overflow: hidden;
        "
        x-data="{ url: @js($glbUrl) }"
        x-init="
            const tryMount = () => {
                if (window.evrstMountOnshapeViewer) {
                    window.evrstMountOnshapeViewer($refs.canvasWrap, url);
                } else {
                    setTimeout(tryMount, 80);
                }
            };
            tryMount();
        "
    >
        <div x-ref="canvasWrap" style="width: 100%; height: 540px; min-height: 360px;
            background: #f8fafc;
        " class="dark:!bg-gray-950"></div>

        <div style="
            position: absolute; right: .75rem; top: .75rem;
            display: flex; gap: .35rem; align-items: center;
        ">
            <a
                href="{{ $openUrl }}" target="_blank" rel="noopener noreferrer"
                style="
                    display:inline-flex; align-items:center; gap:.35rem;
                    background: rgba(15,23,42,.7); color: white;
                    border-radius: .375rem; padding: .35rem .55rem;
                    font-size: .72rem; text-decoration: none;
                "
            >
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                {{ __('admin.onshape.view_in_onshape') }}
            </a>
        </div>

        @if ($model?->glb_exported_at)
            <div style="
                position: absolute; left: .75rem; bottom: .75rem;
                font-size: .65rem; color: rgb(100 116 139);
                background: rgba(255,255,255,.85); padding: .15rem .5rem;
                border-radius: .375rem;
            " class="dark:!bg-white/10 dark:!text-gray-300">
                {{ __('admin.onshape.glb_exported_at', [
                    'date' => $model->glb_exported_at->format('d M Y H:i'),
                    'size' => number_format(($model->glb_size ?? 0) / 1024, 1) . ' KB',
                ]) }}
            </div>
        @endif
    </div>
@elseif ($model)
    <div
        class="bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100 dark:border dark:border-white/10"
        style="
            display: flex; flex-direction: column; align-items: center;
            gap: 1rem; padding: 2.5rem 1.5rem;
            border-radius: .75rem;
            border: 1px solid rgba(15,23,42,.08);
            text-align: center;
        "
    >
        <div style="
            width: 72px; height: 72px;
            border-radius: 9999px;
            background: rgba(245, 158, 11, .15);
            display: inline-flex; align-items: center; justify-content: center;
            color: rgb(245 158 11);
        ">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                <line x1="12" y1="22.08" x2="12" y2="12"/>
            </svg>
        </div>

        <div style="font-size: 1rem; font-weight: 600;">{{ $model->title }}</div>

        @if ($exporting)
            <p class="text-gray-600 dark:text-gray-300" style="font-size: .85rem;">
                {{ __('admin.onshape.export_running') }}
            </p>
        @elseif ($failed)
            <p class="text-red-600 dark:text-red-300" style="font-size: .85rem; max-width: 32rem;">
                {{ __('admin.onshape.export_failed', ['reason' => $model->glb_error ?: '']) }}
            </p>
        @else
            <p class="text-gray-600 dark:text-gray-300" style="font-size: .85rem; max-width: 32rem;">
                {{ __('admin.onshape.no_glb_yet') }}
            </p>
        @endif

        @if ($openUrl)
            <a
                href="{{ $openUrl }}" target="_blank" rel="noopener noreferrer"
                style="
                    display: inline-flex; align-items: center; gap: .5rem;
                    background: rgb(245 158 11); color: rgb(120 53 15);
                    border: 0; border-radius: .5rem;
                    padding: .65rem 1.1rem;
                    font-size: .9rem; font-weight: 600;
                    text-decoration: none;
                "
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                {{ __('admin.onshape.view_in_onshape') }}
            </a>
        @endif
    </div>
@endif
