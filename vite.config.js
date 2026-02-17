import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import statamic from '@statamic/cms/vite-plugin';
import tailwindcss from '@tailwindcss/vite'; 

export default defineConfig({
    plugins: [
        laravel({
            hotFile: 'resources/dist/hot',
            input: [
                'resources/js/alt-inbound-addon.js',
                'resources/js/alt-inbound-frontend.js',
                'resources/css/alt-inbound-addon.css',
                'resources/css/alt-inbound-frontend.css',
            ],
            publicDirectory: 'resources/dist',
        }),
        statamic(),
        tailwindcss(),
    ],
});
