import pinooxHot, { pinooxDevAssets, pinooxServer, pinooxVueTemplateOptions } from './vite.pinoox.mjs';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig, loadEnv } from 'vite';
import vue from '@vitejs/plugin-vue';
import commonjs from 'vite-plugin-commonjs';
import { babel } from '@rollup/plugin-babel';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        base: './',
        build: {
            manifest: true,
            rollupOptions: {
                input: 'src/main.js',
            },
        },
        plugins: [
            pinooxHot({ env }),
            pinooxDevAssets(env),
            vue(pinooxVueTemplateOptions()),
            commonjs(),
            babel({ babelHelpers: 'bundled' }),
        ],
        resolve: {
            alias: {
                '@': fileURLToPath(new URL('./src', import.meta.url)),
            },
        },
        server: pinooxServer(env),
    };
});
