import { createRouter, createWebHistory } from 'vue-router'
import Home from '../pages/Home.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home
  },
  {
    path: '/map',
    name: 'Map',
    component: () => import('../pages/Map.vue')
  },
  {
    path: '/lieux',
    name: 'Lieux',
    component: () => import('../pages/Lieux.vue')
  },
  {
    path: '/visites',
    name: 'Visites',
    component: () => import('../pages/Visites.vue')
  },
  {
    path: '/lieu/:id',
    name: 'LieuDetails',
    component: () => import('../pages/LieuDetails.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  }
})

export default router
