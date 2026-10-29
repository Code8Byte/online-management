import { createRouter, createWebHashHistory } from 'vue-router'
import { authState } from '../utils/api'

import Login from '../views/Login.vue'
import Dashboard from '../views/Dashboard.vue'
import ProgressPP from '../views/ProgressPP.vue'
import LkhForm from '../views/LkhForm.vue'
import LkhList from '../views/LkhList.vue'
import MasterData from '../views/MasterData.vue'
import Profile from '../views/Profile.vue'
import ActivityLog from '../views/ActivityLog.vue'
import AccountManagement from '../views/AccountManagement.vue'

const routes = [
  { path: '/login', component: Login, meta: { guest: true } },
  { path: '/', component: Dashboard, meta: { requiresAuth: true } },
  { path: '/progress', component: ProgressPP, meta: { requiresAuth: true, allowedRoles: ['admin', 'viewer'] } },
  { path: '/progress-data', component: ProgressPP, meta: { requiresAuth: true, allowedRoles: ['admin', 'viewer'] } },
  { path: '/lkh/baru', component: LkhForm, meta: { requiresAuth: true, allowedRoles: ['admin', 'operator'] } },
  { path: '/lkh/edit/:id', component: LkhForm, meta: { requiresAuth: true, allowedRoles: ['admin', 'operator'] } },
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
  if (to.meta.requiresAuth && !authState.value.isAuthenticated) {
    next('/login')
  } else if (to.meta.guest && authState.value.isAuthenticated) {
    if (authState.value.role === 'operator') {
      next('/lkh/baru')
    } else {
      next('/')
    }
  } else if (to.meta.allowedRoles && !to.meta.allowedRoles.includes(authState.value.role)) {
    if (authState.value.role === 'viewer') {
      next('/lkh')
    } else if (authState.value.role === 'operator') {
      next('/lkh/baru')
    } else {
      next('/')
    }
  } else {
    next()
  }
})

export default router
