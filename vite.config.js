import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import inject from '@rollup/plugin-inject';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/custom/showMore.js',
                'resources/js/custom/carousel.js',
                'resources/js/custom/criteriaForm.js',
                'resources/js/custom/trailerModal.js',
            ],
            refresh: true,
        }),
        inject({
            include: ['**/*.js'],
            $: 'jquery',
            jQuery: 'jquery',
        }),
    ],
});
