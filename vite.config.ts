import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const appPath = env.APP_URL ? new URL(env.APP_URL).pathname : '/';
    const basePath = (env.VITE_BASE_PATH || appPath || '/').replace(/\/+$/, '') || '/';

    return {
        // Laravel serves the Vite manifest below /build; dynamic imports must
        // use the same prefix when the application is mounted as a subpath.
        base: `${basePath === '/' ? '' : basePath}/build/`,
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.ts'],
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
            tailwindcss(),
        ],
        resolve: {
            alias: {
                '@': path.resolve(import.meta.dirname, './resources/js'),
            },
        },
    };
});
