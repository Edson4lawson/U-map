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
  },
  {
    path: '/chat',
    name: 'Chat',
    component: () => import('../pages/Chat.vue')
  },
  {
    path: '/admin/login',
    name: 'AdminLogin',
    component: () => import('../pages/AdminLogin.vue'),
    meta: { requiresGuestAdmin: true }
  },
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: () => import('../pages/AdminDashboard.vue'),
    meta: { requiresAdmin: true }
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

import { adminService } from '../services/adminService'

router.beforeEach((to, from, next) => {
  if (to.meta.requiresAdmin && !adminService.isAuthenticated()) {
    next('/admin/login')
  } else if (to.meta.requiresGuestAdmin && adminService.isAuthenticated()) {
    next('/admin/dashboard')
  } else {
    next()
  }
})

export default router
