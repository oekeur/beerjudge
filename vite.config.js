import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/judging.js', 'resources/css/filament/admin/theme.css'],
            refresh: true,
            fonts: process.env.VITE_SKIP_FONTS === '1' ? [] : [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
        VitePWA({
            registerType: 'autoUpdate',
            manifest: false,
            injectRegister: null,
            workbox: {
                globPatterns: ['**/*.{js,css,woff2}'],
                navigateFallback: null,
            },
        }),
    ],
    server: {
        host: '0.0.0.0',
        hmr: { host: 'localhost' },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
