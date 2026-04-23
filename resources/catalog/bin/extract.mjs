#!/usr/bin/env node
// Requires Node >= 22 (uses --experimental-strip-types on-the-fly).
// Usage: node --experimental-strip-types resources/catalog/bin/extract.mjs
//
// Pulls catalog source-of-truth from the frontend TypeScript data files and
// emits JSON into resources/catalog/data/ for the Catalog module seeders.
//
// The extract is idempotent: run it any time the frontend data files change.

import { writeFile, mkdir } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const backendRoot = resolve(here, '../../..');
const dataDir = resolve(backendRoot, 'resources/catalog/data');
const frontendDir = process.env.FRONTEND_DIR
  ? resolve(process.env.FRONTEND_DIR, 'src/modules/medical-records')
  : resolve(
      backendRoot,
      '../../e-medical-record-frontend/src/modules/medical-records',
    );

const load = async (relative) => {
  const path = resolve(frontendDir, relative);
  return import(path);
};

const writeJson = async (name, payload) => {
  await mkdir(dataDir, { recursive: true });
  const path = resolve(dataDir, `${name}.json`);
  await writeFile(path, `${JSON.stringify(payload, null, 2)}\n`);
  console.log(`wrote ${name}.json`);
};

const main = async () => {
  const magistralModule = await load('data/magistralCatalogData.ts');
  const injectableModule = await load('data/injectableCatalogData.ts');
  const problemsModule = await load('types/defaults/problem-list-defaults.ts');
  const panelsModule = await load('types/defaults/lab-panel-definitions.ts');

  const magistral = magistralModule.magistralCatalog;
  const injectable = injectableModule.injectableCatalogData;
  const problems = problemsModule.PREDEFINED_PROBLEMS;
  const panels = panelsModule.LAB_PANEL_DEFINITIONS;

  await writeJson('magistral', magistral);
  await writeJson('injectable', injectable);
  await writeJson('problem-list', problems);
  await writeJson('lab-panels', panels);
};

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
