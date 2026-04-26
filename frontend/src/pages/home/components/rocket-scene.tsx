import { Suspense } from 'react';
import { Canvas } from '@react-three/fiber';
import {
  Loader,
  OrbitControls,
  PerspectiveCamera,
  Stars,
} from '@react-three/drei';
import { Box } from '@mantine/core';
import type { Vector3 } from 'three';

import { Rocket } from './rocket';

import classes from '../home.module.css';

interface Props {
  rocketUrl?: string;
  scale?: Vector3 | number | [number, number, number];
  position?: Vector3 | [number, number, number];
}

export function RocketScene({ rocketUrl, scale, position }: Props) {
  return (
    <>
      <Box className={classes.canvasWrap}>
        <Canvas dpr={[1.5, 2]} linear shadows>
          <ambientLight intensity={0.5} />
          <PerspectiveCamera fov={75} position={[0, 0, 4]} makeDefault>
            <directionalLight
              position={[0, 20, 16]}
              shadow-camera-right={8}
              shadow-camera-top={8}
              shadow-camera-left={-8}
              shadow-camera-bottom={-8}
              shadow-mapSize-width={1024}
              shadow-mapSize-height={1024}
              intensity={3.2}
              shadow-bias={-0.0001}
              castShadow
            />
          </PerspectiveCamera>
          <Suspense fallback={null}>
            {rocketUrl && (
              <Rocket url={rocketUrl} scale={scale} position={position} />
            )}
          </Suspense>
          <OrbitControls
            autoRotate
            autoRotateSpeed={1}
            enablePan={false}
            enableZoom={false}
            maxPolarAngle={Math.PI / 2}
            minPolarAngle={Math.PI / 2}
          />
          <Stars radius={16} depth={120} count={200} fade />
        </Canvas>
      </Box>
      <Loader />
    </>
  );
}

export default RocketScene;
