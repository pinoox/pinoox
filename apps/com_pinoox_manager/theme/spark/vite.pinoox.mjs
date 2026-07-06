import fs from 'node:fs';
import path from 'node:path';

const DEFAULT_ENTRIES = {
    vue: ['src/main.js'],
    react: ['src/main.jsx'],
    vite: ['src/main.js'],
};

const DEFAULT_REFRESH = [
    '**/*.twig',
    'partials/**/*.twig',
    'layouts/**/*.twig',
    'views/**/*.twig',
];

/**
 * @param {Record<string, string>} env
 * @param {{ paths?: string[]|boolean }} [options]
 * @returns {string[]}
 */
export function resolveRefreshPaths(env = {}, options = {}) {
    const paths = options.paths ?? true;
    let base;

    if (paths === false) {
        base = [];
    } else if (Array.isArray(paths)) {
        base = paths;
    } else {
        base = DEFAULT_REFRESH;
    }

    const extra = String(env.VITE_DEV_REFRESH || process.env.VITE_DEV_REFRESH || '')
        .split(',')
        .map((entry) => entry.trim())
        .filter(Boolean);

    return [...base, ...extra];
}

/**
 * Hot-file path shared by Node (pinooxHot) and PHP (FrontendConfig::hotRelativePath).
 * Override with VITE_HOT_FILE in theme .env or dev.hot in frontend.config.php.
 */
export function resolveHotFile(env = {}, options = {}) {
    if (options.file) {
        return options.file;
    }

    return env.VITE_HOT_FILE || process.env.VITE_HOT_FILE || 'dist/hot';
}

/**
 * Writes theme/dist/hot (or VITE_HOT_FILE) so PHP ViteHelper injects HMR script tags.
 * @see \Pinoox\Component\Template\Frontend\FrontendConfig::hotRelativePath()
 */
export function pinooxHot(options = {}) {
    const themeRoot = process.cwd();
    const hotRelative = resolveHotFile(options.env ?? {}, options);
    const hotFilePath = path.isAbsolute(hotRelative)
        ? hotRelative
        : path.join(themeRoot, hotRelative);

    const writeHot = (server) => {
        const host = server.config.server.host;
        const hostname = host === true || host === '0.0.0.0' ? '127.0.0.1' : (host || '127.0.0.1');
        const port = server.config.server.port ?? 5173;
        const devUrl = `http://${hostname}:${port}`;

        fs.mkdirSync(path.dirname(hotFilePath), { recursive: true });
        fs.writeFileSync(hotFilePath, devUrl);
    };

    const cleanup = () => {
        if (fs.existsSync(hotFilePath)) {
            fs.unlinkSync(hotFilePath);
        }
    };

    return {
        name: 'pinoox-hot-file',
        configureServer(server) {
            writeHot(server);

            server.httpServer?.once('close', cleanup);
            process.once('SIGINT', cleanup);
            process.once('SIGTERM', cleanup);
        },
    };
}

export default pinooxHot;

/**
 * Vue SFC options so @/ assets resolve to the Vite dev server (not the PHP origin).
 */
export function pinooxVueTemplateOptions(extra = {}) {
    const { template: extraTemplate, ...rest } = extra;

    return {
        ...rest,
        template: {
            transformAssetUrls: {
                base: null,
                includeAbsolute: false,
            },
            ...(extraTemplate ?? {}),
        },
    };
}

/**
 * Rewrites root-absolute /src/... asset URLs to the Vite dev server during `vite dev`.
 * Needed when the page is served from PHP (e.g. /manager) but scripts load from Vite.
 */
export function pinooxDevAssets(env = {}) {
    return {
        name: 'pinoox-dev-assets',
        apply: 'serve',
        transform(code) {
            const devServerUrl = resolveViteDevOrigin(env);

            if (!devServerUrl || !code.includes('/src/')) {
                return null;
            }

            const rewritten = code.replace(
                /(["'`])\/src\//g,
                (match, quote, offset) => {
                    const before = code.slice(Math.max(0, offset - devServerUrl.length), offset);

                    if (before.endsWith(devServerUrl)) {
                        return match;
                    }

                    return `${quote}${devServerUrl}/src/`;
                },
            );

            return rewritten === code ? null : rewritten;
        },
    };
}

/**
 * Full-page reload when Twig templates or app PHP (Flow, routes, controllers) change.
 *
 * @param {string[]|boolean} paths  Glob paths relative to theme root, or true for defaults
 * @param {Record<string, string>} [env]  loadEnv() result; merges VITE_DEV_REFRESH from process env
 */
export function pinooxRefresh(paths = true, env = {}) {
    const mergedEnv = { ...process.env, ...env };
    const watchGlobs = resolveRefreshPaths(mergedEnv, { paths: paths === false ? false : (Array.isArray(paths) ? paths : true) });

    return {
        name: 'pinoox-refresh',
        configureServer(server) {
            if (watchGlobs.length === 0) {
                return;
            }

            for (const pattern of watchGlobs) {
                server.watcher.add(pattern);
            }

            const shouldReload = (file) => {
                const normalized = file.replace(/\\/g, '/');

                return watchGlobs.some((pattern) => matchGlob(normalized, pattern));
            };

            server.watcher.on('change', (file) => {
                if (shouldReload(file)) {
                    server.ws.send({ type: 'full-reload', path: '*' });
                }
            });
        },
    };
}

/**
 * Zero-config Vite config factory.
 *
 * @param {{
 *   env?: Record<string, string>,
 *   stack?: 'vue'|'react'|'vite'|string,
 *   entries?: string[],
 *   refresh?: string[]|boolean,
 *   plugins?: import('vite').PluginOption[],
 *   resolve?: import('vite').UserConfig['resolve'],
 *   build?: import('vite').BuildOptions,
 *   server?: import('vite').ServerOptions,
 * }} [options]
 */
export function createPinooxViteConfig(options = {}) {
    const env = options.env ?? {};
    const stack = options.stack ?? 'vite';
    const entries = options.entries?.length
        ? options.entries
        : (DEFAULT_ENTRIES[stack] ?? DEFAULT_ENTRIES.vite);

    const refresh = options.refresh ?? true;
    const refreshPaths = resolveRefreshPaths(env, {
        paths: refresh === false ? false : (Array.isArray(refresh) ? refresh : true),
    });

    const plugins = [
        pinooxHot({ env }),
        pinooxDevAssets(env),
        ...(refreshPaths.length > 0 ? [pinooxRefresh(refresh, env)] : []),
        ...(options.plugins ?? []),
    ];

    const server = {
        ...pinooxServer(env),
        ...(options.server ?? {}),
    };

    return {
        base: './',
        build: {
            manifest: true,
            outDir: 'dist',
            rollupOptions: {
                input: entries,
            },
            ...(options.build ?? {}),
        },
        plugins,
        resolve: options.resolve,
        server,
    };
}

/**
 * Vite dev-server block from theme .env (VITE_DEV_PORT, VITE_SERVER_URL, VITE_DEV_PROXY).
 *
 * @param {Record<string, string>} env  loadEnv() result
 * @param {{ serverUrl?: string, port?: number, proxy?: string[], host?: boolean, strictPort?: boolean }} [options]
 */
export function pinooxServer(env = {}, options = {}) {
    const serverUrl = env.VITE_SERVER_URL || options.serverUrl || 'http://127.0.0.1:8000';
    const port = Number(env.VITE_DEV_PORT || options.port || 5173);
    const phpOrigin = parseOrigin(serverUrl);
    const viteOrigin = resolveViteDevOrigin(env, port, options);
    const prefixes = resolveProxyPrefixes(env, options, serverUrl);
    const proxy = {};

    for (const prefix of prefixes) {
        proxy[prefix] = { target: phpOrigin, changeOrigin: true };
    }

    return {
        port,
        host: options.host ?? true,
        strictPort: options.strictPort ?? true,
        origin: viteOrigin,
        proxy,
        printUrls: options.printUrls ?? false,
    };
}

function resolveViteDevOrigin(env = {}, port = 5173, options = {}) {
    const fromEnv = env.VITE_DEV_SERVER || process.env.VITE_DEV_SERVER || options.viteOrigin;

    if (fromEnv) {
        return String(fromEnv).replace(/\/$/, '');
    }

    const host = options.host ?? true;
    const hostname = host === true || host === '0.0.0.0' ? '127.0.0.1' : (host || '127.0.0.1');

    return `http://${hostname}:${port}`;
}

function parseOrigin(serverUrl) {
    try {
        return new URL(serverUrl).origin;
    } catch {
        return 'http://127.0.0.1:8000';
    }
}

function resolveProxyPrefixes(env, options, serverUrl) {
    if (Array.isArray(options.proxy) && options.proxy.length > 0) {
        return options.proxy;
    }

    const fromEnv = String(env.VITE_DEV_PROXY || '')
        .split(',')
        .map((entry) => entry.trim())
        .filter(Boolean);

    if (fromEnv.length > 0) {
        return fromEnv;
    }

    try {
        const mountPath = new URL(serverUrl).pathname.replace(/\/$/, '');

        if (mountPath && mountPath !== '/') {
            return [mountPath];
        }
    } catch {
        // ignore
    }

    return [];
}

function matchGlob(filePath, pattern) {
    const regex = globToRegExp(pattern);

    return regex.test(filePath) || regex.test(path.basename(filePath));
}

function globToRegExp(glob) {
    const escaped = glob
        .replace(/\\/g, '/')
        .replace(/[.+^${}()|[\]\\]/g, '\\$&')
        .replace(/\*\*/g, '§§')
        .replace(/\*/g, '[^/]*')
        .replace(/§§/g, '.*')
        .replace(/\?/g, '.');

    return new RegExp(`(^|/)${escaped}$`);
}
