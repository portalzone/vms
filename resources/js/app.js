import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router'; // Make sure this file exists
import axios from 'axios';

// Axios defaults for API
axios.defaults.baseURL = 'http://localhost:8000/api'; // Your Laravel API URL
axios.defaults.withCredentials = true;

const app = createApp(App);

app.use(router);
app.mount('#app');
