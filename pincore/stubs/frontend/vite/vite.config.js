import { defineConfig, loadEnv } from 'vite';

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
        server: {
            proxy: {
                '/api': env.VITE_SERVER_URL || 'http://127.0.0.1:8000',
            },
        },
    };
});
