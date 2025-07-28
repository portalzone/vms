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

// ✅ Toastification
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'

const toastOptions = {
  position: 'top-right',
  timeout: 3000,
  closeOnClick: true,
  pauseOnFocusLoss: true,
  pauseOnHover: true,
  draggable: true,
  draggablePercent: 0.6,
  hideProgressBar: false,
  closeButton: 'button',
  icon: true,
  rtl: false
}

// ✅ Create and mount app
const app = createApp(App)
app.use(createPinia())
app.use(router)
app.use(Toast, toastOptions)
app.mount('#app')
