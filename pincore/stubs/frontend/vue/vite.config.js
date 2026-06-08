import { defineConfig, loadEnv } from 'vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        base: './',
        build: {
            manifest: true,
            outDir: 'dist',
            rollupOptions: {
                input: ['src/main.js'],
            },
        },
        plugins: [vue()],
        resolve: {
            alias: {
                '@': fileURLToPath(new URL('./src', import.meta.url)),
            },
        },
        server: {
            proxy: {
                '/api': env.VITE_SERVER_URL || 'http://127.0.0.1:8000',
            },
        },
    };
});
