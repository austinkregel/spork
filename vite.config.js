import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import path from 'path';
import tailwindcss from '@tailwindcss/vite'
import Markdown from 'unplugin-vue-markdown/vite'
import Components from 'unplugin-vue-components/vite'
import hljs from 'highlight.js' // https://highlightjs.org
import { spoiler } from './resources/js/Support/markdown-it-spoiler'
import { full as emoji } from 'markdown-it-emoji'

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [ 'resources/css/app.css','resources/js/app.js'],
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
        Markdown({
            markdownItSetup(md) {
                md.use(emoji).use(spoiler)
            },
        }),
        Components({
            include: [/\.vue$/, /\.vue\?vue/, /\.md$/],
            resolvers: [
            ],
        }),
        viteStaticCopy({
            targets: [
                {
                    src: path.resolve(__dirname, 'resources/sounds'),
                    dest: path.resolve(__dirname,'public'), // 2️⃣
                },
            ],
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    server: {
        watch: {
            ignored: ["**/vendor/**", "**/node_modules/**"],
        },
    },
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: path.resolve(__dirname, 'resources/js/tests/setup.ts'),
        include: ['resources/js/**/*.{test,spec}.{js,ts,tsx}'],
    },
});
