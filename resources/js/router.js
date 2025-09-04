import { createRouter, createWebHistory } from 'vue-router';
import TicketList from './views/TicketList.vue';

const routes = [
  { path: '/', redirect: '/tickets' },
  { path: '/tickets', component: TicketList },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
