import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import * as path from "node:path";

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: false,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            //'@': path.resolve(__dirname, 'resources/js'),
            find: '@', replacement: path.resolve(__dirname,'resources/js')
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                additionalData: `@import "src/styles/variables.scss";`
            }
        }
    }
});
