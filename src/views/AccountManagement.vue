<template>
  <div class="account-mgmt-wrap">
    <!-- Header -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin:0; border:none; padding:0; display:flex; align-items:center; gap:8px;">
            <span>👥</span> Account Management
          </h2>
          <small class="text-muted">
            Kelola pengguna sistem, eskalasi perubahan tingkat hak akses (Operator, Viewer, Admin), serta reset password akun.
          </small>
        </div>
        <div class="d-flex gap-05 align-center flex-wrap">
          <button class="btn btn-secondary btn-sm" @click="fetchUsers" :disabled="loading" title="Refresh data akun">
            <span>🔄</span> Refresh
          </button>
          <button class="btn btn-primary" @click="openModalCreate" style="display:inline-flex; align-items:center; gap:6px;">
            <span>➕</span> Tambah Akun Baru
          </button>
        </div>
      </div>
    </div>

    <!-- KPI / Overview Cards -->
    <div class="grid grid-4 gap-1 mb-1">
      <div class="kpi-card">
        <div class="kpi-label">Total Akun Terdaftar</div>
        <div class="kpi-value text-primary">{{ users.length }}</div>
        <small class="text-muted">Seluruh pengguna aktif</small>
      </div>

      <div class="kpi-card" style="border-top-color: #4f46e5;">
        <div class="kpi-label" style="color: #4338ca;">Administrator</div>
        <div class="kpi-value" style="color: #4338ca;">{{ countByRole('admin') }}</div>
        <small class="text-muted">Hak akses penuh sistem</small>
      </div>

      <div class="kpi-card kpi-warning">
        <div class="kpi-label" style="color: #b45309;">Operator Produksi</div>
        <div class="kpi-value text-warning">{{ countByRole('operator') }}</div>
        <small class="text-muted">Input LKH &amp; Dashboard Operator</small>
      </div>

      <div class="kpi-card" style="border-top-color: #0284c7;">
        <div class="kpi-label" style="color: #0369a1;">Viewer / Monitoring</div>
        <div class="kpi-value" style="color: #0284c7;">{{ countByRole('viewer') }}</div>
        <small class="text-muted">Pratinjau / Read-only</small>
      </div>
    </div>

    <!-- Alert Sukses / Error -->
    <div v-if="successMsg" class="badge badge-success mb-1 alert-box success-box">
      <div class="d-flex justify-between align-center">
        <div><b>✅ Berhasil:</b> {{ successMsg }}</div>
        <button type="button" @click="successMsg = ''" class="btn-close-alert">✕</button>
      </div>
    </div>

    <div v-if="errorMsg" class="badge badge-danger mb-1 alert-box error-box">
      <div class="d-flex justify-between align-center">
        <div><b>⚠️ Perhatian:</b> {{ errorMsg }}</div>
        <button type="button" @click="errorMsg = ''" class="btn-close-alert">✕</button>
      </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div class="d-flex gap-1 align-center flex-wrap" style="flex: 1; min-width: 260px;">
          <!-- Search input -->
          <div style="position: relative; flex: 1; min-width: 200px;">
            <input
              type="text"
              v-model="searchQuery"
              class="form-control"
              placeholder="🔍 Cari nama username..."
              style="padding-left: 36px;"
            />
            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 0.85rem; color: #94a3b8;">
              🔍
            </span>
          </div>

          <!-- Role Filter -->
          <div style="min-width: 170px;">
            <select v-model="filterRole" class="form-control">
              <option value="">Semua Tingkat Role</option>
              <option value="admin">Administrator (Admin)</option>
              <option value="operator">Operator Produksi</option>
              <option value="viewer">Viewer (Monitoring)</option>
            </select>
          </div>
        </div>

        <div v-if="searchQuery || filterRole" class="d-flex align-center">
          <button type="button" class="btn btn-secondary btn-sm" @click="resetFilters">
            Reset Filter
          </button>
        </div>
      </div>
    </div>

    <!-- Table Users (Desktop) & Cards (Mobile) -->
    <div class="card">
      <div class="table-responsive">
        <table class="user-table">
          <thead>
            <tr>
              <th width="50" class="text-center">#</th>
              <th>Username</th>
              <th width="150">Tingkat Hak Akses</th>
              <th>Deskripsi Izin Akses</th>
              <th width="120" class="text-center">Riwayat LKH</th>
              <th width="150">Terdaftar</th>
              <th width="240" class="text-center">Aksi / Eskalasi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="text-center text-muted" style="padding: 2.5rem;">
                <div class="d-flex align-center justify-center gap-05">
                  <span>⏳</span> Sedang memuat daftar akun...
                </div>
              </td>
            </tr>

            <tr v-else-if="filteredUsers.length === 0">
              <td colspan="7" class="text-center text-muted" style="padding: 2.5rem;">
                Tidak ada akun pengguna yang sesuai dengan pencarian atau filter.
              </td>
            </tr>

            <tr v-else v-for="(u, idx) in filteredUsers" :key="u.id" :class="{ 'highlight-self': isSelf(u) }">
              <td class="text-center text-muted" style="font-size: 0.85rem;">{{ idx + 1 }}</td>
              <td>
                <div class="d-flex align-center gap-05">
                  <div class="user-avatar" :class="'avatar-' + u.role">
                    {{ (u.username || 'U').substring(0, 2).toUpperCase() }}
                  </div>
                  <div>
                    <b class="user-name">{{ u.username }}</b>
                    <span v-if="isSelf(u)" class="badge-self" title="Akun yang saat ini sedang Anda gunakan untuk login">
                      Akun Anda
                    </span>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge" :class="getRoleBadgeClass(u.role)" style="font-size: 0.76rem; text-transform: uppercase; font-weight: 700; padding: 0.28rem 0.65rem;">
                  {{ u.role }}
                </span>
              </td>
              <td style="font-size: 0.85rem; color: #475569;">
                <div v-if="u.role === 'admin'">
                  <b style="color: #4338ca;">Akses Penuh:</b> Master Data, Akun &amp; Eskalasi, Log Aktivitas, Edit/Hapus LKH.
                </div>
                <div v-else-if="u.role === 'operator'">
                  <b style="color: #b45309;">Operasional:</b> Input LKH Baru, Dashboard Operator, Rekap LKH &amp; Export Cost Control.
                </div>
                <div v-else>
                  <b style="color: #0369a1;">Read-only:</b> Pratinjau Dashboard OEE, Monitoring Progress PP, &amp; Pratinjau Data LKH.
                </div>
              </td>
              <td class="text-center">
                <span v-if="Number(u.total_lkh) > 0" class="badge badge-info" style="font-size: 0.8rem; font-weight: 700;">
                  {{ u.total_lkh }} LKH
                </span>
                <span v-else class="text-muted" style="font-size: 0.82rem;">0</span>
              </td>
              <td style="font-size: 0.82rem; color: #64748b;">
                {{ formatDate(u.created_at) }}
              </td>
              <td class="text-center text-nowrap">
                <div class="d-flex justify-center align-center gap-03 flex-wrap">
                  <!-- Tombol Eskalasi / Perubahan Tingkat -->
                  <button
                    type="button"
                    class="btn btn-sm btn-primary"
                    @click="openModalEscalate(u)"
                    title="Ubah tingkat hak akses (Operator, Viewer, Admin)"
                    style="padding: 0.28rem 0.6rem; font-size: 0.78rem;"
                  >
                    ⚡ Eskalasi Role
                  </button>

                  <!-- Tombol Reset Password -->
                  <button
                    type="button"
                    class="btn btn-sm btn-secondary"
                    @click="openModalResetPass(u)"
                    title="Reset password akun pengguna ini"
                    style="padding: 0.28rem 0.55rem; font-size: 0.78rem;"
                  >
                    🔑 Password
                  </button>

                  <!-- Tombol Hapus Akun -->
                  <button
                    type="button"
                    class="btn btn-sm btn-danger"
                    :disabled="isSelf(u)"
                    :title="isSelf(u) ? 'Tidak dapat menghapus akun Anda sendiri' : 'Hapus akun pengguna ini secara permanen'"
                    @click="confirmDeleteUser(u)"
                    style="padding: 0.28rem 0.5rem; font-size: 0.78rem;"
                  >
                    🗑️
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ==================== MODAL 1: ESKALASI / PERUBAHAN TINGKAT ROLE ==================== -->
    <div v-if="showModalEscalate" class="modal-overlay" @click.self="showModalEscalate = false">
      <div class="modal-content" style="max-width: 540px;">
        <div class="d-flex justify-between align-center mb-1 pb-05 border-bottom">
          <h3 style="margin: 0; font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
            <span>⚡</span> Eskalasi &amp; Perubahan Tingkat Akun
          </h3>
          <button type="button" class="btn btn-sm btn-secondary" @click="showModalEscalate = false" style="padding: 0.2rem 0.5rem;">✕</button>
        </div>

        <div class="mb-1 p-1 bg-soft rounded">
          <div class="d-flex justify-between align-center">
            <div>
              <div class="text-muted" style="font-size: 0.8rem; text-transform: uppercase; font-weight: 600;">Akun Pengguna</div>
              <b style="font-size: 1.1rem; color: #1e293b;">{{ targetUser?.username }}</b>
            </div>
            <div>
              <span class="text-muted" style="font-size: 0.8rem; margin-right: 6px;">Role Sekarang:</span>
              <span class="badge" :class="getRoleBadgeClass(targetUser?.role)" style="font-size: 0.78rem; text-transform: uppercase;">
                {{ targetUser?.role }}
              </span>
            </div>
          </div>
          <div v-if="isSelf(targetUser)" class="mt-05 p-05 bg-warning-light rounded text-warning-dark" style="font-size: 0.82rem; line-height: 1.4;">
            ⚠️ <b>Peringatan:</b> Anda sedang mengubah role akun Anda sendiri yang sedang aktif. Pastikan sistem tetap memiliki setidaknya satu Administrator aktif.
          </div>
        </div>

        <div class="form-group mb-1">
          <label style="font-weight: 700; color: #334155; margin-bottom: 0.5rem; display: block;">
            Pilih Tingkat Hak Akses Baru:
          </label>

          <!-- Option 1: Operator -->
          <label class="role-option-card" :class="{ selected: selectedRole === 'operator' }">
            <input type="radio" value="operator" v-model="selectedRole" style="margin-top: 3px;" />
            <div class="role-option-body">
              <div class="d-flex align-center gap-05 mb-02">
                <b style="color: #b45309;">Operator Produksi</b>
                <span class="badge badge-warning" style="font-size: 0.68rem;">OPERATOR</span>
              </div>
              <p class="role-option-desc">
                Diperuntukkan bagi staf / operator divisi sortir. Memiliki akses untuk pengisian Form LKH baru, rekap LKH harian, Dashboard Operator khusus pencapaian pribadinya, dan export Laporan Cost Control (CC).
              </p>
            </div>
          </label>

          <!-- Option 2: Viewer -->
          <label class="role-option-card" :class="{ selected: selectedRole === 'viewer' }">
            <input type="radio" value="viewer" v-model="selectedRole" style="margin-top: 3px;" />
            <div class="role-option-body">
              <div class="d-flex align-center gap-05 mb-02">
                <b style="color: #0369a1;">Viewer (Monitoring)</b>
                <span class="badge badge-info" style="font-size: 0.68rem;">VIEWER</span>
              </div>
              <p class="role-option-desc">
                Diperuntukkan bagi staf monitoring, supervisor, atau pihak eksternal. Mode hanya baca (read-only): dapat memantau Dashboard Analitik, grafik OEE, Progress Job Order (PP), dan rekap data LKH tanpa izin input/edit.
              </p>
            </div>
          </label>

          <!-- Option 3: Administrator -->
          <label class="role-option-card" :class="{ selected: selectedRole === 'admin' }">
            <input type="radio" value="admin" v-model="selectedRole" style="margin-top: 3px;" />
            <div class="role-option-body">
              <div class="d-flex align-center gap-05 mb-02">
                <b style="color: #4338ca;">Administrator (Tingkat Penuh)</b>
                <span class="badge badge-primary" style="font-size: 0.68rem;">ADMIN</span>
              </div>
              <p class="role-option-desc">
                Tingkat hak akses tertinggi. Memiliki kontrol penuh atas Master Data, Job Order (PP), persetujuan standar JPH (ACC), manajemen seluruh akun pengguna (eskalasi role), Log Audit, serta edit/hapus data LKH.
              </p>
            </div>
          </label>
        </div>

        <div class="d-flex justify-between align-center gap-05 pt-1 border-top">
          <button type="button" class="btn btn-secondary" @click="showModalEscalate = false" :disabled="submitting">
            Batal
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="submitEscalateRole"
            :disabled="submitting || selectedRole === targetUser?.role"
          >
            {{ submitting ? 'Menyimpan Perubahan...' : '💾 Simpan Perubahan Tingkat' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL 2: TAMBAH AKUN BARU ==================== -->
    <div v-if="showModalCreate" class="modal-overlay" @click.self="showModalCreate = false">
      <div class="modal-content" style="max-width: 480px;">
        <div class="d-flex justify-between align-center mb-1 pb-05 border-bottom">
          <h3 style="margin: 0; font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
            <span>➕</span> Tambah Akun Pengguna Baru
          </h3>
          <button type="button" class="btn btn-sm btn-secondary" @click="showModalCreate = false" style="padding: 0.2rem 0.5rem;">✕</button>
        </div>

        <form @submit.prevent="submitCreateUser">
          <div class="form-group mb-1">
            <label style="font-weight: 600;">Username <span class="text-danger">*</span></label>
            <input
              type="text"
              v-model="createForm.username"
              class="form-control"
              required
              minlength="3"
              maxlength="50"
              placeholder="Contoh: agus_sortir atau andi"
            />
            <small class="text-muted">Gunakan huruf, angka, atau underscore. Minimal 3 karakter.</small>
          </div>

          <div class="form-group mb-1">
            <label style="font-weight: 600;">Tingkat Hak Akses (Role) <span class="text-danger">*</span></label>
            <select v-model="createForm.role" class="form-control" required>
              <option value="operator">Operator Produksi (Input LKH &amp; CC)</option>
              <option value="viewer">Viewer (Hanya Monitoring / Read-only)</option>
              <option value="admin">Administrator (Akses Penuh Seluruh Sistem)</option>
            </select>
          </div>

          <div class="form-group mb-1">
            <label style="font-weight: 600;">Password Awal <span class="text-danger">*</span></label>
            <div style="position: relative;">
              <input
                :type="showCreatePass ? 'text' : 'password'"
                v-model="createForm.password"
                class="form-control"
                required
                minlength="3"
                placeholder="Password untuk login pertama kali..."
                style="padding-right: 42px;"
              />
              <button
                type="button"
                @click="showCreatePass = !showCreatePass"
                class="btn-toggle-eye"
                title="Tampilkan / Sembunyikan"
              >
                {{ showCreatePass ? '🙈' : '👁️' }}
              </button>
            </div>
            <small class="text-muted">Minimal 3 karakter.</small>
          </div>

          <div class="form-group mb-1">
            <label style="font-weight: 600;">Konfirmasi Password <span class="text-danger">*</span></label>
            <input
              type="password"
              v-model="createForm.confirm_password"
              class="form-control"
              required
              minlength="3"
              placeholder="Ketik ulang password..."
            />
            <div v-if="createForm.confirm_password" style="font-size: 0.8rem; margin-top: 4px;">
              <span v-if="createForm.password === createForm.confirm_password" style="color: #059669; font-weight: 600;">
                ✓ Password cocok
              </span>
              <span v-else style="color: #e11d48; font-weight: 600;">
                ✕ Konfirmasi password belum cocok
              </span>
            </div>
          </div>

          <div class="d-flex justify-between align-center gap-05 pt-1 border-top">
            <button type="button" class="btn btn-secondary" @click="showModalCreate = false" :disabled="submitting">
              Batal
            </button>
            <button
              type="submit"
              class="btn btn-success"
              :disabled="submitting || createForm.password !== createForm.confirm_password"
            >
              {{ submitting ? 'Menyimpan...' : '💾 Buat Akun' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ==================== MODAL 3: RESET PASSWORD AKUN ==================== -->
    <div v-if="showModalReset" class="modal-overlay" @click.self="showModalReset = false">
      <div class="modal-content" style="max-width: 460px;">
        <div class="d-flex justify-between align-center mb-1 pb-05 border-bottom">
          <h3 style="margin: 0; font-size: 1.15rem; display: flex; align-items: center; gap: 8px;">
            <span>🔑</span> Reset Password Pengguna
          </h3>
          <button type="button" class="btn btn-sm btn-secondary" @click="showModalReset = false" style="padding: 0.2rem 0.5rem;">✕</button>
        </div>

        <div class="mb-1 p-08 bg-soft rounded">
          <small class="text-muted d-block text-uppercase" style="font-size: 0.75rem; font-weight: 700;">Pengguna Terpilih</small>
          <b style="font-size: 1.05rem; color: #0f172a;">{{ targetUser?.username }}</b>
          <span class="badge ml-05" :class="getRoleBadgeClass(targetUser?.role)" style="font-size: 0.72rem; text-transform: uppercase;">
            {{ targetUser?.role }}
          </span>
        </div>

        <form @submit.prevent="submitResetPassword">
          <div class="form-group mb-1">
            <label style="font-weight: 600;">Password Baru <span class="text-danger">*</span></label>
            <div style="position: relative;">
              <input
                :type="showResetPass ? 'text' : 'password'"
                v-model="resetForm.new_password"
                class="form-control"
                required
                minlength="3"
                placeholder="Masukkan password baru..."
                style="padding-right: 42px;"
              />
              <button
                type="button"
                @click="showResetPass = !showResetPass"
                class="btn-toggle-eye"
                title="Tampilkan / Sembunyikan"
              >
                {{ showResetPass ? '🙈' : '👁️' }}
              </button>
            </div>
            <small class="text-muted">Minimal 3 karakter.</small>
          </div>

          <div class="form-group mb-1">
            <label style="font-weight: 600;">Konfirmasi Password Baru <span class="text-danger">*</span></label>
            <input
              type="password"
              v-model="resetForm.confirm_password"
              class="form-control"
              required
              minlength="3"
              placeholder="Ulangi password baru..."
            />
            <div v-if="resetForm.confirm_password" style="font-size: 0.8rem; margin-top: 4px;">
              <span v-if="resetForm.new_password === resetForm.confirm_password" style="color: #059669; font-weight: 600;">
                ✓ Password cocok
              </span>
              <span v-else style="color: #e11d48; font-weight: 600;">
                ✕ Konfirmasi password belum cocok
              </span>
            </div>
          </div>

          <div class="d-flex justify-between align-center gap-05 pt-1 border-top">
            <button type="button" class="btn btn-secondary" @click="showModalReset = false" :disabled="submitting">
              Batal
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="submitting || resetForm.new_password !== resetForm.confirm_password"
            >
              {{ submitting ? 'Memperbarui...' : '💾 Simpan Password Baru' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { apiFetch, authState } from '../utils/api'

const users = ref([])
const loading = ref(false)
const submitting = ref(false)
const currentUserId = ref(0)

const searchQuery = ref('')
const filterRole = ref('')

const successMsg = ref('')
const errorMsg = ref('')

// Modal state
const showModalEscalate = ref(false)
const showModalCreate = ref(false)
const showModalReset = ref(false)

const targetUser = ref(null)
const selectedRole = ref('')

const showCreatePass = ref(false)
const createForm = ref({
  username: '',
  role: 'operator',
  password: '',
  confirm_password: ''
})

const showResetPass = ref(false)
const resetForm = ref({
  new_password: '',
  confirm_password: ''
})

// Check if user is current logged-in user
const isSelf = (user) => {
  if (!user) return false
  if (currentUserId.value && user.id === currentUserId.value) return true
  return user.username === authState.value?.username
}

const countByRole = (role) => {
  return users.value.filter(u => u.role === role).length
}

const getRoleBadgeClass = (role) => {
  if (role === 'admin') return 'badge-primary'
  if (role === 'operator') return 'badge-warning'
  return 'badge-info'
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    })
  } catch (e) {
    return dateStr
  }
}

const resetFilters = () => {
  searchQuery.value = ''
  filterRole.value = ''
}

const filteredUsers = computed(() => {
  return users.value.filter(u => {
    const q = searchQuery.value.trim().toLowerCase()
    const matchSearch = !q || (u.username || '').toLowerCase().includes(q)
    const matchRole = !filterRole.value || u.role === filterRole.value
    return matchSearch && matchRole
  })
})

const fetchUsers = async () => {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await apiFetch('users.php')
    if (res.success && Array.isArray(res.data)) {
      users.value = res.data
      if (res.current_user_id) {
        currentUserId.value = res.current_user_id
      }
    }
  } catch (err) {
    errorMsg.value = err.message || 'Gagal memuat data akun.'
  } finally {
    loading.value = false
  }
}

// 1. Eskalasi / Perubahan Tingkat
const openModalEscalate = (user) => {
  targetUser.value = user
  selectedRole.value = user.role
  showModalEscalate.value = true
}

const submitEscalateRole = async () => {
  if (!targetUser.value || !selectedRole.value) return
  submitting.value = true
  errorMsg.value = ''
  successMsg.value = ''

  try {
    const res = await apiFetch('users.php?action=update_role', {
      method: 'POST',
      body: JSON.stringify({
        id: targetUser.value.id,
        role: selectedRole.value
      })
    })

    successMsg.value = res.message || `Tingkat hak akses akun '${targetUser.value.username}' berhasil diperbarui ke ${selectedRole.value}.`
    showModalEscalate.value = false
    await fetchUsers()

    // Jika mengubah akun sendiri, sinkronkan authState lokal
    if (isSelf(targetUser.value)) {
      authState.value.role = selectedRole.value
    }
  } catch (err) {
    errorMsg.value = err.message || 'Gagal mengubah role akun.'
  } finally {
    submitting.value = false
  }
}

// 2. Tambah Akun Baru
const openModalCreate = () => {
  createForm.value = {
    username: '',
    role: 'operator',
    password: '',
    confirm_password: ''
  }
  showCreatePass.value = false
  showModalCreate.value = true
}

const submitCreateUser = async () => {
  if (createForm.value.password !== createForm.value.confirm_password) {
    errorMsg.value = 'Password dan konfirmasi password tidak cocok.'
    return
  }

  submitting.value = true
  errorMsg.value = ''
  successMsg.value = ''

  try {
    const res = await apiFetch('users.php?action=create', {
      method: 'POST',
      body: JSON.stringify({
        username: createForm.value.username,
        password: createForm.value.password,
        role: createForm.value.role
      })
    })

    successMsg.value = res.message || `Akun '${createForm.value.username}' berhasil dibuat!`
    showModalCreate.value = false
    await fetchUsers()
  } catch (err) {
    errorMsg.value = err.message || 'Gagal membuat akun baru.'
  } finally {
    submitting.value = false
  }
}

// 3. Reset Password
const openModalResetPass = (user) => {
  targetUser.value = user
  resetForm.value = {
    new_password: '',
    confirm_password: ''
  }
  showResetPass.value = false
  showModalReset.value = true
}

const submitResetPassword = async () => {
  if (!targetUser.value) return
  if (resetForm.value.new_password !== resetForm.value.confirm_password) {
    errorMsg.value = 'Password baru dan konfirmasi password tidak cocok.'
    return
  }

  submitting.value = true
  errorMsg.value = ''
  successMsg.value = ''

  try {
    const res = await apiFetch('users.php?action=reset_password', {
      method: 'POST',
      body: JSON.stringify({
        id: targetUser.value.id,
        new_password: resetForm.value.new_password
      })
    })

    successMsg.value = res.message || `Password akun '${targetUser.value.username}' berhasil diperbarui.`
    showModalReset.value = false
  } catch (err) {
    errorMsg.value = err.message || 'Gagal mereset password akun.'
  } finally {
    submitting.value = false
  }
}

// 4. Hapus Akun
const confirmDeleteUser = async (user) => {
  if (isSelf(user)) {
    alert('Anda tidak dapat menghapus akun Anda sendiri.')
    return
  }

  const promptMsg = `Apakah Anda yakin ingin MENGHAPUS akun pengguna '${user.username}' (Role: ${user.role})?\n\nTindakan ini tidak dapat dibatalkan.`
  if (!confirm(promptMsg)) return

  errorMsg.value = ''
  successMsg.value = ''

  try {
    const res = await apiFetch('users.php?action=delete', {
      method: 'POST',
      body: JSON.stringify({ id: user.id })
    })

    successMsg.value = res.message || `Akun '${user.username}' berhasil dihapus.`
    await fetchUsers()
  } catch (err) {
    errorMsg.value = err.message || 'Gagal menghapus akun pengguna.'
  }
}

onMounted(() => {
  fetchUsers()
})
</script>

<style scoped>
.account-mgmt-wrap {
  width: 100%;
}

.alert-box {
  display: block;
  text-align: left;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.88rem;
  line-height: 1.4;
}
.success-box {
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
}
.error-box {
  background: #fff1f2;
  color: #be123c;
  border: 1px solid #fecdd3;
}
.btn-close-alert {
  background: none;
  border: none;
  font-size: 1rem;
  cursor: pointer;
  color: inherit;
  padding: 0 4px;
}

.user-table th {
  background: #f8fafc;
  color: #475569;
  font-weight: 700;
  font-size: 0.82rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  padding: 0.75rem 0.9rem;
  border-bottom: 2px solid #e2e8f0;
}
.user-table td {
  padding: 0.8rem 0.9rem;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}
.user-table tr:hover {
  background-color: #f8fafc;
}
.highlight-self {
  background-color: #f0fdf4 !important;
}

.user-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.78rem;
  color: #fff;
  flex-shrink: 0;
}
.avatar-admin {
  background: linear-gradient(135deg, #4f46e5, #4338ca);
}
.avatar-operator {
  background: linear-gradient(135deg, #f59e0b, #d97706);
}
.avatar-viewer {
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
}

.user-name {
  color: #0f172a;
  font-size: 0.92rem;
  display: block;
}
.badge-self {
  display: inline-block;
  font-size: 0.68rem;
  font-weight: 700;
  color: #059669;
  background: #d1fae5;
  padding: 1px 6px;
  border-radius: 99px;
  margin-top: 2px;
}

.role-option-card {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.85rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  margin-bottom: 0.65rem;
  cursor: pointer;
  transition: all 0.15s ease;
  background: #fff;
}
.role-option-card:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}
.role-option-card.selected {
  border-color: #4f46e5;
  background: #eef2ff;
}
.role-option-body {
  flex: 1;
}
.role-option-desc {
  margin: 0;
  font-size: 0.82rem;
  color: #64748b;
  line-height: 1.4;
}

.border-bottom {
  border-bottom: 1px solid var(--border-light, #e2e8f0);
}
.border-top {
  border-top: 1px solid var(--border-light, #e2e8f0);
}
.bg-soft {
  background: #f8fafc;
}
.bg-warning-light {
  background: #fef3c7;
}
.text-warning-dark {
  color: #92400e;
}

.btn-toggle-eye {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  color: var(--text-muted);
  padding: 4px;
}
</style>
