import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import inject from '@rollup/plugin-inject';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/js/custom/showmore.js',
                'resources/js/custom/customOwlCarousel.js',
                'resources/js/custom/customForm.js',
                'resources/js/custom/customModal.js',
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
