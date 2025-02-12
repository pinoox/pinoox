import {fileURLToPath, URL} from 'node:url'

import {defineConfig, loadEnv} from 'vite'
import vue from '@vitejs/plugin-vue'
import sassGlobImports from 'vite-plugin-sass-glob-import';
import Components from 'unplugin-vue-components/vite';
import commonjs from 'vite-plugin-commonjs'
import {babel} from "@rollup/plugin-babel";
import tailwindcss from '@tailwindcss/vite'

export default defineConfig(({command, mode}) => {
    const env = loadEnv(mode, process.cwd(), '')

    return {
        base: './',
        build: {
            manifest: true,
            rollupOptions: {
                input: ['src/main.js'], // Entry point
                manualChunks(id) {
                    // Separate chunks for vendor and plugins
                    if (id.includes('vendor')) return 'plugins';
                    if (id.includes('node_modules')) return 'vendor';
                }
            },
        },
        plugins: [
            vue(),
            sassGlobImports(),
            commonjs(),
            babel({babelHelpers: 'bundled'}),
            tailwindcss(),
            Components({
                dirs: ['src/views/components'],
            })
        ],
        resolve: {
            alias: {
                '~': fileURLToPath(new URL('./', import.meta.url)),
                '@': fileURLToPath(new URL('./src', import.meta.url)),
                '@api': fileURLToPath(new URL('./src/api', import.meta.url)),
                '@assets': fileURLToPath(new URL('./src/assets', import.meta.url)),
                '@store': fileURLToPath(new URL('./src/store', import.meta.url)),
                '@utils': fileURLToPath(new URL('./src/utils', import.meta.url)),
                '@views': fileURLToPath(new URL('./src/views', import.meta.url)),
                '@sass': fileURLToPath(new URL('./src/assets/sass', import.meta.url)),
                '@global': fileURLToPath(new URL('./src/utils/global.js', import.meta.url)),
            }
        },
        server: {
            proxy: {
                '/api': env.VITE_SERVER_API,
                '/dist/pinoox.js': env.VITE_SERVER_API,
            },
        },
    }
});
