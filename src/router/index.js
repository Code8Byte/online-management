import { createRouter, createWebHashHistory } from 'vue-router'
import { authState } from '../utils/api'

import Login from '../views/Login.vue'
import LoginRoadmap from '../views/LoginRoadmap.vue'
import Roadmap from '../views/Roadmap.vue'

// Division Dashboards
import CetakDashboard from '../views/cetak/Dashboard.vue'
import CompletingDashboard from '../views/completing/Dashboard.vue'
import CabutDashboard from '../views/cabut/Dashboard.vue'
import LipatDashboard from '../views/lipat/Dashboard.vue'
import SortirDashboard from '../views/sortir/Dashboard.vue'
import PpicDashboard from '../views/ppic/Dashboard.vue'

// Operational Views (Lintas Divisi & Master)
import ProgressPP from '../views/ProgressPP.vue'
import CetakProgressPP from '../views/cetak/ProgressPP.vue'
import CompletingProgressPP from '../views/completing/ProgressPP.vue'
import CabutProgressPP from '../views/cabut/ProgressPP.vue'
import LipatProgressPP from '../views/lipat/ProgressPP.vue'
import SortirProgressPP from '../views/sortir/ProgressPP.vue'
import LkhForm from '../views/sortir/LkhForm.vue'
import LkhList from '../views/sortir/LkhList.vue'
import MasterData from '../views/sortir/MasterData.vue'
import Profile from '../views/sortir/Profile.vue'
import ActivityLog from '../views/sortir/ActivityLog.vue'
import AccountManagement from '../views/sortir/AccountManagement.vue'

// Helper menentukan dashboard tujuan berdasarkan divisi akun
export function getDivisionDashboardRoute(user) {
  const d = (user?.divisi || user?.role || '').toLowerCase()
  if (d === 'ppic') return '/ppic'
  if (d === 'cetak') return '/cetak'
  if (d === 'completing') return '/completing'
  if (d === 'cabut') return '/cabut'
  if (d === 'lipat') return '/lipat'
  if (d === 'sortir' || d === 'operator') return '/sortir'
  return '/sortir'
}

const routes = [
  // Public & Auth Routes
  { path: '/login', component: Login, meta: { guest: true } },
  { path: '/roadmap/login', component: LoginRoadmap },
  { path: '/loginroadmap', component: LoginRoadmap },
  { path: '/roadmap', component: Roadmap },

  // Division Dashboards & PPIC
  { path: '/ppic', component: PpicDashboard, meta: { requiresAuth: true } },
  { path: '/cetak', component: CetakDashboard, meta: { requiresAuth: true } },
  { path: '/completing', component: CompletingDashboard, meta: { requiresAuth: true } },
  { path: '/cabut', component: CabutDashboard, meta: { requiresAuth: true } },
  { path: '/lipat', component: LipatDashboard, meta: { requiresAuth: true } },
  { path: '/sortir', component: SortirDashboard, meta: { requiresAuth: true } },

  // Root redirect placeholder
  { path: '/', component: SortirDashboard, meta: { requiresAuth: true } },

  // Operational & Management Routes
  { path: '/progress', component: ProgressPP, meta: { requiresAuth: true } },
  { path: '/progress-data', component: ProgressPP, meta: { requiresAuth: true } },
  { path: '/cetak/progress', component: CetakProgressPP, meta: { requiresAuth: true } },
  { path: '/completing/progress', component: CompletingProgressPP, meta: { requiresAuth: true } },
  { path: '/cabut/progress', component: CabutProgressPP, meta: { requiresAuth: true } },
  { path: '/lipat/progress', component: LipatProgressPP, meta: { requiresAuth: true } },
  { path: '/sortir/progress', component: SortirProgressPP, meta: { requiresAuth: true } },
  { path: '/lkh/baru', component: LkhForm, meta: { requiresAuth: true, allowedRoles: ['admin', 'operator', 'sortir', 'cetak', 'completing', 'cabut', 'lipat'] } },
  { path: '/lkh/edit/:id', component: LkhForm, meta: { requiresAuth: true, allowedRoles: ['admin', 'operator', 'sortir', 'cetak', 'completing', 'cabut', 'lipat'] } },
  { path: '/lkh', component: LkhList, meta: { requiresAuth: true } },
  { path: '/master', component: MasterData, meta: { requiresAuth: true, allowedRoles: ['admin'] } },
  { path: '/users', component: AccountManagement, meta: { requiresAuth: true, allowedRoles: ['admin'] } },
  { path: '/account-management', component: AccountManagement, meta: { requiresAuth: true, allowedRoles: ['admin'] } },
  { path: '/logs', component: ActivityLog, meta: { requiresAuth: true, allowedRoles: ['admin'] } },
  { path: '/profile', component: Profile, meta: { requiresAuth: true } }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  // 1. Guard rute yang membutuhkan login
  if (to.meta.requiresAuth && !authState.value.isAuthenticated) {
    next('/login')
    return
  }

  // 2. Jika user sudah login mengakses halaman login (misal via tombol Back browser)
  if (to.meta.guest && authState.value.isAuthenticated) {
    next({ path: getDivisionDashboardRoute(authState.value), replace: true })
    return
  }

  // 3. Jika user mengakses root '/', arahkan otomatis ke dashboard divisinya
  if (to.path === '/' && authState.value.isAuthenticated) {
    const target = getDivisionDashboardRoute(authState.value)
    if (target !== '/') {
      next({ path: target, replace: true })
      return
    }
  }

  // 4. Periksa role jika ada pembatasan
  if (to.meta.allowedRoles && !to.meta.allowedRoles.includes(authState.value.role)) {
    if (authState.value.role === 'viewer') {
      next('/lkh')
    } else {
      next(getDivisionDashboardRoute(authState.value))
    }
    return
  }

  next()
})

router.afterEach((to) => {
  const titles = {
    '/ppic': 'PPIC & Penjadwalan Kapasitas • PT Pura Barutama Unit Offset',
    '/cetak': 'Divisi Cetak • PT Pura Barutama Unit Offset',
    '/completing': 'Divisi Completing • PT Pura Barutama Unit Offset',
    '/cabut': 'Divisi Cabut • PT Pura Barutama Unit Offset',
    '/lipat': 'Divisi Lipat • PT Pura Barutama Unit Offset',
    '/sortir': 'Divisi Sortir • PT Pura Barutama Unit Offset',
    '/roadmap': 'Roadmap Digitalisasi Pura Offset • PT Pura Barutama Unit Offset',
    '/roadmap/login': 'Login Editor Roadmap • PT Pura Barutama Unit Offset',
    '/loginroadmap': 'Login Editor Roadmap • PT Pura Barutama Unit Offset',
    '/login': 'Login • PT Pura Barutama Unit Offset',
    '/progress': 'Progress PP • PT Pura Barutama Unit Offset',
    '/lkh': 'Data LKH • PT Pura Barutama Unit Offset',
    '/lkh/baru': 'Form LKH Baru • PT Pura Barutama Unit Offset',
    '/master': 'Master Data • PT Pura Barutama Unit Offset',
    '/users': 'Account Management • PT Pura Barutama Unit Offset',
    '/logs': 'Log Aktivitas • PT Pura Barutama Unit Offset',
    '/profile': 'Profil Akun • PT Pura Barutama Unit Offset'
  }
  document.title = titles[to.path] || 'PT Pura Barutama Unit Offset'
})

export default router
