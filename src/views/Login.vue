<template>
  <div class="login-shell">
    <div class="login-card">
      <div class="login-header">
        <div class="logo-wrap">
          <img :src="logoImg" alt="Pura Group Indonesia" class="login-logo" />
        </div>
        <h1 class="login-title">PT Pura Barutama Unit Offset</h1>
        <p class="login-subtitle">Masuk ke Sistem Terintegrasi</p>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group mb-3">
          <label class="form-label">Username</label>
          <input 
            type="text" 
            v-model="form.username" 
            class="form-control" 
            required 
            placeholder="Masukkan username divisi atau admin..." 
            autocomplete="username"
            autofocus 
          />
        </div>

        <div class="form-group mb-3">
          <label class="form-label">Password</label>
          <input 
            type="password" 
            v-model="form.password" 
            class="form-control" 
            required 
            placeholder="Masukkan password..." 
            autocomplete="current-password"
          />
        </div>

        <div v-if="error" class="login-error">
          <span>⚠️</span> {{ error }}
        </div>

        <button type="submit" class="btn btn-primary btn-submit" :disabled="loading">
          {{ loading ? 'Memverifikasi...' : 'Masuk ke Sistem' }}
        </button>

        <div class="roadmap-link-wrap">
          <router-link to="/roadmap" class="btn-roadmap-link">
            🗺️ Lihat Roadmap Digitalisasi
          </router-link>
        </div>
      </form>

      <div class="login-footer">
        <img :src="logoImg" alt="Pura Group" class="footer-logo" />
        <span>&copy;2026 PT Pura Barutama Unit Offset. All Rights Reserved.</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { apiFetch, authState, initAuth, setAuthUser } from '../utils/api'
import logoImg from '../assets/puragroupindonesia_logo.jpg'
import { showWarning, showError } from '../view/modal/modalService'

const router = useRouter()
const route = useRoute()
const form = ref({ username: '', password: '' })
const error = ref('')
const loading = ref(false)

// Jika user sudah terautentikasi (misal menekan Back ke login), langsung kembalikan ke dashboard
onMounted(() => {
  if (authState.value.isAuthenticated) {
    const div = (authState.value.divisi || authState.value.role || '').toLowerCase()
    const target = ['ppic', 'cetak', 'completing', 'cabut', 'lipat'].includes(div) ? `/${div}` : '/sortir'
    router.replace(target)
  }
})

const handleLogin = async () => {
  error.value = ''
  const u = form.value.username.trim()
  const p = form.value.password.trim()

  if (!u || !p) {
    const msg = 'Silakan isi username dan password terlebih dahulu.'
    error.value = msg
    showWarning(msg, { title: 'Form Belum Lengkap' })
    return
  }

  loading.value = true
  try {
    const res = await apiFetch('auth.php?action=login', {
      method: 'POST',
      body: JSON.stringify({ username: u, password: p })
    })

    if (res && res.success) {
      // Simpan auth state secara permanen di session & localStorage
      setAuthUser(res)

      // Re-fetch csrf in background
      initAuth().catch(() => {})

      // Ganti rute dengan replace (bukan push) agar URL login tidak tertinggal di history back browser
      if (route.query.redirect === 'roadmap' || route.query.redirect === 'https://roadmap.puraoffset.my.id') {
        router.replace('/roadmap')
        return
      }

      const div = (res.divisi || res.role || '').toLowerCase()
      if (div === 'ppic') {
        router.replace('/ppic')
      } else if (div === 'cetak') {
        router.replace('/cetak')
      } else if (div === 'completing') {
        router.replace('/completing')
      } else if (div === 'cabut') {
        router.replace('/cabut')
      } else if (div === 'lipat') {
        router.replace('/lipat')
      } else {
        router.replace('/sortir')
      }
    } else {
      const msg = res?.error || 'Gagal masuk. Periksa kembali username dan password Anda.'
      error.value = msg
      showError(msg, { title: 'Gagal Masuk' })
    }
  } catch (e) {
    const msg = e.message || 'Username atau password salah. Silakan coba lagi.'
    error.value = msg
    showError(msg, { title: 'Gagal Masuk' })
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-shell {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  background: var(--c-bg, #f8fafc);
}

.login-card {
  width: 100%;
  max-width: 420px;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.07), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
  border: 1px solid var(--c-line, #e2e8f0);
  padding: 2.25rem 2rem;
}

.login-header {
  text-align: center;
  margin-bottom: 1.75rem;
}

.logo-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}

.login-logo {
  height: 58px;
  width: auto;
  object-fit: contain;
  border-radius: 8px;
}

.login-title {
  font-size: 1.45rem;
  font-weight: 800;
  color: var(--c-ink, #0f172a);
  letter-spacing: -0.02em;
  margin: 0 0 0.35rem 0;
}

.login-subtitle {
  font-size: 0.88rem;
  color: var(--c-muted, #64748b);
  font-weight: 500;
  margin: 0;
}

.login-form {
  margin-bottom: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin-bottom: 1.1rem;
}

.form-label {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--c-text, #334155);
}

.form-control {
  width: 100%;
  height: 42px;
  padding: 0 0.85rem;
  font-size: 0.92rem;
  border: 1px solid var(--c-line, #cbd5e1);
  border-radius: 9px;
  background: #ffffff;
  color: var(--c-ink, #0f172a);
  transition: border-color 0.15s, box-shadow 0.15s;
  box-sizing: border-box;
}

.form-control:focus {
  outline: none;
  border-color: var(--c-brand, #4f46e5);
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
}

.login-error {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
  padding: 0.65rem 0.85rem;
  border-radius: 8px;
  font-size: 0.82rem;
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 1.1rem;
}

.btn-submit {
  width: 100%;
  height: 44px;
  font-size: 0.95rem;
  font-weight: 600;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  background: var(--c-brand, #4f46e5);
  color: #ffffff;
  border: none;
  box-shadow: 0 2px 6px rgba(79, 70, 229, 0.3);
  transition: opacity 0.15s, transform 0.1s;
}

.btn-submit:hover:not(:disabled) {
  opacity: 0.95;
}

.btn-submit:active:not(:disabled) {
  transform: scale(0.99);
}

.btn-submit:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.roadmap-link-wrap {
  margin-top: 1rem;
  margin-bottom: 0.25rem;
  text-align: center;
}

.btn-roadmap-link {
  font-size: 0.82rem;
  font-weight: 600;
  color: #475569;
  text-decoration: none;
  padding: 0.4rem 0.85rem;
  border-radius: 8px;
  background: #f1f5f9;
  display: inline-block;
  transition: all 0.15s ease;
}

.btn-roadmap-link:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.login-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding-top: 1.25rem;
  border-top: 1px solid var(--c-line, #f1f5f9);
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--c-muted, #94a3b8);
}

.footer-logo {
  height: 18px;
  width: auto;
  border-radius: 3px;
  object-fit: contain;
}

@media (max-width: 480px) {
  .login-card {
    padding: 1.75rem 1.25rem;
  }
}
</style>
