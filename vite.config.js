import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/map/map.css",
                "resources/css/map/calibration.css",
                "resources/css/index.css",

                "resources/js/app.js",
                "resources/js/map/map.js",
                "resources/js/map/public-map.js",
                "resources/js/map/calibration.js",
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
