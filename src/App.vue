<template>
  <div class="app-layout">
    <!-- Desktop Header (Hanya tampil saat user sudah login di dashboard sistem) -->
    <header v-if="showNavbar" class="app-header no-print">
      <nav class="desktop-navbar">
        <router-link :to="dashboardRoute" class="brand-wrapper">
          <img :src="logoImg" alt="Pura Group" class="brand-logo-img" />
          <div>
            <div class="brand-title">{{ currentHeaderTitle }} <span class="badge badge-secondary" style="font-size: 0.68rem; vertical-align: middle; padding: 2px 6px;">v0.1</span></div>
            <div class="brand-subtitle">{{ currentHeaderSubtitle }}</div>
          </div>
        </router-link>
        
        <!-- Navigation Links saat login -->
        <div class="nav-links">
          <router-link :to="dashboardRoute" class="nav-link">
            <span>📊</span> Dashboard
          </router-link>

          <!-- Menu Jadwal (Khusus PPIC, nama ringkas 'Jadwal') -->
          <router-link v-if="userDivisi === 'ppic'" to="/ppic/jadwal" class="nav-link">
            <span>📅</span> Jadwal
          </router-link>

          <!-- Progress PP (Dapat dilihat oleh semua departemen) -->
          <router-link to="/progress" class="nav-link">
            <span>📈</span> Progress PP
          </router-link>

          <!-- Data PP (Khusus PPIC) / Data LKH (Divisi Lain) -->
          <router-link v-if="userDivisi === 'ppic'" to="/ppic/pp" class="nav-link">
            <span>📋</span> Data PP
          </router-link>
          <router-link v-else :to="'/' + userDivisi + '/lkh'" class="nav-link">
            <span>📋</span> Data LKH
          </router-link>

          <!-- Form Input PP Baru (Khusus PPIC) / Isi LKH (Divisi Lain) -->
          <router-link v-if="userDivisi === 'ppic'" to="/ppic/input-pp" class="nav-link">
            <span>✍️</span> Input PP Baru
          </router-link>
          <router-link v-else :to="'/' + userDivisi + '/lkh/baru'" class="nav-link">
            <span>✍️</span> Isi LKH
          </router-link>

          <!-- Master Data spesifik divisi pengguna aktif (Kecuali PPIC) -->
          <router-link v-if="userDivisi !== 'ppic'" :to="'/' + userDivisi + '/master'" class="nav-link">
            <span>⚙️</span> Master Data
          </router-link>
        </div>

        <!-- User Controls / Auth Status -->
        <div class="d-flex align-center gap-1">
          <router-link to="/profile" class="user-badge" style="text-decoration: none; cursor: pointer; display: inline-flex; align-items: center;" title="Buka Profil & Ganti Password">
            <span>👤</span> {{ authState.username || 'Pengguna' }}
            <span class="badge badge-primary" style="margin-left: 6px; font-size: 0.72rem; text-transform: uppercase;">
              STAFF • {{ userDivisi.toUpperCase() }}
            </span>
          </router-link>
          <button @click="logout" class="btn btn-danger btn-sm" title="Keluar">
            Logout
          </button>
        </div>
      </nav>
    </header>

    <!-- Mobile Top Bar (Android) (Hanya tampil saat user sudah login di dashboard sistem) -->
    <header v-if="showNavbar" class="mobile-topbar no-print">
      <router-link :to="dashboardRoute" class="brand-wrapper">
        <img :src="logoImg" alt="Pura Group" class="brand-logo-img-mobile" />
        <div>
          <div class="brand-title" style="font-size: 0.95rem;">{{ currentHeaderTitle }} <span class="badge badge-secondary" style="font-size: 0.65rem; vertical-align: middle; padding: 1px 5px;">v0.1</span></div>
          <div class="brand-subtitle" style="font-size: 0.7rem;">{{ currentHeaderSubtitle }}</div>
        </div>
      </router-link>
      <div class="d-flex align-center gap-05">
        <router-link v-if="userDivisi === 'ppic'" to="/ppic/jadwal" class="btn btn-secondary btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; text-decoration: none;" title="Jadwal">
          📅 Jadwal
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
        <span>&copy;2026 PT Pura Barutama Unit Offset. All Rights Reserved. &bull; <b>v0.1</b></span>
      </div>
    </footer>

    <!-- Mobile Bottom Navigation Bar (Android Material Native Style) -->
    <nav v-if="showNavbar" class="mobile-bottom-nav no-print">
      <router-link :to="dashboardRoute" class="bottom-nav-item">
        <span class="bottom-nav-icon">📊</span>
        <span>Dashboard</span>
      </router-link>
      <router-link v-if="userDivisi === 'ppic'" to="/ppic/jadwal" class="bottom-nav-item">
        <span class="bottom-nav-icon">📅</span>
        <span>Jadwal</span>
      </router-link>
      <router-link to="/progress" class="bottom-nav-item">
        <span class="bottom-nav-icon">📈</span>
        <span>Progress</span>
      </router-link>
      <!-- Data PP (Khusus PPIC) / Data LKH (Divisi Lain) -->
      <router-link v-if="userDivisi === 'ppic'" to="/ppic/pp" class="bottom-nav-item">
        <span class="bottom-nav-icon">📋</span>
        <span>Data PP</span>
      </router-link>
      <router-link v-else :to="'/' + userDivisi + '/lkh'" class="bottom-nav-item">
        <span class="bottom-nav-icon">📋</span>
        <span>Data LKH</span>
      </router-link>
      <!-- Form Input PP Baru (Khusus PPIC) / Isi LKH (Divisi Lain) -->
      <router-link v-if="userDivisi === 'ppic'" to="/ppic/input-pp" class="bottom-nav-item">
        <span class="bottom-nav-icon">✍️</span>
        <span>Input PP</span>
      </router-link>
      <router-link v-else :to="'/' + userDivisi + '/lkh/baru'" class="bottom-nav-item">
        <span class="bottom-nav-icon">✍️</span>
        <span>Isi LKH</span>
      </router-link>
      <router-link v-if="userDivisi !== 'ppic'" :to="'/' + userDivisi + '/master'" class="bottom-nav-item">
        <span class="bottom-nav-icon">⚙️</span>
        <span>Master</span>
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

const userDivisi = computed(() => {
  const d = (authState.value?.divisi || authState.value?.role || 'sortir').toLowerCase()
  if (['cetak', 'completing', 'cabut', 'lipat', 'sortir', 'ppic'].includes(d)) {
    return d
  }
  return 'sortir'
})

const dashboardRoute = computed(() => {
  return '/' + userDivisi.value
})

const currentHeaderTitle = computed(() => {
  const p = route.path
  if (p === '/ppic') return 'PPIC'
  if (p === '/ppic/pp') return 'Data PP'
  if (p === '/ppic/input-pp') return 'Input PP Baru'
  if (p === '/ppic/jadwal' || p === '/jadwal') return 'Jadwal & Kapasitas'
  if (p.startsWith('/cetak')) return 'Divisi Cetak'
  if (p.startsWith('/completing')) return 'Divisi Completing'
  if (p.startsWith('/cabut')) return 'Divisi Cabut'
  if (p.startsWith('/lipat')) return 'Divisi Lipat'
  if (p.startsWith('/sortir')) return 'Divisi Sortir'
  if (p.startsWith('/ppic')) return 'PPIC'
  if (p === '/roadmap') return 'Roadmap Digitalisasi'
  if (p === '/progress' || p === '/progress-data') return 'Progress PP'
  if (p.includes('/lkh')) return 'Data LKH'
  if (p.includes('/master')) return 'Master Data'
  if (p === '/users' || p === '/account-management') return 'Manajemen Akun'
  if (p === '/logs') return 'Log Aktivitas'
  if (p === '/profile') return 'Profil Akun'

  const d = userDivisi.value
  if (d === 'ppic') return 'PPIC'
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
html,
body {
  height: 100%;
  min-height: 100%;
  margin: 0;
  padding: 0;
}

#app {
  min-height: 100vh;
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  width: 100%;
}

.app-layout {
  min-height: 100vh;
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  width: 100%;
  flex: 1 0 auto;
}

.app-layout > main {
  flex: 1 0 auto;
  display: flex;
  flex-direction: column;
  width: 100%;
}

.plain-main {
  width: 100%;
  margin: 0;
  padding: 0;
  flex: 1 0 auto;
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
  width: 100%;
  flex-shrink: 0;
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
