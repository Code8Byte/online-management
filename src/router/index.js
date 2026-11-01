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

// Cetak Views
import CetakLkhList from '../views/cetak/LkhList.vue'
import CetakLkhForm from '../views/cetak/LkhForm.vue'
import CetakMasterData from '../views/cetak/MasterData.vue'
import CetakProgressPP from '../views/cetak/ProgressPP.vue'

// Completing Views
import CompletingLkhList from '../views/completing/LkhList.vue'
import CompletingLkhForm from '../views/completing/LkhForm.vue'
import CompletingMasterData from '../views/completing/MasterData.vue'
import CompletingProgressPP from '../views/completing/ProgressPP.vue'

// Cabut Views
import CabutLkhList from '../views/cabut/LkhList.vue'
import CabutLkhForm from '../views/cabut/LkhForm.vue'
import CabutMasterData from '../views/cabut/MasterData.vue'
import CabutProgressPP from '../views/cabut/ProgressPP.vue'

// Lipat Views
import LipatLkhList from '../views/lipat/LkhList.vue'
import LipatLkhForm from '../views/lipat/LkhForm.vue'
import LipatMasterData from '../views/lipat/MasterData.vue'
import LipatProgressPP from '../views/lipat/ProgressPP.vue'

// Sortir Views
import SortirLkhList from '../views/sortir/LkhList.vue'
import SortirLkhForm from '../views/sortir/LkhForm.vue'
import SortirMasterData from '../views/sortir/MasterData.vue'
import SortirProgressPP from '../views/sortir/ProgressPP.vue'

// PPIC Views
import PpicLkhList from '../views/ppic/LkhList.vue'
import PpicLkhForm from '../views/ppic/LkhForm.vue'
import PpicMasterData from '../views/ppic/MasterData.vue'
import PpicJadwal from '../views/ppic/Jadwal.vue'
import PpicInputPp from '../views/ppic/InputPp.vue'

// Shared & Operational Views
import ProgressPP from '../views/ProgressPP.vue'
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

  // Division Dashboards (Diisolasi sesuai divisi masing-masing)
  { path: '/ppic', component: PpicDashboard, meta: { requiresAuth: true } },
  { path: '/cetak', component: CetakDashboard, meta: { requiresAuth: true } },
  { path: '/completing', component: CompletingDashboard, meta: { requiresAuth: true } },
  { path: '/cabut', component: CabutDashboard, meta: { requiresAuth: true } },
  { path: '/lipat', component: LipatDashboard, meta: { requiresAuth: true } },
  { path: '/sortir', component: SortirDashboard, meta: { requiresAuth: true } },

  // Cetak Division Routes
  { path: '/cetak/lkh', component: CetakLkhList, meta: { requiresAuth: true } },
  { path: '/cetak/lkh/baru', component: CetakLkhForm, meta: { requiresAuth: true } },
  { path: '/cetak/master', component: CetakMasterData, meta: { requiresAuth: true } },
  { path: '/cetak/progress', component: CetakProgressPP, meta: { requiresAuth: true } },

  // Completing Division Routes
  { path: '/completing/lkh', component: CompletingLkhList, meta: { requiresAuth: true } },
  { path: '/completing/lkh/baru', component: CompletingLkhForm, meta: { requiresAuth: true } },
  { path: '/completing/master', component: CompletingMasterData, meta: { requiresAuth: true } },
  { path: '/completing/progress', component: CompletingProgressPP, meta: { requiresAuth: true } },

  // Cabut Division Routes
  { path: '/cabut/lkh', component: CabutLkhList, meta: { requiresAuth: true } },
  { path: '/cabut/lkh/baru', component: CabutLkhForm, meta: { requiresAuth: true } },
  { path: '/cabut/master', component: CabutMasterData, meta: { requiresAuth: true } },
  { path: '/cabut/progress', component: CabutProgressPP, meta: { requiresAuth: true } },

  // Lipat Division Routes
  { path: '/lipat/lkh', component: LipatLkhList, meta: { requiresAuth: true } },
  { path: '/lipat/lkh/baru', component: LipatLkhForm, meta: { requiresAuth: true } },
  { path: '/lipat/master', component: LipatMasterData, meta: { requiresAuth: true } },
  { path: '/lipat/progress', component: LipatProgressPP, meta: { requiresAuth: true } },

  // Sortir Division Routes
  { path: '/sortir/lkh', component: SortirLkhList, meta: { requiresAuth: true } },
  { path: '/sortir/lkh/baru', component: SortirLkhForm, meta: { requiresAuth: true } },
  { path: '/sortir/master', component: SortirMasterData, meta: { requiresAuth: true } },
  { path: '/sortir/progress', component: SortirProgressPP, meta: { requiresAuth: true } },

  // PPIC Division Routes
  { path: '/ppic/jadwal', component: PpicJadwal, meta: { requiresAuth: true } },
  { path: '/jadwal', redirect: '/ppic/jadwal', meta: { requiresAuth: true } },
  { path: '/ppic/input-pp', component: PpicInputPp, meta: { requiresAuth: true } },
  { path: '/ppic/lkh', component: PpicLkhList, meta: { requiresAuth: true } },
  { path: '/ppic/lkh/baru', redirect: '/ppic/input-pp', meta: { requiresAuth: true } },
  { path: '/ppic/master', component: PpicMasterData, meta: { requiresAuth: true } },

  // Shared Routes (Progress PP dapat dilihat semua departemen)
  { path: '/progress', component: ProgressPP, meta: { requiresAuth: true } },
  { path: '/progress-data', component: ProgressPP, meta: { requiresAuth: true } },

  // Fallback routes untuk Generic /lkh, /lkh/baru, /master (Di-handle di beforeEach)
  { path: '/lkh', component: SortirLkhList, meta: { requiresAuth: true } },
  { path: '/lkh/baru', component: SortirLkhForm, meta: { requiresAuth: true } },
  { path: '/lkh/edit/:id', component: SortirLkhForm, meta: { requiresAuth: true } },
  { path: '/master', component: SortirMasterData, meta: { requiresAuth: true } },

  // Profil, Akun & Log
  { path: '/profile', component: Profile, meta: { requiresAuth: true } },
  { path: '/users', component: AccountManagement, meta: { requiresAuth: true } },
  { path: '/account-management', component: AccountManagement, meta: { requiresAuth: true } },
  { path: '/logs', component: ActivityLog, meta: { requiresAuth: true } },

  // Root
  { path: '/', component: SortirDashboard, meta: { requiresAuth: true } }
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
    next({ path: target, replace: true })
    return
  }

  // 4. Pembatasan akses dashboard antar divisi:
  // User hanya boleh mengakses dashboard divisi masing-masing (misal cetak hanya bisa akses /cetak)
  const divisionDashboards = ['/cetak', '/completing', '/cabut', '/lipat', '/sortir', '/ppic']
  if (divisionDashboards.includes(to.path) && authState.value.isAuthenticated) {
    const allowed = getDivisionDashboardRoute(authState.value)
    if (to.path !== allowed) {
      next({ path: allowed, replace: true })
      return
    }
  }

  // 5. Redirect rute generik /lkh, /lkh/baru, /master ke divisi pengguna aktif
  if (authState.value.isAuthenticated) {
    const div = (authState.value?.divisi || authState.value?.role || 'sortir').toLowerCase()
    const validDiv = ['cetak', 'completing', 'cabut', 'lipat', 'sortir', 'ppic'].includes(div) ? div : 'sortir'
    
    if (to.path === '/lkh') {
      next({ path: `/${validDiv}/lkh`, replace: true })
      return
    }
    if (to.path === '/lkh/baru') {
      if (validDiv === 'ppic') {
        next({ path: '/ppic/input-pp', replace: true })
        return
      }
      next({ path: `/${validDiv}/lkh/baru`, replace: true })
      return
    }
    if (to.path === '/master') {
      next({ path: `/${validDiv}/master`, replace: true })
      return
    }
  }

  next()
})

router.afterEach((to) => {
  const titles = {
    '/ppic': 'PPIC & Penjadwalan Kapasitas • PT Pura Barutama Unit Offset',
    '/ppic/jadwal': 'Jadwal & Gantt Chart Kapasitas • PT Pura Barutama Unit Offset',
    '/ppic/input-pp': 'Form Input PP Baru • PT Pura Barutama Unit Offset',
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
    '/profile': 'Profil Akun • PT Pura Barutama Unit Offset'
  }
  document.title = titles[to.path] || 'PT Pura Barutama Unit Offset'
})

export default router
