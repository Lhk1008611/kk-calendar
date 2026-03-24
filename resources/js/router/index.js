import { createRouter, createWebHistory } from 'vue-router';
import axios from '../axios';
import Login from '../components/auth/Login.vue';
import Register from '../components/auth/Register.vue';
import Home from '@/views/Home.vue'
// import Dashboard from '../components/Dashboard.vue';

const routes = [
    { path: '/', name: 'Home', component: Home },
    { path: '/login', component: Login },
    { path: '/register', component: Register },
    // { path: '/dashboard', component: Dashboard, meta: { requiresAuth: true } },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// 导航守卫：检查认证状态
router.beforeEach(async (to, from, next) => {
    if (to.meta.requiresAuth) {
        try {
            const response = await axios.get('/api/user');
            if (response.data) {
                next();
            } else {
                next('/login');
            }
        } catch {
            next('/login');
        }
    } else {
        next();
    }
});

export default router;
