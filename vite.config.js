// vite.config.js

import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue"; // <--- Import the plugin

export default defineConfig({
    // other configuration...
    plugins: [
        vue(), // <--- Add the plugin here
    ],

    build: {
        rollupOptions: {
            input: {
                // Change this line to your new entry point path
                plugins: [
                    vue(), // <--- Add the plugin here
                ],
                main: "vms-frontend/vue-project/src/main.js",
            },
        },
    },
});
