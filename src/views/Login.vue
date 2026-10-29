<template>
  <div class="login-shell">
    <div class="login-panel">
      <!-- Brand / showcase side (desktop only) -->
      <div class="login-aside">
        <div class="login-aside-glow"></div>
        <div class="login-aside-content">
          <div class="login-aside-icon">📦</div>
          <h1>Divisi Sortir</h1>
          <p>Sistem Laporan Kerja Harian &amp; OEE untuk memantau produksi, downtime, dan kualitas sortir secara real-time.</p>
          <ul class="login-aside-list">
            <li><span>📊</span> Dashboard OEE &amp; analitik produksi</li>
            <li><span>📋</span> Pencatatan LKH harian per shift</li>
            <li><span>📈</span> Pemantauan progress Job Order</li>
          </ul>
        </div>
      </div>

      <!-- Form side -->
      <div class="login-form-side">
        <div class="login-form-wrap">
          <div class="login-mobile-brand">
            <div class="brand-icon" style="width: 48px; height: 48px; font-size: 1.5rem;">📦</div>
            <div>
              <h2>Divisi Sortir</h2>
              <div class="text-muted" style="font-size: 0.85rem;">Sistem LKH &amp; OEE</div>
            </div>
          </div>

          <div class="login-form-heading">
            <h2>Masuk ke Sistem</h2>
            <p class="text-muted">Silakan masukkan kredensial Anda untuk melanjutkan.</p>
          </div>

          <form @submit.prevent="handleLogin">
            <div class="form-group">
              <label>Username</label>
              <input type="text" v-model="form.username" class="form-control" required placeholder="Masukkan username..." autofocus />
            </div>
            <div class="form-group mb-2">
              <label>Password</label>
              <input type="password" v-model="form.password" class="form-control" required placeholder="Masukkan password..." />
            </div>

            <div v-if="error" class="badge badge-danger mb-2" style="display: block; text-align: left; padding: 0.6rem 0.75rem; border-radius: 8px;">
              ⚠️ {{ error }}
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem; font-size: 1rem;" :disabled="loading">
              {{ loading ? 'Memverifikasi...' : 'Masuk ke Sistem' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { apiFetch, authState, initAuth } from '../utils/api'

const router = useRouter()
const form = ref({ username: '', password: '' })
const error = ref('')
const loading = ref(false)

const handleLogin = async () => {
  error.value = ''
  loading.value = true
  try {
    const res = await apiFetch('auth.php?action=login', {
      method: 'POST',
      body: JSON.stringify(form.value)
    })

    // fetch CSRF token again after login to ensure session is updated
    await initAuth()

    if (authState.value.isAuthenticated) {
      if (authState.value.role === 'operator') {
        router.push('/lkh/baru')
      } else {
        router.push('/')
      }
    }
  } catch (e) {
    error.value = e.message
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
  padding: 1.25rem;
}

.login-panel {
  width: 100%;
  max-width: 920px;
  display: grid;
  grid-template-columns: 1.05fr 1fr;
  background: #ffffff;
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--border);
  overflow: hidden;
  min-height: 560px;
}

/* ---------- Showcase side ---------- */
.login-aside {
  position: relative;
  background: linear-gradient(160deg, #4f46e5 0%, #4338ca 55%, #6d28d9 100%);
  color: #fff;
  padding: 2.5rem 2.25rem;
  display: flex;
  align-items: center;
  overflow: hidden;
}

.login-aside-glow {
  position: absolute;
  width: 420px;
  height: 420px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.16), transparent 70%);
  top: -140px;
  right: -140px;
}

.login-aside-content { position: relative; z-index: 1; }

.login-aside-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-md);
  background: rgba(255, 255, 255, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.7rem;
  margin-bottom: 1.25rem;
}

.login-aside h1 {
  font-size: 1.7rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  margin-bottom: 0.6rem;
}

.login-aside p {
  font-size: 0.92rem;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.86);
  margin-bottom: 1.5rem;
  max-width: 340px;
}

.login-aside-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.login-aside-list li {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.95);
}

.login-aside-list li span {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border-radius: 9px;
  background: rgba(255, 255, 255, 0.16);
  flex-shrink: 0;
}

/* ---------- Form side ---------- */
.login-form-side {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2.5rem 2.25rem;
}

.login-form-wrap { width: 100%; max-width: 340px; }

.login-mobile-brand { display: none; }

.login-form-heading { margin-bottom: 1.5rem; }
.login-form-heading h2 {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--text);
  letter-spacing: -0.01em;
  margin-bottom: 0.3rem;
}

@media (max-width: 860px) {
  .login-panel {
    grid-template-columns: 1fr;
    max-width: 420px;
    min-height: 0;
  }
  .login-aside { display: none; }
  .login-form-side { padding: 2rem 1.5rem; }
  .login-mobile-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
  }
  .login-mobile-brand h2 { font-size: 1.15rem; font-weight: 800; color: var(--text); }
}
</style>
