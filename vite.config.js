import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';
import { copyFileSync, mkdirSync } from 'node:fs';
import { resolve } from 'node:path';
import laravel from 'laravel-vite-plugin';

const fontSource = fileURLToPath(new URL('node_modules/bootstrap-icons/font/fonts', import.meta.url));

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/scss/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
        {
            name: 'copy-bootstrap-icons-fonts',
            apply: 'build',
            writeBundle() {
                const outDir = resolve('public/build/assets/fonts');
                mkdirSync(outDir, { recursive: true });
                copyFileSync(resolve(fontSource, 'bootstrap-icons.woff2'), resolve(outDir, 'bootstrap-icons.woff2'));
                copyFileSync(resolve(fontSource, 'bootstrap-icons.woff'), resolve(outDir, 'bootstrap-icons.woff'));
            },
        },
    ],
    resolve: {
        alias: {
            '~bootstrap': fileURLToPath(new URL('node_modules/bootstrap', import.meta.url)),
            '~bootstrap-icons': fileURLToPath(new URL('node_modules/bootstrap-icons', import.meta.url)),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules/apexcharts')) {
                        return 'vendor-charts';
                    }
                },
            },
        },
    },
});
