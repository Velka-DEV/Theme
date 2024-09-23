// @ts-nocheck
import { defaultTheme } from "tailwindcss/defaultTheme"
import tailwindcssForms from "@tailwindcss/forms"

export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./themes/%theme_name%/**/*.{blade.php,js,vue,ts}",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Nunito", ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [
        tailwindcssForms
    ],
};