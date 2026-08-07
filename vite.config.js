import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
    ],
    resolve: {
        alias: {
            'vue': 'vue/dist/vue.esm-bundler.js',
            '@/lib': path.resolve(__dirname, './resources/views/lib'),
            '@/components/ui': path.resolve(__dirname, './resources/views/components/ui'),
            '@/composables': path.resolve(__dirname, './resources/js/composables'),
            '@/components': path.resolve(__dirname, './resources/js/components'),
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    build: {
        chunkSizeWarningLimit: 500,
        rollupOptions: {
            output: {
                manualChunks: {
                    'vue-vendor': ['vue', 'vue-router', '@inertiajs/vue3'],
                    'ui-vendor': ['reka-ui', '@vueuse/core', 'lucide-vue-next'],
                    'table-vendor': ['@tanstack/vue-table'],
                },
            },
        },
    },
});
