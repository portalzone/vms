import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        vue(),
    ],
    build: {
        rollupOptions: {
            input: "vms-frontend/vue-project/src/main.js",
        },
    },
});
