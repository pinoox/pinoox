import {fileURLToPath, URL} from 'node:url'

import {defineConfig} from 'vite'
import vue from '@vitejs/plugin-vue'
import commonjs from 'vite-plugin-commonjs'
import {babel} from "@rollup/plugin-babel";

// https://vitejs.dev/config/
export default defineConfig({
    base: './',
    build: {
        manifest: true,
        rollupOptions: {
            input: 'src/main.js',
        },
    },
    plugins: [
        vue(),
        commonjs(),
        babel(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./src', import.meta.url))
        }
    }
})
