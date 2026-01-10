import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'home',
            component: () => import('@/Pages/Shop/Home.vue'),
        },
        {
            path: '/products/:id',
            name: 'product.show',
            component: () => import('@/Pages/Shop/ProductShow.vue'),
        },
        {
            path: '/login',
            name: 'login',
            component: () => import('@/Pages/Auth/Login.vue'),
        },
        {
            path: '/register',
            name: 'register',
            component: () => import('@/Pages/Auth/Register.vue'),
        },
        {
            path: '/cart',
            name: 'cart',
            component: () => import('@/Pages/Shop/Cart.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/orders',
            name: 'orders',
            component: () => import('@/Pages/Shop/Orders.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/admin',
            name: 'admin.dashboard',
            component: () => import('@/Pages/Admin/Dashboard.vue'),
            meta: { requiresAuth: true, requiresAdmin: true },
        },
        {
            path: '/admin/products',
            name: 'admin.products',
            component: () => import('@/Pages/Admin/Products.vue'),
            meta: { requiresAuth: true, requiresAdmin: true },
        },
        {
            path: '/admin/categories',
            name: 'admin.categories',
            component: () => import('@/Pages/Admin/Categories.vue'),
            meta: { requiresAuth: true, requiresAdmin: true },
        },
        {
            path: '/admin/orders',
            name: 'admin.orders',
            component: () => import('@/Pages/Admin/Orders.vue'),
            meta: { requiresAuth: true, requiresAdmin: true },
        },
    ],
});

router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore();

    if (to.meta.requiresAuth) {
        if (!auth.isAuthenticated) {
            if (!auth.user && auth.token) {
                await auth.fetchUser();
            }

            if (!auth.isAuthenticated) {
                next({ name: 'login', query: { redirect: to.fullPath } });
                return;
            }
        }

        if (to.meta.requiresAdmin) {
            if (auth.token && (!auth.user || !auth.user.role)) {
                await auth.fetchUser();
            }

            if (!auth.isAdmin) {
                next({ name: 'home' });
                return;
            }
        }
    }

    next();
});

export default router;
