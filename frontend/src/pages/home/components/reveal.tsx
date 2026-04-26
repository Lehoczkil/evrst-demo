import type { CSSProperties, ReactNode } from 'react';
import { motion } from 'motion/react';

interface Props {
  children: ReactNode;
  delay?: number;
  duration?: number;
  y?: number;
  /** When true, animates on first paint instead of when scrolled into view. */
  immediate?: boolean;
  className?: string;
  style?: CSSProperties;
}

export function Reveal({
  children,
  delay = 0,
  duration = 0.35,
  y = 12,
  immediate = false,
  className,
  style,
}: Props) {
  const transition = {
    duration,
    delay,
    ease: [0.22, 1, 0.36, 1] as [number, number, number, number],
  };

  if (immediate) {
    return (
      <motion.div
        initial={{ opacity: 0, y }}
        animate={{ opacity: 1, y: 0 }}
        transition={transition}
        className={className}
        style={style}
      >
        {children}
      </motion.div>
    );
  }

  return (
    <motion.div
      initial={{ opacity: 0, y }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, amount: 0.15 }}
      transition={transition}
      className={className}
      style={style}
    >
      {children}
    </motion.div>
  );
}
