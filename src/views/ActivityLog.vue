<template>
  <div>
    <!-- Header -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin:0; border:none; padding:0;">
            <span>📜</span> Log Aktivitas Pengguna (Audit Trail)
          </h2>
          <small class="text-muted">
            Monitoring rekam jejak pengisian LKH, perubahan data (edit), penghapusan, dan export dokumen para operator, viewer, dan admin.
          </small>
        </div>
        <div class="d-flex gap-05 align-center">
          <button class="btn btn-secondary btn-sm" @click="loadLogs" :disabled="loading" title="Refresh Log">
            <span>🔄</span> Refresh
          </button>
        </div>
      </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-4 gap-1 mb-1">
      <div class="kpi-card">
        <div class="kpi-label">Aktivitas Hari Ini</div>
        <div class="kpi-value text-primary">{{ summary.total_today || 0 }}</div>
      </div>
      <div class="kpi-card kpi-success">
        <div class="kpi-label" style="color: var(--success);">Pengisian LKH Baru</div>
        <div class="kpi-value text-success">{{ summary.isi_today || 0 }}</div>
      </div>
      <div class="kpi-card" style="border-top-color: #0ea5e9;">
        <div class="kpi-label" style="color: #0369a1;">Perubahan Data (Edit)</div>
        <div class="kpi-value" style="color: #0369a1;">{{ summary.edit_today || 0 }}</div>
      </div>
      <div class="kpi-card kpi-purple">
        <div class="kpi-label" style="color: #7c3aed;">Export Dokumen</div>
        <div class="kpi-value" style="color: #7c3aed;">{{ summary.export_today || 0 }}</div>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="card mb-1">
      <div class="grid grid-4 gap-1 mb-1">
        <div class="form-group" style="margin-bottom: 0;">
          <label>Tanggal Mulai</label>
          <input type="date" v-model="filter.start_date" class="form-control" />
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Tanggal Selesai</label>
          <input type="date" v-model="filter.end_date" class="form-control" />
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Filter Pengguna</label>
          <select v-model="filter.username" class="form-control">
            <option value="">Semua Pengguna</option>
            <option v-for="u in usersList" :key="u.username" :value="u.username">
              {{ u.username }} ({{ u.role }})
            </option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Jenis Aktivitas</label>
          <select v-model="filter.action" class="form-control">
            <option value="">Semua Aktivitas</option>
            <option value="ISI_LKH">✍️ Pengisian LKH Baru</option>
            <option value="EDIT_LKH">✏️ Edit / Perubahan LKH</option>
            <option value="DELETE_LKH">🗑️ Hapus LKH</option>
            <option value="EXPORT_EXCEL">📥 Export Excel LKH</option>
            <option value="EXPORT_CSV">📄 Export CSV LKH</option>
            <option value="EXPORT_CC_EXCEL">📑 Export CC (Excel)</option>
            <option value="EXPORT_CC_PRINT">🖨️ Export CC (Cetak / PDF)</option>
            <option value="LOGIN">🔑 Login</option>
            <option value="LOGOUT">🚪 Logout</option>
            <option value="CHANGE_PASSWORD">🔒 Ubah Password</option>
          </select>
        </div>
      </div>

      <div class="grid grid-2 gap-1 mb-1">
        <div class="form-group" style="margin-bottom: 0;">
          <label>Pencarian Kata Kunci</label>
          <input 
            type="text" 
            v-model="filter.q" 
            class="form-control" 
            placeholder="Cari No PP, nama mesin, IP, atau keterangan..." 
            @keyup.enter="applyFilter" 
          />
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Role</label>
          <select v-model="filter.role" class="form-control">
            <option value="">Semua Role</option>
            <option value="operator">Operator</option>
            <option value="viewer">Viewer</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>

      <div class="d-flex justify-between align-center flex-wrap gap-05 pt-1" style="border-top: 1px solid var(--border-light);">
        <div class="d-flex gap-05">
          <button class="btn btn-primary btn-sm" @click="applyFilter">
            <span>⚡</span> Terapkan Filter
          </button>
          <button class="btn btn-secondary btn-sm" @click="resetFilter">
            Reset
          </button>
        </div>
        <span class="text-muted" style="font-size: 0.8rem;">
          Total ditemukan: <b>{{ total }}</b> rekaman aktivitas
        </span>
      </div>
    </div>

    <!-- Log Table -->
    <div class="card">
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th style="width: 130px;">Waktu &amp; Jam</th>
              <th style="width: 140px;">Pengguna</th>
              <th style="width: 150px;">Aktivitas</th>
              <th>Keterangan / Rincian</th>
              <th style="width: 120px;">IP Address</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <!-- Waktu & Jam -->
              <td class="text-nowrap" style="font-size: 0.82rem;">
                <div style="font-weight: 700; color: var(--text); font-family: monospace;">
                  {{ log.formatted_time }}
                </div>
                <div class="text-muted" style="font-size: 0.72rem;">
                  {{ log.formatted_date }}
                </div>
              </td>

              <!-- Pengguna -->
              <td class="text-nowrap">
                <div style="font-weight: 700; color: var(--text);">
                  👤 {{ log.username }}
                </div>
                <span 
                  class="badge" 
                  :class="getRoleBadgeClass(log.role)" 
                  style="font-size: 0.68rem; text-transform: uppercase; padding: 0.1rem 0.35rem;"
                >
                  {{ log.role }}
                </span>
              </td>

              <!-- Aktivitas -->
              <td class="text-nowrap">
                <span class="badge" :class="getActionBadgeClass(log.action)" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                  {{ getActionLabel(log.action) }}
                </span>
              </td>

              <!-- Keterangan -->
              <td style="font-size: 0.85rem; line-height: 1.45; word-break: break-word;">
                {{ log.keterangan || '-' }}
              </td>

              <!-- IP Address -->
              <td class="text-nowrap" style="font-size: 0.75rem; font-family: monospace; color: var(--text-muted);">
                {{ log.ip_address || '-' }}
              </td>
            </tr>

            <tr v-if="logs.length === 0 && !loading">
              <td colspan="5" class="text-center text-muted" style="padding: 2rem;">
                Tidak ada riwayat aktivitas yang sesuai filter.
              </td>
            </tr>

            <tr v-if="loading">
              <td colspan="5" class="text-center text-muted" style="padding: 2rem;">
                ⏳ Memuat data log aktivitas...
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="d-flex justify-between align-center mt-1 flex-wrap gap-05">
        <button class="btn btn-secondary btn-sm" :disabled="page <= 1 || loading" @click="page--; loadLogs()">
          ◀ Sebelumnya
        </button>
        <span class="text-muted" style="font-size: 0.82rem;">
          Halaman <b>{{ page }}</b> dari <b>{{ maxPage }}</b> (Total {{ total }} data)
        </span>
        <button class="btn btn-secondary btn-sm" :disabled="page >= maxPage || loading" @click="page++; loadLogs()">
          Selanjutnya ▶
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { apiFetch } from '../utils/api'

const logs = ref([])
const total = ref(0)
const page = ref(1)
const limit = ref(25)
const loading = ref(false)

const summary = ref({
  total_today: 0,
  isi_today: 0,
  edit_today: 0,
  export_today: 0
})

const usersList = ref([])

const filter = ref({
  start_date: '',
  end_date: '',
  username: '',
  role: '',
  action: '',
  q: ''
})

const maxPage = computed(() => Math.max(1, Math.ceil(total.value / limit.value)))

const getRoleBadgeClass = (role) => {
  if (role === 'admin') return 'badge-primary'
  if (role === 'operator') return 'badge-warning'
  return 'badge-info'
}

const getActionBadgeClass = (act) => {
  switch (act) {
    case 'ISI_LKH': return 'badge-success'
    case 'EDIT_LKH': return 'badge-info'
    case 'DELETE_LKH': return 'badge-danger'
    case 'EXPORT_EXCEL':
    case 'EXPORT_CSV': return 'badge-purple'
    case 'EXPORT_CC_EXCEL':
    case 'EXPORT_CC_PRINT': return 'badge-warning'
    case 'LOGIN': return 'badge-success'
    case 'LOGOUT': return 'badge-secondary'
    case 'CHANGE_PASSWORD': return 'badge-primary'
    default: return 'badge-secondary'
  }
}

const getActionLabel = (act) => {
  switch (act) {
    case 'ISI_LKH': return '✍️ ISI LKH'
    case 'EDIT_LKH': return '✏️ EDIT LKH'
    case 'DELETE_LKH': return '🗑️ HAPUS LKH'
    case 'EXPORT_EXCEL': return '📥 EXPORT EXCEL'
    case 'EXPORT_CSV': return '📄 EXPORT CSV'
    case 'EXPORT_CC_EXCEL': return '📑 EXPORT CC (XLSX)'
    case 'EXPORT_CC_PRINT': return '🖨️ CETAK CC (PDF)'
    case 'LOGIN': return '🔑 LOGIN'
    case 'LOGOUT': return '🚪 LOGOUT'
    case 'CHANGE_PASSWORD': return '🔒 UBAH PASSWORD'
    default: return act
  }
}

const applyFilter = () => {
  page.value = 1
  loadLogs()
}

const resetFilter = () => {
  filter.value = {
    start_date: '',
    end_date: '',
    username: '',
    role: '',
    action: '',
    q: ''
  }
  page.value = 1
  loadLogs()
}

const loadLogs = async () => {
  loading.value = true
  try {
    const query = new URLSearchParams()
    query.append('page', page.value)
    query.append('limit', limit.value)
    if (filter.value.start_date) query.append('start_date', filter.value.start_date)
    if (filter.value.end_date) query.append('end_date', filter.value.end_date)
    if (filter.value.username) query.append('username', filter.value.username)
    if (filter.value.role) query.append('role', filter.value.role)
    if (filter.value.action) query.append('action', filter.value.action)
    if (filter.value.q) query.append('q', filter.value.q)

    const res = await apiFetch(`logs.php?${query.toString()}`)
    logs.value = res.data || []
    total.value = res.total || 0
    if (res.summary) summary.value = res.summary
    if (res.users) usersList.value = res.users
  } catch (e) {
    console.error('Failed to load logs', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadLogs()
})
</script>
