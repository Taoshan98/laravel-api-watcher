import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';

export default defineConfig({
    plugins: [
        tailwindcss(),
        vue()
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        manifest: true,
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            input: 'resources/js/app.js',
            output: {
                manualChunks: {
                    'vendor-charts': ['apexcharts', 'vue3-apexcharts'],
                }
            }
        },
    },
    publicDir: false,
});
