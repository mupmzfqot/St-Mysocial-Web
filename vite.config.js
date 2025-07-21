import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import vueDevTools from 'vite-plugin-vue-devtools';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            ssr: 'resources/js/ssr.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        vueDevTools()
    ],
    server: {
        host: '127.0.0.1', // Force IPv4
        port: 5173,
        hmr: {
            host: '127.0.0.1',
        },
        cors: {
            origin: ['https://social_web.test', 'http://social_web.test'],
            credentials: true,
        },
    },
    build: {
        outDir: 'public/build',
        assetsDir: 'assets',
        manifest: true,
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
            treeshake: {
                moduleSideEffects: false,
            },
        },
        target: 'es2018',
    },
    
});
