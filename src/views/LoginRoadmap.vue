<template>
  <div class="login-shell">
    <div class="login-card">
      <div class="login-header">
        <div class="logo-wrap">
          <img :src="logoImg" alt="Pura Group Indonesia" class="login-logo" />
        </div>
        <div class="editor-badge">
          <span>🔐</span> Portal Editor Roadmap
        </div>
        <h1 class="login-title">PT Pura Barutama Unit Offset</h1>
        <p class="login-subtitle">
          Portal Editor Roadmap Digitalisasi
        </p>
      </div>

      <form @submit.prevent="handleLoginRoadmap" class="login-form">
        <div class="form-group mb-3">
          <label class="form-label">Username Akun</label>
          <input 
            type="text" 
            v-model="form.username" 
            class="form-control" 
            required 
            placeholder="Masukkan username (admin / divisi)..." 
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
          {{ loading ? 'Memverifikasi Database...' : 'Masuk sebagai Editor Roadmap' }}
        </button>

        <div class="back-wrap mt-3">
          <router-link to="/roadmap" class="btn-back-link">
            &larr; Kembali ke Roadmap (Mode Baca / Publik)
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
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { apiFetch, authState, initAuth, setAuthUser } from '../utils/api'
import logoImg from '../assets/puragroupindonesia_logo.jpg'
import { showWarning, showError } from '../view/modal/modalService'

const router = useRouter()
const form = ref({ username: '', password: '' })
const error = ref('')
const loading = ref(false)

const handleLoginRoadmap = async () => {
  error.value = ''
  const u = form.value.username.trim()
  const p = form.value.password.trim()

  if (!u || !p) {
    const msg = 'Silakan isi username dan password terlebih dahulu'
    error.value = msg
    showWarning(msg, { title: 'Form Belum Lengkap' })
    return
  }

  loading.value = true
  try {
    const res = await apiFetch('apiroadmap.php?action=login', {
      method: 'POST',
      body: JSON.stringify({ username: u, password: p })
    })

    if (res && res.success) {
      // Simpan auth state
      setAuthUser(res)

      // Re-sync auth session in background
      initAuth().catch(() => {})

      // Ganti rute dengan replace agar URL login tidak tertinggal di history
      router.replace('/roadmap')
    } else {
      const msg = res?.error || 'Gagal masuk editor roadmap. Periksa kembali username & password Anda.'
      error.value = msg
      showError(msg, { title: 'Gagal Masuk Editor' })
    }
  } catch (e) {
    const msg = e.message || 'Username atau password salah.'
    error.value = msg
    showError(msg, { title: 'Gagal Masuk Editor' })
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
  background: #f6f4ee;
}

.login-card {
  width: 100%;
  max-width: 440px;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
  border: 1px solid #dcd6c6;
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
  margin-bottom: 0.75rem;
}

.login-logo {
  height: 54px;
  width: auto;
  object-fit: contain;
  border-radius: 8px;
}

.editor-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: #e6f1fb;
  color: #185fa5;
  font-size: 0.76rem;
  font-weight: 700;
  padding: 0.25rem 0.65rem;
  border-radius: 999px;
  margin-bottom: 0.65rem;
}

.login-title {
  font-size: 1.45rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin: 0 0 0.45rem 0;
}

.login-subtitle {
  font-size: 0.84rem;
  color: #64748b;
  font-weight: 500;
  line-height: 1.45;
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
  color: #334155;
}

.form-control {
  width: 100%;
  height: 42px;
  padding: 0 0.85rem;
  font-size: 0.92rem;
  border: 1px solid #cbd5e1;
  border-radius: 9px;
  background: #ffffff;
  color: #0f172a;
  transition: border-color 0.15s, box-shadow 0.15s;
  box-sizing: border-box;
}

.form-control:focus {
  outline: none;
  border-color: #0f6e56;
  box-shadow: 0 0 0 3px rgba(15, 110, 86, 0.18);
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
  background: #0f6e56;
  color: #ffffff;
  border: none;
  box-shadow: 0 2px 6px rgba(15, 110, 86, 0.3);
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

.back-wrap {
  text-align: center;
  margin-top: 1rem;
}

.btn-back-link {
  color: #64748b;
  text-decoration: none;
  font-size: 0.82rem;
  font-weight: 600;
  transition: color 0.15s;
}

.btn-back-link:hover {
  color: #0f6e56;
}

.login-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding-top: 1.25rem;
  border-top: 1px solid #f1f5f9;
  font-size: 0.76rem;
  font-weight: 600;
  color: #94a3b8;
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
