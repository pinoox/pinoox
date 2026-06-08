import { fileURLToPath } from 'node:url';
import { mkdirSync, writeFileSync, statSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { pathToFileURL } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const serverDir = join(root, 'dist/server');
const serverEntry = [join(serverDir, 'entry-server.js'), join(serverDir, 'entry-server.mjs')]
    .find((path) => {
        try {
            return statSync(path).isFile();
        } catch {
            return false;
        }
    });

if (!serverEntry) {
    throw new Error('SSR bundle not found. Run vite build --ssr src/entry-server.js first.');
}

const outputDir = join(root, 'dist/ssr');
const outputFile = join(outputDir, 'app.html');
const metaFile = join(outputDir, 'meta.json');

const boot = {
    locale: process.env.PINOOX_SSR_LOCALE || 'fa',
    direction: process.env.PINOOX_SSR_DIRECTION || 'rtl',
    url: {
        APP: process.env.PINOOX_SSR_APP || '/',
        BASE: process.env.PINOOX_SSR_BASE || '/',
        API: process.env.PINOOX_SSR_API || '/api/v1/',
        SITE: process.env.PINOOX_SSR_SITE || '/',
        MANAGER: process.env.PINOOX_SSR_MANAGER || '/manager',
        THEME: process.env.PINOOX_SSR_THEME || '/apps/{package}/theme/{theme}/',
    },
};

const route = process.env.PINOOX_SSR_URL || '/';

const { render } = await import(pathToFileURL(serverEntry).href);
const html = await render(route, boot, boot.url.BASE);

mkdirSync(outputDir, { recursive: true });
writeFileSync(outputFile, html, 'utf8');
writeFileSync(metaFile, JSON.stringify({
    locale: boot.locale,
    direction: boot.direction,
    url: boot.url,
    route,
    builtAt: new Date().toISOString(),
}, null, 2), 'utf8');

console.log(`SSR prerender written to ${outputFile}`);
