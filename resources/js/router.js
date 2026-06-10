import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from './views/Dashboard.vue';
import MachineDetail from './views/MachineDetail.vue';
import Machines from './views/Machines.vue';
import Schedules from './views/Schedules.vue';

const routes = [
    { path: '/', component: Dashboard, name: 'dashboard' },
    { path: '/machine/:id', component: MachineDetail, name: 'machine-detail', props: true },
    // { path: '/machine/:id/report', component: MachineReport, name: 'machine-report', props: true },
    { path: '/machines', component: Machines, name: 'machines' },
    { path: '/schedules', component: Schedules, name: 'schedules' },
];

export const router = createRouter({
    history: createWebHistory(),
    routes
});
