import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/scss/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
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
