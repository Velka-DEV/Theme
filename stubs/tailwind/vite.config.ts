// @ts-nocheck
import { defineConfig } from 'vite'
import laravel, { refreshPaths } from 'laravel-vite-plugin'
import path from "path";
import tailwindcss from "tailwindcss";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "themes/$this->themeName/assets/sass/app.scss",
                "themes/$this->themeName/assets/js/app.js"
            ],
            refresh: [
                ...refreshPaths,
                'app/Livewire/**',
            ],
            buildDirectory: 'themes/$this->themeName',
        }),
    ],
    resolve: {
        alias: {
            '@': '/themes/$this->themeName/assets/js',
        }
    },
    css: {
        postcss: {
            plugins: [
                tailwindcss({
                    config: path.resolve(__dirname, "tailwind.config.js"),
                }),
            ],
        },
    },
});