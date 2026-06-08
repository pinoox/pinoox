import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        base: './',
        build: {
            manifest: true,
            outDir: 'dist',
            rollupOptions: {
                input: ['src/main.jsx'],
            },
        },
        plugins: [react()],
        server: {
            proxy: {
                '/api': env.VITE_SERVER_URL || 'http://127.0.0.1:8000',
            },
        },
    };
});
