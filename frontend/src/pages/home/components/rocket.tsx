import { Vector3 } from 'three';
import { Gltf } from '@react-three/drei';

interface Props {
  url: string;
  scale?: Vector3 | number | [number, number, number];
  position?: Vector3 | [number, number, number];
}

export function Rocket(props: Props) {
  return (
    <group scale={props.scale} position={props.position}>
      <Gltf src={props.url} />
    </group>
  );
}
