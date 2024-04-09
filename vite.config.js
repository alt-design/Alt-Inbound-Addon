import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/alt-inbound-addon.js',
                'resources/js/alt-inbound-frontend.js',
                'resources/css/alt-inbound-addon.css',
                'resources/css/alt-inbound-frontend.css',
                'resources/img/alt-gradient-full.png',
                'resources/img/alt-gradient-mobile.png',
                'resources/img/stop-blocked-icon.png',
            ],
            publicDirectory: 'resources/dist',
        }),
        vue(),
    ],
});
