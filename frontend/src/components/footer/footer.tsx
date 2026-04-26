import { Text } from '@mantine/core';
import { Container } from '@/components/container';
import classes from './footer.module.css';

export function Footer() {
  return (
    <footer>
      <Container className={classes.container}>
        <Text c='dimmed' fz='11px'>
          © {new Date().getFullYear()} Escape Velocity Rocketry Student Team.
          Build: v{__APP_VERSION__}
        </Text>
      </Container>
    </footer>
  );
}
