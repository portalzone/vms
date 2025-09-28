import { defineConfig } from "vite";

import vue from "@vitejs/plugin-vue"; // 

export default defineConfig({
    // other configuration...
    plugins: [
        vue(),
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
            input: "vms-frontend/vue-project/src/main.js",
        },
    },
});
