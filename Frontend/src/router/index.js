import { createRouter, createWebHistory } from 'vue-router'

import Login from '@/view/login.vue'
import Otp from '@/view/otp.vue'
import Dashboard from '@/view/dashboard.vue'
import Products from '@/view/product.vue'
import BrandVue from '@/view/BrandVue.vue'
import Categoriesview from '@/view/Categoriesview.vue'
const routes = [
    {
        path: '/',
        redirect: '/dashboard'
    },

    {
        path: '/login',
        component: Login,
        meta: {
            guest: true
        }
    },

    {
        path: '/otp',
        component: Otp,
        meta: {
            guest: true
        }
    },

    {
        path: '/dashboard',
        component: Dashboard,
        meta: {
            auth: true
        }
    },

    {
        path: '/products',
        component: Products,
        meta: {
            auth: true,
            permission: 'product.view'
        }
    },
    {
        path: '/brands',
        component: BrandVue,
        meta: {
            auth: true,
            permission: 'brand.view'
        }
    },
    {
        path: '/categories',
        component: Categoriesview,
        meta: {
            auth: true,
            permission: 'category.view'
        }
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach((to) => {
    const token = localStorage.getItem('token')

    if (to.meta.auth && !token) {
        return '/login'
    }

    if (to.meta.guest && token) {
        return '/dashboard'
    }

    return true
})

export default router