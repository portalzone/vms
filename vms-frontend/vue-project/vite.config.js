<<<<<<< HEAD
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue()],

  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
      '@components': fileURLToPath(new URL('./src/components', import.meta.url)),
      '@stores': fileURLToPath(new URL('./src/stores', import.meta.url)),
      '@views': fileURLToPath(new URL('./src/views', import.meta.url)),
      '@assets': fileURLToPath(new URL('./src/assets', import.meta.url)),
      '@utils': fileURLToPath(new URL('./src/utils', import.meta.url)),
=======
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
>>>>>>> 7cc979e21fbbe7813ac5d04ac9f3117fd672d574
    },
  },
})
