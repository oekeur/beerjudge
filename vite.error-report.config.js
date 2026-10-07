import { defineConfig } from 'vite';
import { resolve } from 'node:path';

export default defineConfig({
    publicDir: false,
    build: {
        outDir: 'public/vendor/error-report',
        emptyOutDir: true,
        lib: {
            entry: resolve(import.meta.dirname, 'resources/js/error-report-bundle.js'),
            name: 'SentryErrorReport',
            formats: ['iife'],
            fileName: () => 'bundle.js',
        },
    },
});
