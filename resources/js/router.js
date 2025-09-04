import { createRouter, createWebHistory } from 'vue-router';
import TicketList from './views/TicketList.vue';
import Dashboard from './views/Dashboard.vue';

const routes = [
  { path: '/', redirect: '/tickets' },
  { path: '/tickets', component: TicketList },
  { path: '/dashboard', component: Dashboard },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
