// src/main.js

// ✅ Import Tailwind and your global styles
import './assets/main.css'

// ✅ Vue core
import { createApp } from 'vue'
import { createPinia } from 'pinia'

// ✅ App & router
import App from './App.vue'
import router from './router'

// ✅ Axios global setup
import './axios'

// ✅ Create and mount app
const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
