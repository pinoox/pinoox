import {fileURLToPath, URL} from 'node:url'

import {defineConfig, loadEnv} from 'vite'
import vue from '@vitejs/plugin-vue'
import commonjs from 'vite-plugin-commonjs'

const QUERY_ROUTE_PARAM = '_pnx'

export default defineConfig(({mode}) => {
    const env = loadEnv(mode, process.cwd(), '')

    return {
        base: './',
        build: {
            manifest: true,
            emptyOutDir: false,
            rollupOptions: {
                input: 'src/main.js',
            },
        },
        css: {
            devSourcemap: true,
        },
        plugins: [
            vue(),
            commonjs(),
        ],
        resolve: {
            alias: {
                '@': fileURLToPath(new URL('./src', import.meta.url)),
                '@api': fileURLToPath(new URL('./src/api', import.meta.url)),
                '@global': fileURLToPath(new URL('./src/utils/global.js', import.meta.url)),
                '@img': fileURLToPath(new URL('./src/assets/images', import.meta.url)),
                '@assets': fileURLToPath(new URL('./src/assets', import.meta.url)),
            },
        },
        server: {
            proxy: {
                [`/?${QUERY_ROUTE_PARAM}=`]: env.VITE_SERVER_URL || 'http://localhost',
                '/api/v1': env.VITE_SERVER_URL || 'http://localhost',
            },
        },
    }
})
