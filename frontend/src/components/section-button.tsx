import { Button } from '@mantine/core';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function SectionButton(props: any) {
  return (
    <Button
      size='sm'
      radius='xl'
      variant='outline'
      tt='uppercase'
      styles={{
        root: { fontSize: '14px', height: '36px', paddingInline: '20px' },
        section: { marginInlineStart: '4px' },
      }}
      {...props}
    />
  );
}
