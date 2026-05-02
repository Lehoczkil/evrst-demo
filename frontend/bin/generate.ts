#!/usr/bin/env bun
/* eslint-disable */
import { mkdirSync, writeFileSync, existsSync } from 'node:fs';
import { join } from 'node:path';

const args = process.argv.slice(2);
const cmd = args[0];
const name = args[1];

if (!cmd || !name) {
  console.error('Usage: bun bin/generate.ts <component|page> <Name>');
  process.exit(1);
}

const root = process.cwd();
const dir = cmd === 'component' ? join(root, 'src/components', name) : join(root, 'src/pages');
const file = cmd === 'component' ? join(dir, `${name}.vue`) : join(dir, `${name}.vue`);

if (cmd === 'component') {
  if (existsSync(file)) {
    console.error(`Already exists: ${file}`);
    process.exit(1);
  }
  mkdirSync(dir, { recursive: true });
}

const template = `<script lang="ts" setup>
/*---------------------------------------------
/  PROPS & EMITS
---------------------------------------------*/
/*---------------------------------------------
/  VARIABLES
---------------------------------------------*/
/*---------------------------------------------
/  METHODS
---------------------------------------------*/
/*---------------------------------------------
/  COMPUTED
---------------------------------------------*/
/*---------------------------------------------
/  WATCHERS
---------------------------------------------*/
/*---------------------------------------------
/  HOOKS
---------------------------------------------*/
</script>

<template>
  <div class="${name.toLowerCase()}"></div>
</template>

<style lang="scss" scoped></style>
`;

writeFileSync(file, template);
console.log(`Created ${file}`);
