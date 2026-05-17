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
                'resources/js/custom/showmore.js',
                'resources/js/custom/customSwiper.js',
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
