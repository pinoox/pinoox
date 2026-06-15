import {fileURLToPath, URL} from 'node:url'

import {defineConfig, loadEnv} from 'vite'
import vue from '@vitejs/plugin-vue'
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
                input: ['src/main.js', 'src/assets/styles/app-view-error.scss'],
                output: {
                    manualChunks(id) {
                        if (id.includes('@kyvg/vue3-notification')) {
                            return undefined;
                        }
                        if (id.includes('vendor')) return 'plugins';
                        if (id.includes('node_modules')) return 'vendor';
                    },
                },
            },
        },
        plugins: [
            vue({
                template: {
                    compilerOptions: {
                        isCustomElement: (tag) => tag.startsWith('dock-'),
                    },
                },
            }),
            commonjs(),
            babel({babelHelpers: 'bundled'}),
            tailwindcss(),
            Components({
                dirs: ['src/views/components'],
            })
        ],
        resolve: {
            dedupe: ['vue', '@kyvg/vue3-notification'],
            alias: {
                '~': fileURLToPath(new URL('./', import.meta.url)),
                '@': fileURLToPath(new URL('./src', import.meta.url)),
                '@api': fileURLToPath(new URL('./src/api', import.meta.url)),
                '@assets': fileURLToPath(new URL('./src/assets', import.meta.url)),
                '@views': fileURLToPath(new URL('./src/views', import.meta.url)),
                '@global': fileURLToPath(new URL('./src/utils/global.js', import.meta.url)),
                '@utils': fileURLToPath(new URL('./src/utils', import.meta.url)),
            }
        },
        server: {
            proxy: {
              '/api': env.VITE_SERVER_URL,
            },
        },
    }
});
