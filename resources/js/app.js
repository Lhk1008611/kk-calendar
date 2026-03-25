import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import {createBootstrap} from 'bootstrap-vue-next'
import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue-next/dist/bootstrap-vue-next.css'
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import { createPinia } from 'pinia';

createApp(App)
    .use(router)
    .use(createBootstrap())
    .use(Toast)
    .use(createPinia())
    .mount('#app');
