import {createRouter, createWebHistory} from 'vue-router';
import axios from '../axios';
import Home from '@/views/Home.vue'
import Layout from '@/layouts/Layout.vue';
import Calendar from '@/views/Calendar.vue';
import Profile from '@/views/Profile.vue';
import Manage from '@/views/Manage.vue';

const routes = [
    {
        path: '/',
        name: 'Home',
        component: Home,
        meta: {requiresGuest: true} // 未登录才能访问
    },
    {
        path: '/dashboard',
        component: Layout,
        meta: {requiresAuth: true},
        children: [
            {
                path: '',
                name: 'Dashboard',
                component: Calendar
            },
            {
                path: '/profile',
                name: 'Profile',
                component: Profile
            },
            {
                path: '/manage',
                name: 'Manage',
                component: Manage
            }
        ]
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// 路由守卫：检查登录状态
router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token');
    console.log(token);

    if (to.meta.requiresAuth && !token ) {
        next({name: 'Home'});
    } else if (to.meta.requiresGuest && token) {
        next({name: 'Dashboard'});
    } else {
        next();
    }
});

export default router;
