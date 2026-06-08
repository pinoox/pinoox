import { pathToFileURL } from 'node:url';

function readStdin() {
    return new Promise((resolve, reject) => {
        const chunks = [];

        process.stdin.on('data', (chunk) => chunks.push(chunk));
        process.stdin.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
        process.stdin.on('error', reject);
    });
}

const raw = await readStdin();
const input = JSON.parse(raw || '{}');
const serverEntry = input.serverEntry;

if (!serverEntry) {
    console.error('render-request: serverEntry is required');
    process.exit(1);
}

const { render } = await import(pathToFileURL(serverEntry).href);
const boot = input.boot ?? {};
const url = input.url ?? '/';
const routerBase = input.routerBase ?? boot.url?.BASE ?? '/';
const html = await render(url, boot, routerBase);

process.stdout.write(html);
