import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from './composables/useAuth.js';

import Dashboard from './views/Dashboard.vue';
import MachineDetail from './views/MachineDetail.vue';
import Report from './views/Report.vue';
import Machines from './views/Machines.vue';
import Approval from './views/Approval.vue';
import Login from './views/Login.vue';
import Users from './views/Users.vue';
import Logs from './views/Logs.vue';
import Components from './views/Components.vue';

const routes = [
    { 
        path: '/login', 
        component: Login, 
        name: 'login',
        meta: { guest: true }
    },
    { 
        path: '/', 
        component: Dashboard, 
        name: 'dashboard',
        meta: { requiresAuth: true }
    },
    { 
        path: '/machine/:id', 
        component: MachineDetail, 
        name: 'machine-detail', 
        props: true,
        meta: { requiresAuth: true }
    },
    { 
        path: '/report', 
        component: Report, 
        name: 'report',
        meta: { requiresAuth: true }
    },
    { 
        path: '/machines', 
        component: Machines, 
        name: 'machines',
        meta: { requiresAuth: true }
    },
    { 
        path: '/components', 
        component: Components, 
        name: 'components',
        meta: { requiresAuth: true }
    },
    { 
        path: '/approvals', 
        component: Approval, 
        name: 'approvals',
        meta: { requiresAuth: true }
    },
    { 
        path: '/users', 
        component: Users, 
        name: 'users',
        meta: { requiresAuth: true, requiresManagerOrAdmin: true }
    },
    { 
        path: '/logs', 
        component: Logs, 
        name: 'logs',
        meta: { requiresAuth: true, requiresAdmin: true }
    },
];

export const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to, from, next) => {
    const { isAuthenticated, isManagerOrAdmin, isAdmin } = useAuth();

    if (to.meta.requiresAuth && !isAuthenticated.value) {
        return next({ name: 'login' });
    }

    if (to.meta.requiresManagerOrAdmin && !isManagerOrAdmin.value) {
        return next({ name: 'dashboard' });
    }

    if (to.meta.requiresAdmin && !isAdmin.value) {
        return next({ name: 'dashboard' });
    }

    if (to.meta.guest && isAuthenticated.value) {
        return next({ name: 'dashboard' });
    }

    next();
});
