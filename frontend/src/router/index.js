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
    path: '/login',
    name: 'Login',
    component: () => import('../pages/Login.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../pages/Register.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: () => import('../pages/ForgotPassword.vue'),
    meta: { requiresGuest: true }
  },
  {
    path: '/otp-verification',
    name: 'OtpVerification',
    component: () => import('../pages/OtpVerification.vue')
  },
  {
    path: '/magic-link-login',
    name: 'MagicLinkLogin',
    component: () => import('../pages/MagicLinkLogin.vue')
  },
  {
    path: '/settings/security',
    name: 'SecuritySettings',
    component: () => import('../pages/SecuritySettings.vue'),
    meta: { requiresAuth: true }
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
  },
  {
    path: '/privacy-policy',
    name: 'PrivacyPolicy',
    component: () => import('../pages/PrivacyPolicy.vue')
  },
  {
    path: '/terms-of-service',
    name: 'TermsOfService',
    component: () => import('../pages/TermsOfService.vue')
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('../pages/NotFound.vue')
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
import { authService } from '../services/authService'

router.beforeEach((to, from, next) => {
  // Admin routes
  if (to.meta.requiresAdmin && !adminService.isAuthenticated()) {
    next('/admin/login')
  } else if (to.meta.requiresGuestAdmin && adminService.isAuthenticated()) {
    next('/admin/dashboard')
  }
  // User auth routes
  else if (to.meta.requiresGuest && authService.isAuthenticated()) {
    next('/')
  }
  // User protected routes
  else if (to.meta.requiresAuth && !authService.isAuthenticated()) {
    next('/login')
  } else {
    next()
  }
})

export default router
