// @ts-nocheck
import { defineConfig } from 'vite'
import laravel, { refreshPaths } from 'laravel-vite-plugin'
import path from "path";
import tailwindcss from "tailwindcss";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "themes/%theme_name%/assets/styles/app.scss",
                "themes/%theme_name%/assets/scripts/app.ts"
            ],
            refresh: [
                ...refreshPaths,
                'app/Livewire/**',
                'themes/%theme_name%/**',
            ],
            buildDirectory: 'themes/%theme_name%',
        }),
    ],
    resolve: {
        alias: {
            '@': '/themes/%theme_name%/assets/scripts',
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