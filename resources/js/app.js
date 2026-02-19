import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import VueApexCharts from 'vue3-apexcharts';
import App from './App.vue';
import '../css/app.css';

const routes = [
    { path: '/', component: () => import('./pages/Dashboard.vue'), name: 'dashboard' },
    { path: '/requests', component: () => import('./pages/RequestsList.vue'), name: 'requests.list' },
    { path: '/requests/:id', component: () => import('./pages/RequestDetails.vue'), name: 'requests.show' },
    { path: '/analytics', component: () => import('./pages/Analytics.vue'), name: 'analytics' },
    { path: '/alerts', component: () => import('./pages/Alerts.vue'), name: 'alerts' },
    { path: '/keys', component: () => import('./pages/ApiKeys.vue'), name: 'keys' },
    { path: '/settings', component: () => import('./pages/Settings.vue'), name: 'settings' },
];

const router = createRouter({
    history: createWebHistory('/api-watcher'),
    routes,
});

const app = createApp(App);
app.use(router);
app.use(VueApexCharts);
app.mount('#api-watcher-app');
