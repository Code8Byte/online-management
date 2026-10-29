<template>
  <div class="app-layout">
    <!-- Desktop Header -->
    <header v-if="authState.isAuthenticated" class="app-header no-print">
      <nav class="desktop-navbar">
        <router-link to="/" class="brand-wrapper">
          <div class="brand-icon">📦</div>
          <div>
            <div class="brand-title">Divisi Sortir</div>
            <div class="brand-subtitle">Sistem LKH &amp; OEE</div>
          </div>
        </router-link>
        
        <div class="nav-links">
          <router-link to="/" class="nav-link">
            <span>📊</span> Dashboard
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
          <router-link v-if="authState.role === 'admin'" to="/logs" class="nav-link">
            <span>📜</span> Log Aktivitas
          </router-link>
        </div>

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

    <!-- Mobile Top Bar (Android) -->
    <header v-if="authState.isAuthenticated" class="mobile-topbar no-print">
      <router-link to="/" class="brand-wrapper">
        <div class="brand-icon" style="width: 30px; height: 30px; font-size: 0.95rem;">📦</div>
        <div>
          <div class="brand-title" style="font-size: 0.95rem;">Divisi Sortir</div>
          <div class="brand-subtitle" style="font-size: 0.7rem;">LKH &amp; OEE</div>
        </div>
      </router-link>
      <div class="d-flex align-center gap-05">
        <router-link v-if="authState.role === 'admin'" to="/logs" class="btn btn-primary btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; text-decoration: none;" title="Buka Log Aktivitas Admin">
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
    <main class="container">
      <router-view />
    </main>

    <!-- Mobile Bottom Navigation Bar (Android Material Native Style) -->
    <nav v-if="authState.isAuthenticated" class="mobile-bottom-nav no-print">
      <router-link to="/" class="bottom-nav-item">
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
  </div>
</template>

<script setup>
import { authState, apiFetch } from './utils/api'
import { useRouter } from 'vue-router'

const router = useRouter()

const logout = async () => {
  if (!confirm('Apakah Anda yakin ingin keluar?')) return
  try {
    await apiFetch('auth.php?action=logout', { method: 'POST' })
    authState.value.isAuthenticated = false
    authState.value.username = ''
    authState.value.role = ''
    router.push('/login')
  } catch (e) {
    alert(e.message)
  }
}
</script>
