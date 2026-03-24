import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import {createBootstrap} from 'bootstrap-vue-next'
import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue-next/dist/bootstrap-vue-next.css'
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';

createApp(App)
    .use(router)
    .use(createBootstrap())
    .use(Toast)
    .mount('#app');
