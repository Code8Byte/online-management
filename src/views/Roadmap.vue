<template>
  <div class="roadmap-page" @click="closeAnyDropdown">
    <div class="wrap">

      <!-- Header Area -->
      <div class="header-area">
        <div class="title-row">
          <img :src="logoImg" alt="Pura Group Indonesia" class="title-logo" />
          <div style="flex: 1;">
            <h1 class="main-title">Roadmap Digitalisasi Pura Offset</h1>
            <p class="roadmap-subtitle">PT Pura Barutama Unit Offset &bull; Master Rencana Transformasi &amp; Migrasi Sistem</p>
          </div>
          <div v-if="authState.isAuthenticated" class="header-action-wrap">
            <router-link :to="dashboardRoute" class="btn-back-dashboard">
              📊 Ke Dashboard Divisi
            </router-link>
          </div>
        </div>

        <!-- Progress Overview KPI Row -->
        <div class="kpi-row">
          <div class="kpi-card">
            <div class="kpi-val">{{ progressPercentage }}%</div>
            <div class="kpi-label">Progress Selesai</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-val" style="color: var(--done)">{{ kpiStats.done }}</div>
            <div class="kpi-label">Task Selesai</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-val" style="color: var(--warn)">{{ kpiStats.running }}</div>
            <div class="kpi-label">Sedang Berjalan</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-val" style="color: var(--blocked)">{{ kpiStats.blocked }}</div>
            <div class="kpi-label">Terkendala</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-val" style="color: var(--text-muted)">{{ kpiStats.pending }}</div>
            <div class="kpi-label">Belum Mulai</div>
          </div>
        </div>

        <!-- Progress Bar -->
        <div class="prog-outer">
          <div class="prog-fill" :style="{ width: progressPercentage + '%' }"></div>
        </div>
      </div>

      <!-- Legend -->
      <div class="legend">
        <span><i class="dot" style="background:var(--text-muted)"></i>Belum mulai</span>
        <span><i class="dot" style="background:var(--warn)"></i>Berjalan</span>
        <span><i class="dot" style="background:var(--blocked)"></i>Terkendala</span>
        <span><i class="dot" style="background:var(--done)"></i>Selesai</span>
      </div>

      <!-- Gantt Chart -->
      <div class="gantt">
        <!-- Ruler Header -->
        <div class="ruler">
          <div class="ruler-label">Phase</div>
          <div class="ruler-track">
            <div v-for="(week, wIdx) in rulerWeeks" :key="wIdx" class="ruler-week">
              {{ week }}
            </div>
          </div>
        </div>

        <!-- Phase Rows -->
        <div class="gantt-rows">
          <template v-for="phase in phases" :key="phase.id">
            <div class="row">
              <div class="row-head">
                <button v-if="phase.tasks && phase.tasks.length" @click.stop="togglePhase(phase.id)" :aria-expanded="expandedPhases[phase.id]">
                  <span class="chevron" :class="{ open: expandedPhases[phase.id] }">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                      <path d="M5 2.5L9.5 7L5 11.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </span>
                  <span>
                    <div class="phase-name">{{ phase.name }}</div>
                    <div class="phase-desc">{{ phase.desc }}</div>
                  </span>
                </button>
                <div v-else>
                  <div class="phase-name">{{ phase.name }}</div>
                  <div class="phase-desc">{{ phase.desc }}</div>
                </div>
              </div>

              <div class="row-track">
                <div 
                  class="bar" 
                  :class="phase.tbd ? 'bar-tbd' : 'bar-solid'"
                  :style="getBarStyle(phase)"
                >
                  {{ getBarLabel(phase) }}
                </div>
              </div>
            </div>

            <!-- Phase Tasks Detail (Collapsible) -->
            <div v-if="phase.tasks && phase.tasks.length && expandedPhases[phase.id]" class="detail">
              <div v-for="task in phase.tasks" :key="task.id" class="task">
                <div class="task-name">
                  <b>{{ task.name }}</b>
                  <span>{{ task.desc }}</span>
                </div>

                <div class="task-badges">
                  <span 
                    class="div-badge" 
                    :style="{ 
                      color: 'var(--div-' + task.div + ')', 
                      background: 'var(--div-' + task.div + '-soft)' 
                    }"
                  >
                    {{ DIV[task.div] }}
                  </span>

                  <!-- Status Interactive Button & Picker -->
                  <div class="status-btn-wrap">
                    <button 
                      type="button" 
                      class="status-pill-btn" 
                      :class="getStatusBtnClass(statusMap[task.id] || 'Belum mulai')"
                      @click.stop="handleStatusButtonClick(task)"
                      :disabled="updatingTask[task.id]"
                      title="Klik untuk mengubah status task di database"
                    >
                      <span class="status-dot"></span>
                      <span class="status-text">{{ updatingTask[task.id] ? 'Menyimpan...' : (statusMap[task.id] || 'Belum mulai') }}</span>
                      <span class="status-arrow">▾</span>
                    </button>

                    <!-- Status Picker Popover Menu -->
                    <div v-if="activePickerTaskId === task.id" class="status-popover" @click.stop>
                      <div class="popover-title">Pilih Status:</div>
                      <button 
                        v-for="opt in statusOptions" 
                        :key="opt"
                        type="button"
                        class="popover-item"
                        :class="[getStatusBtnClass(opt), { current: (statusMap[task.id] || 'Belum mulai') === opt }]"
                        @click.stop="changeTaskStatus(task, opt)"
                      >
                        <span class="status-dot"></span>
                        <span class="flex-1">{{ opt }}</span>
                        <span v-if="(statusMap[task.id] || 'Belum mulai') === opt" class="check">✓</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- Hint info -->
      <p class="hint">
        <span>ℹ️</span> 
        <span>
          Status task tersinkronisasi otomatis dengan basis data MySQL pusat puraoffset.my.id. Klik tombol status untuk mengubah status task.
        </span>
      </p>

      <!-- Footer PT Pura Barutama Unit Offset (With subtle editor access) -->
      <footer class="roadmap-footer no-print">
        <div class="roadmap-footer-inner">
          <div class="footer-left">
            <img :src="logoImg" alt="Pura Group" class="roadmap-footer-logo" />
            <span>&copy;2026 PT Pura Barutama Unit Offset. All Rights Reserved.</span>
          </div>

          <!-- Subtle Hidden Login / Editor Indicator -->
          <div class="footer-right">
            <template v-if="canEdit || authState.isAuthenticated">
              <span class="editor-chip">
                ✏️ Editor: <b>{{ authState.username || 'Admin' }}</b>
              </span>
              <button type="button" @click="handleLogout" class="btn-subtle-logout">
                Keluar Editor
              </button>
            </template>
            <template v-else>
              <router-link to="/login" class="btn-subtle-editor" style="text-decoration: none; margin-right: 8px;">
                🔐 Masuk ke Sistem
              </router-link>
              <button 
                type="button" 
                @click="openLoginModal" 
                class="btn-subtle-editor"
                title="Akses Editor Status Roadmap"
              >
                🔒 Mode Editor
              </button>
            </template>
          </div>
        </div>
      </footer>
    </div>

    <!-- Modal Login Editor Roadmap -->
    <div v-if="showLoginModal" class="modal-overlay" @click.self="showLoginModal = false">
      <div class="modal-card">
        <div class="modal-head">
          <div class="d-flex align-center gap-05">
            <img :src="logoImg" alt="Pura Group" class="modal-logo" />
            <div>
              <h3 class="modal-title">Mode Editor Roadmap</h3>
              <small class="text-muted">PT Pura Barutama Unit Offset</small>
            </div>
          </div>
          <button type="button" class="btn-close" @click="showLoginModal = false">&times;</button>
        </div>

        <div class="modal-body">
          <p class="modal-intro">
            Masukkan kredensial akun untuk mengubah status task roadmap langsung ke database MySQL.
          </p>

          <form @submit.prevent="submitModalLogin" class="modal-form">
            <div class="form-group mb-2">
              <label class="form-label">Username</label>
              <input 
                type="text" 
                v-model="modalForm.username" 
                class="form-control" 
                required 
                placeholder="admin / cetak / sortir..." 
                autocomplete="username"
                autofocus
              />
            </div>

            <div class="form-group mb-2">
              <label class="form-label">Password</label>
              <input 
                type="password" 
                v-model="modalForm.password" 
                class="form-control" 
                required 
                placeholder="Masukkan password..." 
                autocomplete="current-password"
              />
            </div>

            <div v-if="modalError" class="login-error mb-2">
              <span>⚠️</span> {{ modalError }}
            </div>

            <div class="modal-actions">
              <button type="button" class="btn btn-secondary" @click="showLoginModal = false" :disabled="modalLoading">
                Batal
              </button>
              <button type="submit" class="btn btn-primary" :disabled="modalLoading">
                {{ modalLoading ? 'Memverifikasi...' : 'Masuk ke Mode Edit' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-wrap">
      <div v-for="toast in toasts" :key="toast.id" class="toast" :class="toast.type">
        {{ toast.message }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authState, apiFetch, setAuthUser, clearAuthUser } from '../utils/api'
import logoImg from '../assets/puragroupindonesia_logo.jpg'
import { showConfirm, showError, showAlert } from '../view/modal/modalService'

const router = useRouter()
const loggingOut = ref(false)
const canEdit = ref(false)

const dashboardRoute = computed(() => {
  const d = (authState.value?.divisi || authState.value?.role || '').toLowerCase()
  if (d === 'cetak') return '/cetak'
  if (d === 'completing') return '/completing'
  if (d === 'cabut') return '/cabut'
  if (d === 'lipat') return '/lipat'
  if (d === 'sortir' || d === 'operator') return '/sortir'
  return '/sortir'
})

// Active status picker popover
const activePickerTaskId = ref(null)

// Modal login state
const showLoginModal = ref(false)
const modalLoading = ref(false)
const modalError = ref('')
const modalForm = ref({ username: '', password: '' })
const pendingTaskUpdate = ref(null)

function closeAnyDropdown() {
  activePickerTaskId.value = null
}

function openLoginModal() {
  modalError.value = ''
  modalForm.value = { username: '', password: '' }
  showLoginModal.value = true
}

// Roadmap Constants & Timeline (Oct 27 - Nov 23, 2026)
const DAY = 86400000
const rangeStart = new Date('2026-10-27')
const rangeEnd = new Date('2026-11-23')
const totalDays = Math.round((rangeEnd - rangeStart) / DAY)
const weeksCount = Math.round(totalDays / 7)

const rulerWeeks = computed(() => {
  const weeks = []
  for (let w = 0; w < weeksCount; w++) {
    const d = new Date(rangeStart.getTime() + w * 7 * DAY)
    weeks.push(d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }))
  }
  return weeks
})

function pct(dateStr) {
  const d = new Date(dateStr)
  return Math.max(0, Math.min(100, ((d - rangeStart) / DAY) / totalDays * 100))
}

function widthPct(startStr, endStr) {
  return Math.max(2, pct(endStr) - pct(startStr))
}

function getBarStyle(phase) {
  if (phase.tbd) {
    return { left: '2%', width: '20%' }
  }
  return {
    left: pct(phase.start) + '%',
    width: widthPct(phase.start, phase.end) + '%'
  }
}

function getBarLabel(phase) {
  if (phase.tbd) return 'TBD'
  const d1 = new Date(phase.start).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })
  const d2 = new Date(phase.end).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })
  return `${d1} – ${d2}`
}

const DIV = {
  cetak: 'Cetak',
  completing: 'Completing',
  cabut: 'Cabut',
  lipat: 'Lipat',
  sortir: 'Sortir',
  lintas: 'Lintas divisi'
}

const statusOptions = ['Belum mulai', 'Berjalan', 'Terkendala', 'Selesai']

function getStatusBtnClass(status) {
  switch (status) {
    case 'Selesai': return 'st-done'
    case 'Berjalan': return 'st-warn'
    case 'Terkendala': return 'st-blocked'
    default: return 'st-pending'
  }
}

const phases = ref([
  {
    id: 'p0',
    name: 'Phase 0 — Fondasi',
    desc: 'Migrasi data + login bertingkat per divisi',
    start: '2026-11-02',
    end: '2026-11-02',
    tasks: [
      { id: 't1', div: 'lintas', name: 'Redesain skema data', desc: 'Tabel divisi, user_divisi, divisi_id di mesin/PP/jenis waste, kolom detail JSON di LKH' },
      { id: 't2', div: 'sortir', name: 'Migrasi data Sortir', desc: 'Pindah data LKH/OEE/job order existing (online-management) ke skema baru' },
      { id: 't3', div: 'cetak', name: 'Login & tampilan divisi', desc: 'Dashboard & menu khusus akun Cetak' },
      { id: 't4', div: 'completing', name: 'Login & tampilan divisi', desc: 'Dashboard & menu khusus akun Completing' },
      { id: 't5', div: 'cabut', name: 'Login & tampilan divisi', desc: 'Dashboard & menu khusus akun Cabut' },
      { id: 't6', div: 'lipat', name: 'Login & tampilan divisi', desc: 'Dashboard & menu khusus akun Lipat' },
      { id: 't7', div: 'sortir', name: 'Login & tampilan divisi', desc: 'Dashboard & menu khusus akun Sortir' }
    ]
  },
  {
    id: 'p1',
    name: 'Phase 1 — Form Dasar + Integrasi Waste',
    desc: 'Form waste Cetak/Completing/Lipat, Sortir full migrasi',
    start: '2026-11-03',
    end: '2026-11-03',
    tasks: [
      { id: 't8', div: 'cetak', name: 'Form waste', desc: 'Form input waste sederhana divisi Cetak' },
      { id: 't9', div: 'completing', name: 'Form waste', desc: 'Form input waste sederhana divisi Completing' },
      { id: 't10', div: 'lipat', name: 'Form waste', desc: 'Form input waste sederhana divisi Lipat' },
      { id: 't11', div: 'sortir', name: 'Full migration', desc: 'Fitur existing (LKH, OEE, job order) berjalan penuh di skema baru' },
      { id: 't12', div: 'lintas', name: 'Integrasi waste PP lintas divisi', desc: 'Data waste PP terhubung antar divisi termasuk Sortir' },
      { id: 't13', div: 'lintas', name: 'Konversi unit Cetak → Cabut', desc: 'Plano (Cetak) dikonversi ke oplaag/pcs saat masuk ke Cabut' }
    ]
  },
  {
    id: 'p2',
    name: 'Phase 2 — LKH Lengkap per Divisi',
    desc: 'Upgrade form waste jadi LKH lengkap, input masih oleh staff existing',
    start: '2026-11-04',
    end: '2026-11-04',
    tasks: [
      { id: 't14', div: 'cetak', name: 'LKH lengkap', desc: 'Upgrade dari form waste menjadi LKH lengkap' },
      { id: 't15', div: 'completing', name: 'LKH lengkap', desc: 'Upgrade dari form waste menjadi LKH lengkap' },
      { id: 't16', div: 'cabut', name: 'LKH lengkap', desc: 'Upgrade dari form waste menjadi LKH lengkap' },
      { id: 't17', div: 'lipat', name: 'LKH lengkap', desc: 'Upgrade dari form waste menjadi LKH lengkap' },
      { id: 't17b', div: 'lintas', name: 'Alur input manual', desc: 'Masih ada pembukuan — staff existing cek buku tiap pagi lalu input ke website, belum langsung dari operator' },
      { id: 't17c', div: 'lintas', name: 'Spesifikasi field LKH', desc: 'Diisi oleh staff produksi yang bekerja di tiap divisi' }
    ]
  },
  {
    id: 'p3',
    name: 'Phase 3 — Integrasi PP & Scheduling',
    desc: 'PP/Job Order real-time lintas divisi + scheduling otomatis',
    start: '2026-11-05',
    end: '2026-11-06',
    tasks: [
      { id: 't18', div: 'cetak', name: 'Update PP real-time', desc: 'Job order tersinkron otomatis' },
      { id: 't19', div: 'completing', name: 'Update PP real-time', desc: 'Job order tersinkron otomatis' },
      { id: 't20', div: 'cabut', name: 'Update PP real-time', desc: 'Job order tersinkron otomatis' },
      { id: 't21', div: 'lipat', name: 'Update PP real-time', desc: 'Job order tersinkron otomatis' },
      { id: 't22', div: 'sortir', name: 'Update PP real-time', desc: 'Job order tersinkron otomatis' },
      { id: 't23', div: 'lintas', name: 'Scheduling otomatis', desc: 'Mesin loading & jadwal antar divisi' }
    ]
  },
  {
    id: 'p4',
    name: 'Phase 4 — Dashboard Sentral & OEE Lintas Divisi',
    desc: 'OEE Cetak/Completing/Cabut/Lipat + Executive Dashboard',
    start: '2026-11-09',
    end: '2026-11-10',
    tasks: [
      { id: 't24', div: 'lintas', name: 'Dashboard sentral / Executive', desc: 'Ringkasan performa 5 divisi' },
      { id: 't25', div: 'cetak', name: 'OEE Cetak', desc: 'Implementasi OEE mesin cetak' },
      { id: 't26', div: 'completing', name: 'OEE Completing', desc: 'Implementasi OEE foil/varnish/laminasi' },
      { id: 't27', div: 'cabut', name: 'OEE Cabut', desc: 'Implementasi OEE mesin cabut' },
      { id: 't28', div: 'lipat', name: 'OEE Lipat', desc: 'Implementasi OEE mesin lipat' }
    ]
  },
  {
    id: 'p5',
    name: 'Phase 5 — Full Digitalisasi (Operator Langsung)',
    desc: 'Target akhir — operator tiap divisi input sendiri, pembukuan ditinggal',
    tbd: true,
    tasks: [
      { id: 't29', div: 'lintas', name: 'Tablet / terminal per mesin', desc: 'Hardware untuk tiap operator' },
      { id: 't30', div: 'lintas', name: 'Training operator 5 divisi', desc: 'Pelatihan input LKH digital langsung' }
    ]
  }
])

// Expand/Collapse state
const expandedPhases = ref({
  p0: true,
  p1: true,
  p2: true,
  p3: false,
  p4: false,
  p5: false
})

function togglePhase(phaseId) {
  expandedPhases.value[phaseId] = !expandedPhases.value[phaseId]
}

// Map task_id -> status from database
const statusMap = ref({})
const updatingTask = ref({})

// Total Task Count
const allTaskIds = computed(() => {
  const ids = []
  phases.value.forEach(p => {
    if (p.tasks) p.tasks.forEach(t => ids.push(t.id))
  })
  return ids
})

// Progress percentage & KPI Statistics
const kpiStats = computed(() => {
  let done = 0
  let running = 0
  let blocked = 0
  let pending = 0

  allTaskIds.value.forEach(tid => {
    const st = statusMap.value[tid] || 'Belum mulai'
    if (st === 'Selesai') done++
    else if (st === 'Berjalan') running++
    else if (st === 'Terkendala') blocked++
    else pending++
  })

  return { done, running, blocked, pending, total: allTaskIds.value.length }
})

const progressPercentage = computed(() => {
  if (!kpiStats.value.total) return 0
  return Math.round((kpiStats.value.done / kpiStats.value.total) * 100)
})

// Toasts
const toasts = ref([])
let toastCount = 0

function showToast(message, type = 'info') {
  const id = ++toastCount
  toasts.value.push({ id, message, type })
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }, 3500)
}

// Fetch status from database API
async function loadStatus() {
  try {
    const res = await apiFetch('apiroadmap.php?action=status')
    if (res) {
      if (res.can_edit || authState.value.isAuthenticated) {
        canEdit.value = true
        if (res.user && !authState.value.isAuthenticated) {
          setAuthUser(res.user)
        }
      }
      if (res.tasks) {
        const map = {}
        for (const tid in res.tasks) {
          map[tid] = res.tasks[tid].status
        }
        statusMap.value = map
      }
    }
  } catch (err) {
    console.warn('Gagal memuat status roadmap:', err)
  }
}

// Status button click handler
function handleStatusButtonClick(task) {
  if (canEdit.value || authState.value.isAuthenticated) {
    activePickerTaskId.value = activePickerTaskId.value === task.id ? null : task.id
  } else {
    pendingTaskUpdate.value = { task }
    openLoginModal()
  }
}

// Change status and persist to database
async function changeTaskStatus(task, newStatus) {
  activePickerTaskId.value = null
  const prevStatus = statusMap.value[task.id] || 'Belum mulai'
  if (prevStatus === newStatus) return

  statusMap.value[task.id] = newStatus
  updatingTask.value[task.id] = true

  try {
    const payload = {
      task_id: task.id,
      status: newStatus
    }
    if (authState.value && authState.value.username) {
      payload.auth_user = authState.value.username
    }

    const res = await apiFetch('apiroadmap.php?action=update', {
      method: 'POST',
      body: JSON.stringify(payload)
    })

    if (res && res.success) {
      canEdit.value = true
      showToast(`✅ Status "${task.name}" disimpan ke database (${newStatus})`, 'success')
    } else {
      throw new Error(res?.error || 'Gagal menyimpan ke database')
    }
  } catch (err) {
    statusMap.value[task.id] = prevStatus
    showToast(`❌ Gagal: ${err.message}`, 'error')
    if (err.message.toLowerCase().includes('akses ditolak') || err.message.toLowerCase().includes('login')) {
      canEdit.value = false
      pendingTaskUpdate.value = { task, newStatus }
      openLoginModal()
    }
  } finally {
    updatingTask.value[task.id] = false
  }
}

// Submit modal login
async function submitModalLogin() {
  modalError.value = ''
  const u = modalForm.value.username.trim()
  const p = modalForm.value.password.trim()

  if (!u || !p) {
    modalError.value = 'Silakan isi username dan password'
    return
  }

  modalLoading.value = true
  try {
    const res = await apiFetch('apiroadmap.php?action=login', {
      method: 'POST',
      body: JSON.stringify({ username: u, password: p })
    })

    if (res && res.success) {
      canEdit.value = true
      setAuthUser(res)

      showLoginModal.value = false
      showToast(`✅ Berhasil masuk! Mode editor roadmap aktif sebagai ${res.username || u}.`, 'success')

      // Jika sebelumnya user mengklik task tertentu, buka pickernya
      if (pendingTaskUpdate.value && pendingTaskUpdate.value.task) {
        const t = pendingTaskUpdate.value.task
        if (pendingTaskUpdate.value.newStatus) {
          changeTaskStatus(t, pendingTaskUpdate.value.newStatus)
        } else {
          activePickerTaskId.value = t.id
        }
        pendingTaskUpdate.value = null
      }
    } else {
      modalError.value = res?.error || 'Username atau password salah.'
    }
  } catch (e) {
    modalError.value = e.message || 'Username atau password salah.'
  } finally {
    modalLoading.value = false
  }
}

// Handle Logout
async function handleLogout() {
  const confirmed = await showConfirm(
    'Apakah Anda yakin ingin keluar dari mode Editor Roadmap?',
    {
      title: 'Konfirmasi Keluar Editor',
      confirmText: 'Ya, Keluar',
      cancelText: 'Batal',
      type: 'confirm'
    }
  )
  if (!confirmed) return
  loggingOut.value = true
  try {
    await apiFetch('apiroadmap.php?action=logout', { method: 'POST' })
    canEdit.value = false
    clearAuthUser()
    showToast('Berhasil keluar. Mode beralih ke Hanya Baca (Publik).', 'info')
  } catch (err) {
    showError(err.message)
  } finally {
    loggingOut.value = false
  }
}

onMounted(() => {
  loadStatus()
})
</script>

<style scoped>
.roadmap-page {
  --bg: #F6F4EE;
  --surface: #FFFFFF;
  --surface-2: #EFEBE1;
  --border: #DCD6C6;
  --text: #2B2A25;
  --text-muted: #75715F;
  --accent: #0F6E56;
  --accent-soft: #D9ECE3;
  --warn: #BA7517;
  --warn-soft: #FAEEDA;
  --blocked: #A32D2D;
  --blocked-soft: #FCEBEB;
  --done: #3B6D11;
  --done-soft: #EAF3DE;
  --div-cetak: #185FA5; --div-cetak-soft: #E6F1FB;
  --div-completing: #534AB7; --div-completing-soft: #EEEDFE;
  --div-cabut: #993C1D; --div-cabut-soft: #FAECE7;
  --div-lipat: #993556; --div-lipat-soft: #FBEAF0;
  --div-sortir: #0F6E56; --div-sortir-soft: #E1F5EE;
  --div-lintas: #5F5E5A; --div-lintas-soft: #F1EFE8;
  --font-display: 'Fraunces', serif;
  --font-body: 'Inter', sans-serif;
  --font-mono: 'JetBrains Mono', monospace;

  background: var(--bg);
  color: var(--text);
  font-family: var(--font-body);
  min-height: calc(100vh - 120px);
  padding: 1.5rem 1rem 3.5rem;
  border-radius: 12px;
}

.wrap {
  max-width: 1400px;
  margin: 0 auto;
}

/* Header Area */
.header-area {
  margin-bottom: 24px;
}
.title-row {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}
.btn-back-dashboard {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #ffffff;
  border: 1px solid var(--border);
  color: var(--text);
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 13.5px;
  font-weight: 600;
  text-decoration: none;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all 0.15s ease;
}
.btn-back-dashboard:hover {
  background: var(--surface-2);
  border-color: #2563eb;
  color: #2563eb;
}
.title-logo {
  height: 48px;
  width: auto;
  object-fit: contain;
  border-radius: 8px;
  background: #ffffff;
  padding: 3px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}
.main-title {
  font-family: var(--font-display);
  font-size: 26px;
  font-weight: 700;
  letter-spacing: -0.02em;
  margin: 0;
  color: var(--text);
}
.roadmap-subtitle {
  margin: 3px 0 0 0;
  font-size: 13.5px;
  color: var(--text-muted);
  font-weight: 500;
}

/* KPI Row */
.kpi-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}
.kpi-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 12px 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.kpi-val {
  font-size: 24px;
  font-weight: 700;
  font-family: var(--font-mono);
  color: var(--text);
}
.kpi-label {
  font-size: 11.5px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 3px;
  font-weight: 600;
}

/* Progress bar */
.prog-outer {
  height: 8px;
  background: var(--surface-2);
  border-radius: 999px;
  overflow: hidden;
  border: 1px solid var(--border);
}
.prog-fill {
  height: 100%;
  background: var(--accent);
  transition: width .3s ease;
  border-radius: 999px;
}

/* Legend */
.legend {
  display: flex;
  gap: 18px;
  margin-bottom: 20px;
  font-size: 12.5px;
  color: var(--text-muted);
  flex-wrap: wrap;
  align-items: center;
}
.legend span {
  display: inline-flex;
  align-items: center;
  gap: 7px;
}
.dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  display: inline-block;
}

/* Gantt Chart */
.gantt {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}

.ruler {
  display: flex;
  background: var(--surface-2);
  border-bottom: 1px solid var(--border);
  font-size: 11.5px;
  font-weight: 600;
  color: var(--text-muted);
}
.ruler-label {
  width: 280px;
  padding: 12px 16px;
  border-right: 1px solid var(--border);
  flex-shrink: 0;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.ruler-track {
  display: flex;
  flex: 1;
}
.ruler-week {
  flex: 1;
  padding: 12px 4px;
  text-align: center;
  border-right: 1px dashed var(--border);
  font-family: var(--font-mono);
}
.ruler-week:last-child { border-right: none; }

.row {
  display: flex;
  border-bottom: 1px solid var(--border);
  min-height: 48px;
}
.row:last-child { border-bottom: none; }
.row-head {
  width: 280px;
  border-right: 1px solid var(--border);
  flex-shrink: 0;
  background: var(--surface);
  display: flex;
  align-items: center;
}
.row-head button, .row-head > div {
  width: 100%;
  padding: 10px 14px;
  border: none;
  background: transparent;
  text-align: left;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  color: inherit;
  font: inherit;
}
.row-head > div { cursor: default; }

.chevron {
  width: 16px;
  height: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform .2s ease;
  color: var(--text-muted);
  flex-shrink: 0;
}
.chevron.open { transform: rotate(90deg); }

.phase-name {
  font-weight: 700;
  font-size: 13.5px;
  color: var(--text);
}
.phase-desc {
  font-size: 11.5px;
  color: var(--text-muted);
  margin-top: 2px;
}

.row-track {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
  background-image: repeating-linear-gradient(90deg, transparent, transparent calc((100% / 4) - 1px), var(--border) calc((100% / 4) - 1px), var(--border) calc(100% / 4));
}

.bar {
  position: absolute;
  height: 26px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  padding: 0 10px;
  font-size: 11px;
  font-family: var(--font-mono);
  font-weight: 600;
  white-space: nowrap;
}
.bar-solid {
  background: var(--accent-soft);
  color: var(--accent);
  border: 1px solid var(--accent);
}
.bar-tbd {
  background: repeating-linear-gradient(45deg, var(--surface-2), var(--surface-2) 6px, var(--border) 6px, var(--border) 12px);
  color: var(--text-muted);
  border: 1px dashed var(--text-muted);
}

/* Detail Tasks */
.detail {
  background: var(--surface-2);
  border-bottom: 1px solid var(--border);
  padding: 8px 16px 8px 46px;
}
.task {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 9px 0;
  border-bottom: 1px dashed var(--border);
  gap: 16px;
}
.task:last-child { border-bottom: none; }
.task-name {
  font-size: 13px;
  color: var(--text);
}
.task-name b { font-weight: 600; margin-right: 6px; }
.task-name span { color: var(--text-muted); font-size: 12px; }

.task-badges {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
  position: relative;
}
.div-badge {
  font-size: 11px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 4px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

/* Interactive Status Button */
.status-btn-wrap {
  position: relative;
}

.status-pill-btn {
  font-size: 12px;
  font-weight: 600;
  padding: 5px 10px;
  border-radius: 6px;
  border: 1px solid transparent;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all .15s ease;
  background: #ffffff;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.status-pill-btn:hover:not(:disabled) {
  filter: brightness(0.96);
  transform: translateY(-1px);
}
.status-pill-btn:disabled {
  opacity: 0.75;
  cursor: wait;
}

.status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  display: inline-block;
  flex-shrink: 0;
}
.status-arrow {
  font-size: 9px;
  opacity: 0.7;
}

/* Status Button Theme Colors */
.st-done {
  background: var(--done-soft);
  color: var(--done);
  border-color: #b7e096;
}
.st-done .status-dot { background: var(--done); }

.st-warn {
  background: var(--warn-soft);
  color: var(--warn);
  border-color: #f7d598;
}
.st-warn .status-dot { background: var(--warn); }

.st-blocked {
  background: var(--blocked-soft);
  color: var(--blocked);
  border-color: #f5b7b7;
}
.st-blocked .status-dot { background: var(--blocked); }

.st-pending {
  background: #f1f5f9;
  color: #64748b;
  border-color: #cbd5e1;
}
.st-pending .status-dot { background: #94a3b8; }

/* Status Popover Dropdown */
.status-popover {
  position: absolute;
  top: calc(100% + 4px);
  right: 0;
  width: 170px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 6px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
  z-index: 1000;
  animation: popoverIn .15s ease-out;
}

@keyframes popoverIn {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}

.popover-title {
  font-size: 10.5px;
  font-weight: 700;
  text-transform: uppercase;
  color: #94a3b8;
  padding: 4px 8px 6px;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #f1f5f9;
  margin-bottom: 4px;
}

.popover-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  border-radius: 6px;
  border: none;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  margin-bottom: 3px;
  text-align: left;
  transition: all .1s;
}
.popover-item:last-child { margin-bottom: 0; }
.popover-item:hover {
  filter: brightness(0.94);
}
.popover-item.current {
  outline: 2px solid #0f6e56;
}
.popover-item .check {
  font-weight: 800;
}

/* Hint */
.hint {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 16px;
  padding: 0 4px;
}

/* Footer with subtle editor login */
.roadmap-footer {
  margin-top: 40px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}
.roadmap-footer-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
  font-size: 13px;
  color: var(--text-muted);
}
.footer-left {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
}
.roadmap-footer-logo {
  height: 22px;
  width: auto;
  border-radius: 4px;
  object-fit: contain;
}

.footer-right {
  display: flex;
  align-items: center;
  gap: 12px;
}
.btn-subtle-editor {
  border: 1px solid transparent;
  background: transparent;
  color: #94a3b8;
  font-size: 11.5px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 6px;
  cursor: pointer;
  transition: all .15s;
}
.btn-subtle-editor:hover {
  color: var(--text);
  background: var(--surface);
  border-color: var(--border);
}

.editor-chip {
  font-size: 12px;
  color: var(--accent);
  background: var(--accent-soft);
  padding: 3px 8px;
  border-radius: 6px;
  font-weight: 600;
}
.btn-subtle-logout {
  border: none;
  background: transparent;
  color: #ef4444;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  padding: 2px 6px;
}
.btn-subtle-logout:hover {
  text-decoration: underline;
}

/* Modal Login Editor */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(3px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  z-index: 10000;
}
.modal-card {
  width: 100%;
  max-width: 420px;
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  animation: modalIn .2s ease-out;
}

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.96); }
  to { opacity: 1; transform: scale(1); }
}

.modal-head {
  padding: 16px 20px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.modal-logo {
  height: 36px;
  width: auto;
  border-radius: 4px;
}
.modal-title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}
.btn-close {
  border: none;
  background: transparent;
  font-size: 22px;
  color: #94a3b8;
  cursor: pointer;
  line-height: 1;
}
.btn-close:hover { color: #0f172a; }

.modal-body {
  padding: 20px;
}
.modal-intro {
  margin: 0 0 16px 0;
  font-size: 12.5px;
  color: #64748b;
  line-height: 1.45;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 12px;
}
.form-label {
  font-size: 12px;
  font-weight: 600;
  color: #334155;
}
.form-control {
  height: 38px;
  padding: 0 10px;
  font-size: 13.5px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  outline: none;
}
.form-control:focus {
  border-color: #0f6e56;
  box-shadow: 0 0 0 3px rgba(15, 110, 86, 0.15);
}

.login-error {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 18px;
}
.modal-actions .btn {
  height: 38px;
  padding: 0 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}
.modal-actions .btn-secondary {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #475569;
}
.modal-actions .btn-primary {
  background: #0f6e56;
  border: none;
  color: #ffffff;
}

/* Toast Notifications */
.toast-wrap {
  position: fixed;
  bottom: 20px;
  right: 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  z-index: 9999;
}
.toast {
  background: var(--surface);
  color: var(--text);
  border: 1px solid var(--border);
  padding: 10px 16px;
  border-radius: 10px;
  font-size: 12.5px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  display: flex;
  align-items: center;
  gap: 8px;
  animation: toastIn .2s ease-out;
}
.toast.success { border-left: 4px solid var(--done); }
.toast.info { border-left: 4px solid var(--accent); }
.toast.error { border-left: 4px solid var(--blocked); }

@keyframes toastIn {
  from { transform: translateY(10px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

@media (max-width: 640px) {
  .ruler-label, .row-head { width: 140px; padding: 10px 8px; }
  .detail { padding-left: 140px; }
  .phase-desc { display: none; }
  .ruler-week { font-size: 9px; }
  .roadmap-footer-inner { flex-direction: column; align-items: flex-start; }
}
</style>
