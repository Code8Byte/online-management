<template>
  <div style="max-width: 680px; margin: 0 auto; padding-bottom: 2rem;">
    <!-- Page Header -->
    <div class="d-flex justify-between align-center mb-1 flex-wrap gap-1">
      <div>
        <h2 style="margin: 0; font-size: 1.45rem; font-weight: 800; color: var(--text);">👤 Profil Pengguna</h2>
        <small class="text-muted">Kelola informasi akun dan kata sandi Anda</small>
      </div>
      <button @click="$router.back()" class="btn btn-secondary btn-sm">
        ← Kembali
      </button>
    </div>

    <!-- User Information Card -->
    <div class="card mb-1">
      <div class="d-flex align-center gap-1">
        <div style="width: 58px; height: 58px; border-radius: 50%; background: linear-gradient(135deg, #eef2ff, #e0e7ff); color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; flex-shrink: 0; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.15);">
          👤
        </div>
        <div style="flex: 1;">
          <div class="d-flex align-center gap-05 flex-wrap">
            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #312e81;">
              {{ authState.username || 'Pengguna' }}
            </h3>
            <span class="badge" :class="roleBadgeClass" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; padding: 0.25rem 0.6rem;">
              {{ authState.role }}
            </span>
            <span class="badge" style="background: #ecfdf5; color: #065f46; font-size: 0.72rem; font-weight: 600;">
              ● Aktif
            </span>
          </div>
          <p class="text-muted" style="margin: 4px 0 0 0; font-size: 0.85rem;">
            Hak Akses: <b>{{ roleDescription }}</b>
          </p>
        </div>
      </div>
    </div>

    <!-- Change Password Card -->
    <div class="card">
      <div class="card-title d-flex align-center gap-05" style="border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
        <span style="font-size: 1.2rem;">🔒</span>
        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text);">Ganti Password</h3>
      </div>

      <!-- Success Alert -->
      <div v-if="successMsg" class="badge badge-success mb-1" style="display: block; text-align: left; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.88rem; line-height: 1.4; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
        <div class="d-flex justify-between align-center">
          <div>
            <b>✅ Berhasil!</b> {{ successMsg }}
          </div>
          <button type="button" @click="successMsg = ''" style="background: none; border: none; font-size: 1rem; cursor: pointer; color: #047857;">✕</button>
        </div>
      </div>

      <!-- Error Alert -->
      <div v-if="errorMsg" class="badge badge-danger mb-1" style="display: block; text-align: left; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.88rem; line-height: 1.4; background: #fff1f2; color: #be123c; border: 1px solid #fecdd3;">
        <div class="d-flex justify-between align-center">
          <div>
            <b>⚠️ Perhatian:</b> {{ errorMsg }}
          </div>
          <button type="button" @click="errorMsg = ''" style="background: none; border: none; font-size: 1rem; cursor: pointer; color: #be123c;">✕</button>
        </div>
      </div>

      <form @submit.prevent="handleChangePassword">
        <!-- Password Lama -->
        <div class="form-group mb-1">
          <label style="font-weight: 600; font-size: 0.88rem; color: #3a3f5c;">
            Password Saat Ini (Lama) <span class="text-danger">*</span>
          </label>
          <div style="position: relative;">
            <input
              :type="showOld ? 'text' : 'password'"
              v-model="form.old_password"
              class="form-control"
              required
              placeholder="Masukkan password saat ini..."
              style="padding-right: 42px;"
            />
            <button
              type="button"
              @click="showOld = !showOld"
              style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1rem; color: var(--text-muted); padding: 4px;"
              title="Tampilkan / Sembunyikan"
            >
              {{ showOld ? '🙈' : '👁️' }}
            </button>
          </div>
        </div>

        <!-- Password Baru -->
        <div class="form-group mb-1">
          <label style="font-weight: 600; font-size: 0.88rem; color: #3a3f5c;">
            Password Baru <span class="text-danger">*</span>
          </label>
          <div style="position: relative;">
            <input
              :type="showNew ? 'text' : 'password'"
              v-model="form.new_password"
              class="form-control"
              required
              minlength="3"
              placeholder="Masukkan password baru minimal 3 karakter..."
              style="padding-right: 42px;"
            />
            <button
              type="button"
              @click="showNew = !showNew"
              style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1rem; color: var(--text-muted); padding: 4px;"
              title="Tampilkan / Sembunyikan"
            >
              {{ showNew ? '🙈' : '👁️' }}
            </button>
          </div>
          <small class="text-muted" style="font-size: 0.78rem;">
            Minimal 3 karakter. Disarankan kombinasi huruf dan angka yang mudah diingat.
          </small>
        </div>

        <!-- Konfirmasi Password Baru -->
        <div class="form-group mb-1">
          <label style="font-weight: 600; font-size: 0.88rem; color: #3a3f5c;">
            Konfirmasi Password Baru <span class="text-danger">*</span>
          </label>
          <div style="position: relative;">
            <input
              :type="showConfirm ? 'text' : 'password'"
              v-model="form.confirm_password"
              class="form-control"
              required
              placeholder="Ketik ulang password baru..."
              style="padding-right: 42px;"
            />
            <button
              type="button"
              @click="showConfirm = !showConfirm"
              style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1rem; color: var(--text-muted); padding: 4px;"
              title="Tampilkan / Sembunyikan"
            >
              {{ showConfirm ? '🙈' : '👁️' }}
            </button>
          </div>
          <!-- Match Indicator -->
          <div v-if="form.confirm_password" style="font-size: 0.8rem; margin-top: 4px;">
            <span v-if="form.new_password === form.confirm_password" style="color: #059669; font-weight: 600;">
              ✓ Password baru cocok
            </span>
            <span v-else style="color: #e11d48; font-weight: 600;">
              ✕ Konfirmasi password tidak cocok
            </span>
          </div>
        </div>

        <div class="d-flex align-center gap-1 mt-2">
          <button type="submit" class="btn btn-primary" :disabled="loading || (form.new_password !== form.confirm_password)">
            {{ loading ? 'Menyimpan...' : '💾 Simpan Password Baru' }}
          </button>
          <button type="button" @click="resetForm" class="btn btn-secondary" :disabled="loading">
            Reset
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { authState, apiFetch } from '../utils/api'

const form = ref({
  old_password: '',
  new_password: '',
  confirm_password: ''
})

const showOld = ref(false)
const showNew = ref(false)
const showConfirm = ref(false)

const loading = ref(false)
const successMsg = ref('')
const errorMsg = ref('')

const roleBadgeClass = computed(() => {
  if (authState.value.role === 'admin') return 'badge-primary'
  if (authState.value.role === 'operator') return 'badge-warning'
  return 'badge-info'
})

const roleDescription = computed(() => {
  if (authState.value.role === 'admin') {
    return 'Administrator (Akses penuh seluruh modul, Master Data, dan Export Laporan)'
  }
  if (authState.value.role === 'operator') {
    return 'Operator (Pengisian LKH, Rekap Data LKH, Dashboard & Export CC)'
  }
  return 'Viewer (Hanya Pratinjau / Monitoring Data & Dashboard LKH)'
})

const resetForm = () => {
  form.value.old_password = ''
  form.value.new_password = ''
  form.value.confirm_password = ''
  errorMsg.value = ''
}

const handleChangePassword = async () => {
  errorMsg.value = ''
  successMsg.value = ''

  if (form.value.new_password !== form.value.confirm_password) {
    errorMsg.value = 'Password baru dan konfirmasi password tidak cocok.'
    return
  }

  if (form.value.new_password.length < 3) {
    errorMsg.value = 'Password baru minimal harus 3 karakter.'
    return
  }

  loading.value = true
  try {
    const res = await apiFetch('auth.php?action=change_password', {
      method: 'POST',
      body: JSON.stringify({
        old_password: form.value.old_password,
        new_password: form.value.new_password
      })
    })

    successMsg.value = res.message || 'Password berhasil diperbarui! Silakan gunakan password baru pada sesi login selanjutnya.'
    resetForm()
  } catch (err) {
    errorMsg.value = err.message || 'Gagal mengubah password. Pastikan password lama Anda benar.'
  } finally {
    loading.value = false
  }
}
</script>
