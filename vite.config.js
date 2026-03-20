import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/edux-base.css',
                'resources/css/home-w3.css',
                'resources/css/course-lp-base.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
