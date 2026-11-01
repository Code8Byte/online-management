<template>
  <div class="app-layout">
    <!-- Desktop Header (Hanya tampil saat user sudah login di dashboard sistem) -->
    <header v-if="showNavbar" class="app-header no-print">
      <nav class="desktop-navbar">
        <router-link to="/" class="brand-wrapper">
          <img :src="logoImg" alt="Pura Group" class="brand-logo-img" />
          <div>
            <div class="brand-title">{{ currentHeaderTitle }}</div>
            <div class="brand-subtitle">{{ currentHeaderSubtitle }}</div>
          </div>
        </router-link>
        
        <!-- Navigation Links saat login -->
        <div class="nav-links">
          <router-link :to="dashboardRoute" class="nav-link">
            <span>📊</span> Dashboard
          </router-link>
          
          <!-- Admin Division Quick Selector -->
          <div v-if="authState.role === 'admin'" class="admin-div-selector">
            <select :value="$route.path" @change="$router.push($event.target.value)" class="nav-div-select" title="Pindah Pratinjau Dashboard Divisi">
              <option value="/sortir">Divisi Sortir</option>
              <option value="/cetak">Divisi Cetak</option>
              <option value="/completing">Divisi Completing</option>
              <option value="/cabut">Divisi Cabut</option>
              <option value="/lipat">Divisi Lipat</option>
              <option value="/ppic">PPIC &amp; Scheduling</option>
            </select>
          </div>

          <router-link v-if="authState.role === 'admin' || authState.role === 'ppic' || authState.divisi === 'ppic'" to="/ppic" class="nav-link">
            <span>📅</span> PPIC &amp; Jadwal
          </router-link>

          <router-link v-if="authState.role !== 'operator'" to="/progress" class="nav-link">
            <span>📈</span> Progress PP
          </router-link>
          <router-link to="/lkh" class="nav-link">
            <span>📋</span> Data LKH
          </router-link>
          <router-link v-if="authState.role !== 'viewer'" to="/lkh/baru" class="nav-link">
            <span>✍️</span> Isi LKH
          </router-link>
          <router-link v-if="authState.role === 'admin'" to="/master" class="nav-link">
            <span>⚙️</span> Master Data
          </router-link>
          <router-link v-if="authState.role === 'admin'" to="/users" class="nav-link">
            <span>👥</span> Account Management
          </router-link>
          <router-link v-if="authState.role === 'admin'" to="/logs" class="nav-link">
            <span>📜</span> Log Aktivitas
          </router-link>
          <router-link to="/roadmap" class="nav-link" title="Buka Roadmap Digitalisasi">
            <span>🗺️</span> Roadmap
          </router-link>
        </div>

        <!-- User Controls / Auth Status -->
        <div class="d-flex align-center gap-1">
          <router-link to="/profile" class="user-badge" style="text-decoration: none; cursor: pointer; display: inline-flex; align-items: center;" title="Buka Profil & Ganti Password">
            <span>👤</span> {{ authState.username || 'Pengguna' }}
            <span class="badge" :class="authState.role === 'admin' ? 'badge-primary' : (authState.role === 'operator' ? 'badge-warning' : 'badge-info')" style="margin-left: 6px; font-size: 0.72rem; text-transform: uppercase;">
              {{ authState.role }}
            </span>
          </router-link>
          <router-link to="/profile" class="btn btn-secondary btn-sm" title="Profil & Ganti Password" style="padding: 0.35rem 0.65rem;">
            ⚙️ Profil
          </router-link>
          <button @click="logout" class="btn btn-danger btn-sm" title="Keluar">
            Logout
          </button>
        </div>
      </nav>
    </header>

    <!-- Mobile Top Bar (Android) (Hanya tampil saat user sudah login di dashboard sistem) -->
    <header v-if="showNavbar" class="mobile-topbar no-print">
      <router-link to="/" class="brand-wrapper">
        <img :src="logoImg" alt="Pura Group" class="brand-logo-img-mobile" />
        <div>
          <div class="brand-title" style="font-size: 0.95rem;">{{ currentHeaderTitle }}</div>
          <div class="brand-subtitle" style="font-size: 0.7rem;">{{ currentHeaderSubtitle }}</div>
        </div>
      </router-link>
      <div class="d-flex align-center gap-05">
        <router-link v-if="authState.role === 'admin'" to="/users" class="btn btn-primary btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; text-decoration: none;" title="Account Management">
          👥 Akun
        </router-link>
        <router-link to="/roadmap" class="btn btn-secondary btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; text-decoration: none;" title="Buka Roadmap Proyek">
          🗺️ Roadmap
        </router-link>
        <router-link v-if="authState.role === 'admin'" to="/logs" class="btn btn-secondary btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; text-decoration: none;" title="Buka Log Aktivitas Admin">
          📜 Log
        </router-link>
        <router-link to="/profile" class="user-badge" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; text-decoration: none;" title="Buka Profil & Ganti Password">
          👤 {{ authState.username }}
        </router-link>
        <button @click="logout" class="btn btn-danger btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;" title="Logout">
          Keluar
        </button>
      </div>
    </header>

    <!-- Main Content -->
    <main :class="showNavbar ? 'container' : 'plain-main'">
      <router-view />
    </main>

    <!-- Footer PT Pura Barutama Unit Offset (Hanya pada halaman yang memiliki navbar) -->
    <footer v-if="showNavbar" class="app-footer no-print">
      <div class="footer-inner">
        <img :src="logoImg" alt="Pura Group" class="footer-logo" />
        <span>&copy;2026 PT Pura Barutama Unit Offset. All Rights Reserved.</span>
      </div>
    </footer>

    <!-- Mobile Bottom Navigation Bar (Android Material Native Style) -->
    <nav v-if="showNavbar" class="mobile-bottom-nav no-print">
      <router-link :to="dashboardRoute" class="bottom-nav-item">
        <span class="bottom-nav-icon">📊</span>
        <span>Dashboard</span>
      </router-link>
      <router-link v-if="authState.role !== 'operator'" to="/progress" class="bottom-nav-item">
        <span class="bottom-nav-icon">📈</span>
        <span>Progress</span>
      </router-link>
      <router-link to="/lkh" class="bottom-nav-item">
        <span class="bottom-nav-icon">📋</span>
        <span>Data LKH</span>
      </router-link>
      <router-link v-if="authState.role !== 'viewer'" to="/lkh/baru" class="bottom-nav-item">
        <span class="bottom-nav-icon">✍️</span>
        <span>Isi LKH</span>
      </router-link>
      <router-link v-if="authState.role === 'admin'" to="/logs" class="bottom-nav-item">
        <span class="bottom-nav-icon">📜</span>
        <span>Log</span>
      </router-link>
      <router-link to="/profile" class="bottom-nav-item">
        <span class="bottom-nav-icon">👤</span>
        <span>Profil</span>
      </router-link>
    </nav>

    <!-- Global Alert / Confirm / Logout Modal Box -->
    <AlertModal />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { authState, apiFetch, clearAuthUser } from './utils/api'
import { useRouter, useRoute } from 'vue-router'
import logoImg from './assets/puragroupindonesia_logo.jpg'
import AlertModal from './view/modal/AlertModal.vue'
import { showConfirm, showAlert, showError } from './view/modal/modalService'

const router = useRouter()
const route = useRoute()

const currentHeaderTitle = computed(() => {
  const p = route.path
  if (p === '/ppic') return 'PPIC & Penjadwalan Kapasitas'
  if (p === '/cetak') return 'Divisi Cetak'
  if (p === '/completing') return 'Divisi Completing'
  if (p === '/cabut') return 'Divisi Cabut'
  if (p === '/lipat') return 'Divisi Lipat'
  if (p === '/sortir') return 'Divisi Sortir'
  if (p === '/roadmap') return 'Roadmap Digitalisasi'
  if (p === '/progress' || p === '/progress-data') return 'Progress PP'
  if (p.startsWith('/lkh')) return 'Sistem LKH'
  if (p === '/master') return 'Master Data'
  if (p === '/users' || p === '/account-management') return 'Manajemen Akun'
  if (p === '/logs') return 'Log Aktivitas'
  if (p === '/profile') return 'Profil Akun'

  const d = (authState.value?.divisi || authState.value?.role || '').toLowerCase()
  if (d === 'ppic') return 'PPIC & Penjadwalan Kapasitas'
  if (d === 'cetak') return 'Divisi Cetak'
  if (d === 'completing') return 'Divisi Completing'
  if (d === 'cabut') return 'Divisi Cabut'
  if (d === 'lipat') return 'Divisi Lipat'
  if (d === 'sortir') return 'Divisi Sortir'
  return 'Unit Offset'
})

const currentHeaderSubtitle = computed(() => {
  return 'PT Pura Barutama Unit Offset'
})

const showNavbar = computed(() => {
  // Hanya tampilkan navbar jika pengguna sudah login DAN tidak berada di halaman roadmap atau login
  if (!authState.value?.isAuthenticated) return false
  const p = route.path
  if (['/roadmap', '/login', '/loginroadmap', '/roadmap/login'].includes(p)) return false
  return true
})

const dashboardRoute = computed(() => {
  const d = (authState.value?.divisi || authState.value?.role || '').toLowerCase()
  if (d === 'ppic') return '/ppic'
  if (d === 'cetak') return '/cetak'
  if (d === 'completing') return '/completing'
  if (d === 'cabut') return '/cabut'
  if (d === 'lipat') return '/lipat'
  if (d === 'sortir' || d === 'operator') return '/sortir'
  return '/sortir'
})

const logout = async () => {
  const confirmed = await showConfirm(
    'Apakah Anda yakin ingin keluar dari sistem PT Pura Barutama Unit Offset?',
    {
      title: 'Konfirmasi Logout',
      confirmText: 'Ya, Logout',
      cancelText: 'Batal',
      type: 'confirm'
    }
  )
  if (!confirmed) return

  try {
    await apiFetch('auth.php?action=logout', { method: 'POST' })
  } catch (e) {
  } finally {
    clearAuthUser()
    router.replace('/login')
  }
}
</script>

<style>
.plain-main {
  width: 100%;
  margin: 0;
  padding: 0;
}
.brand-logo-img {
  height: 38px;
  width: auto;
  object-fit: contain;
  border-radius: 6px;
  background: #ffffff;
  padding: 2px;
}
.brand-logo-img-mobile {
  height: 30px;
  width: auto;
  object-fit: contain;
  border-radius: 4px;
  background: #ffffff;
  padding: 2px;
}
.app-footer {
  margin-top: auto;
  padding: 16px 20px;
  background: #ffffff;
  border-top: 1px solid var(--c-line, #e2e8f0);
}
.footer-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #64748b;
}
.nav-div-select {
  padding: 0.35rem 0.65rem;
  font-size: 0.78rem;
  font-weight: 600;
  border-radius: 6px;
  border: 1px solid #cbd5e1;
  background: #f8fafc;
  color: #334155;
  cursor: pointer;
  outline: none;
}
.nav-div-select:focus {
  border-color: #4f46e5;
}
.footer-logo {
  height: 24px;
  width: auto;
  object-fit: contain;
  border-radius: 4px;
}
@media (max-width: 768px) {
  .app-footer {
    padding-bottom: 78px;
  }
}
</style>
