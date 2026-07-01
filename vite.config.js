import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        react(),
        VitePWA({
            registerType: 'autoUpdate',
            manifest: {
                name: 'The Velvet Studio',
                short_name: 'Velvet',
                theme_color: '#050505',
                background_color: '#050505',
                display: 'standalone',
                start_url: '/',
            },
        }),
    ],
});
