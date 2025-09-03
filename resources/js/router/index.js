import { createRouter, createWebHistory } from 'vue-router';
import TicketList from '../views/TicketList.vue';
import TicketDetail from '../views/TicketDetail.vue';
import Dashboard from '../views/Dashboard.vue';

const routes = [
  { path: '/tickets', component: TicketList },
  { path: '/tickets/:id', component: TicketDetail, props: true },
  { path: '/dashboard', component: Dashboard },
  { path: '/:pathMatch(.*)*', redirect: '/tickets' },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
