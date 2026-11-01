<template>
  <div class="data-pp-view">
    <!-- ============ HEADER & STATS ============ -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>📋</span> Data Job Order (PP) - PPIC
          </h2>
          <small class="text-muted">
            Rekap seluruh Job Order produksi: target oplaag, konversi plano, jam masuk mesin, alokasi armada, dan batas pengiriman
          </small>
        </div>
        <div class="d-flex gap-05 align-center flex-wrap">
          <router-link to="/ppic/jadwal" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <span>📊</span> Lihat Gantt Chart
          </router-link>
          <router-link to="/ppic/input-pp" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <span>✍️</span> + Input PP Baru
          </router-link>
        </div>
      </div>

      <!-- Quick KPI Badges -->
      <div class="grid grid-4 gap-08 mt-1 pt-1" style="border-top: 1px solid #e2e8f0;">
        <div class="kpi-mini-card">
          <span class="text-muted">TOTAL JOB ORDER</span>
          <b class="text-primary" style="font-size: 1.25rem;">{{ uniquePpList.length }}</b>
          <small class="text-muted">Pesanan Terdaftar</small>
        </div>
        <div class="kpi-mini-card">
          <span class="text-muted">TOTAL OPLAAG</span>
          <b class="text-indigo" style="font-size: 1.25rem;">{{ formatNumber(totalOplaagAll) }}</b>
          <small class="text-muted">Pieces (Pcs)</small>
        </div>
        <div class="kpi-mini-card">
          <span class="text-muted">TOTAL PLANO CETAK</span>
          <b class="text-success" style="font-size: 1.25rem;">{{ formatNumber(totalPlanoAll) }}</b>
          <small class="text-muted">Lembar Plano Druk</small>
        </div>
        <div class="kpi-mini-card">
          <span class="text-muted">AUTO-EXTEND (MOLOR)</span>
          <b class="text-danger" style="font-size: 1.25rem;">{{ totalExtendedPpCount }}</b>
          <small class="text-muted">Perlu Perhatian</small>
        </div>
      </div>
    </div>

    <!-- ============ FILTER BAR (Sesuai Gaya LKH Sortir) ============ -->
    <div class="card mb-1 p-08">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <!-- Segmen Status Berwarna -->
        <div class="d-flex gap-05 align-center flex-wrap">
          <div class="status-seg-group">
            <button
              type="button"
              class="status-seg-btn"
              :class="{ active: filterStatus === '' }"
              @click="filterStatus = ''"
            >
              Semua ({{ uniquePpList.length }})
            </button>
            <button
              type="button"
              class="status-seg-btn btn-proc"
              :class="{ active: filterStatus === 'running' }"
              @click="filterStatus = 'running'"
            >
              <i></i> Running
            </button>
            <button
              type="button"
              class="status-seg-btn btn-done"
              :class="{ active: filterStatus === 'selesai' }"
              @click="filterStatus = 'selesai'"
            >
              <i></i> Selesai
            </button>
            <button
              type="button"
              class="status-seg-btn btn-ext"
              :class="{ active: filterStatus === 'extended' }"
              @click="filterStatus = 'extended'"
            >
              <i></i> ⚠️ Auto-Extend
            </button>
          </div>
        </div>

        <!-- Search Box -->
        <div style="flex: 1; min-width: 240px; max-width: 400px;">
          <input
            type="text"
            v-model="searchQuery"
            class="form-control"
            placeholder="🔍 Cari No PP atau Nama Produk..."
          />
        </div>
      </div>
    </div>

    <!-- ============ LIST DATA PP (Layout Kartu LKH Sortir) ============ -->
    <div v-if="filteredPpList.length === 0" class="card text-center p-2 text-muted">
      Tidak ada Data Job Order (PP) yang cocok dengan filter pencarian.
    </div>

    <div v-else class="pp-cards-list">
      <article
        v-for="pp in filteredPpList"
        :key="pp.no_pp"
        class="pp-card-item"
        :class="getCardStatusClass(pp)"
      >
        <div class="pp-item-bar"></div>
        <div class="pp-item-body">
          <!-- Col 1: Status & Jam Mulai Masuk Mesin -->
          <div class="col-main-status">
            <span class="badge" :class="getStatusBadgeClass(pp)">
              {{ getStatusText(pp) }}
            </span>
            <div class="time-stamp mt-05">
              <span class="text-muted" style="font-size: 0.72rem; display: block;">Mulai Masuk Mesin:</span>
              <b>{{ formatDateTimeShort(pp.start_datetime) }} WIB</b>
            </div>
          </div>

          <!-- Col 2: Identitas Job Order & Produk -->
          <div class="col-pp-info">
            <div class="d-flex align-center gap-05 flex-wrap">
              <b class="pp-no-badge">{{ pp.no_pp }}</b>
              <span class="badge badge-info" style="font-size: 0.7rem;">
                1 Plano = <b>{{ pp.up_per_plano }} Up</b>
              </span>
              <span v-if="pp.is_extended" class="badge badge-danger" style="font-size: 0.7rem;">
                ⚠️ Auto-Extend
              </span>
            </div>
            <div class="pp-prod-name">{{ pp.produk_nama }}</div>
            <div class="pp-stages-chain">
              <span class="text-muted" style="font-size: 0.72rem;">Alur:</span>
              <span
                v-for="(st, idx) in pp.stages"
                :key="st.id"
                class="stage-chip"
                :class="'chip-' + st.stage"
              >
                {{ st.stage_name }}
              </span>
            </div>
          </div>

          <!-- Col 3: Oplaag Pcs & Plano Druk -->
          <div class="col-qty-metric">
            <span class="metric-lbl">Target Oplaag</span>
            <b class="text-primary" style="font-size: 1.05rem;">{{ formatNumber(pp.oplaag_pcs) }}</b>
            <small class="text-muted">pcs</small>
            <div class="text-muted mt-02" style="font-size: 0.75rem;">
              Plano: <b>{{ formatNumber(pp.total_plano) }}</b>
            </div>
          </div>

          <!-- Col 4: Batas Pengiriman (Deadline) & Muatan Logistik -->
          <div class="col-logistics-info">
            <span class="metric-lbl">Batas Maksimal Pengiriman</span>
            <b class="text-danger" style="font-size: 0.88rem;">
              {{ pp.deadline_datetime || '03 Nov 16:00' }} WIB
            </b>
            <div class="mt-02" style="font-size: 0.76rem; color: #475569;">
              🚚 <b>{{ pp.muatan_pallet || 10 }} Pallet</b> ({{ pp.muatan_koli || 200 }} Koli)
              <div class="text-muted" style="font-size: 0.72rem;">{{ pp.ritase || 1 }} Ritase {{ pp.armada || 'CDD' }}</div>
            </div>
          </div>

          <!-- Col 5: Progress Bar -->
          <div class="col-progress-bar">
            <div class="d-flex justify-between align-center mb-02" style="font-size: 0.75rem;">
              <span class="text-muted">Progress</span>
              <b>{{ pp.overallProgress.toFixed(0) }}%</b>
            </div>
            <div class="progress-track">
              <div
                class="progress-fill"
                :class="pp.overallProgress >= 100 ? 'fill-done' : 'fill-active'"
                :style="{ width: Math.min(pp.overallProgress, 100) + '%' }"
              ></div>
            </div>
          </div>

          <!-- Col 6: Tombol Aksi (Lihat & Edit) -->
          <div class="col-action-btns">
            <button
              type="button"
              class="btn btn-secondary btn-sm"
              @click="openViewModal(pp)"
              style="padding: 0.35rem 0.75rem;"
            >
              👁️ Detail
            </button>
            <button
              type="button"
              class="btn btn-primary btn-sm"
              @click="openEditModal(pp)"
              style="padding: 0.35rem 0.75rem;"
            >
              ✏️ Edit
            </button>
          </div>
        </div>
      </article>
    </div>

    <!-- ============ MODAL 1: VIEW DETAIL PP ============ -->
    <div v-if="selectedPp" class="modal-overlay" @click.self="selectedPp = null">
      <div class="modal-card">
        <header class="modal-card-head">
          <div>
            <div class="d-flex align-center gap-05">
              <span class="badge badge-primary" style="font-size: 0.85rem; font-weight: 800;">{{ selectedPp.no_pp }}</span>
              <span class="badge" :class="getStatusBadgeClass(selectedPp)">{{ getStatusText(selectedPp) }}</span>
            </div>
            <h3 style="margin: 6px 0 0; font-size: 1.15rem; color: #0f172a;">{{ selectedPp.produk_nama }}</h3>
          </div>
          <button class="modal-close-btn" @click="selectedPp = null">✕</button>
        </header>

        <div class="modal-card-body">
          <!-- Auto-Extend Banner jika ada -->
          <div v-if="selectedPp.is_extended" class="alert-extend-box">
            <b>⚠️ PERINGATAN JADWAL: TERDAPAT TAHAP YANG AUTO-EXTEND (MOLOR)</b>
            <p style="margin: 4px 0 0; font-size: 0.78rem;">
              Kapasitas produksi mesin belum mencapai target waktu rencana. Jadwal otomatis diperpanjang per jam.
            </p>
          </div>

          <!-- Bagian 1: Informasi Oplaag & Plano -->
          <h5 class="sub-sec-head">1. Spesifikasi Pesanan &amp; Kalkulasi Plano</h5>
          <div class="grid grid-3 gap-08 mb-1">
            <div class="info-tile">
              <span class="tile-lbl">Target Oplaag:</span>
              <b class="text-primary">{{ formatNumber(selectedPp.oplaag_pcs) }} Pcs</b>
            </div>
            <div class="info-tile">
              <span class="tile-lbl">Up per Plano:</span>
              <b>{{ selectedPp.up_per_plano }} Box / Lembar</b>
            </div>
            <div class="info-tile">
              <span class="tile-lbl">Total Plano Druk:</span>
              <b class="text-success">{{ formatNumber(selectedPp.total_plano) }} Plano</b>
            </div>
          </div>

          <!-- Bagian 2: Waktu Masuk & Rangkaian Mesin -->
          <h5 class="sub-sec-head">2. Jam Masuk Mesin &amp; Rangkaian Pengerjaan (Presisi Menit)</h5>
          <div class="info-tile mb-08" style="background: #eff6ff; border-color: #bfdbfe;">
            <div class="d-flex justify-between align-center flex-wrap">
              <span>Mulai Masuk Mesin Pertama:</span>
              <b class="text-primary" style="font-size: 0.95rem;">{{ selectedPp.start_datetime }} WIB</b>
            </div>
          </div>

          <div class="stages-timeline-table mb-1">
            <table class="table" style="font-size: 0.8rem;">
              <thead>
                <tr>
                  <th>Tahap</th>
                  <th>Mesin Alokasi</th>
                  <th>Durasi</th>
                  <th>Mulai</th>
                  <th>Selesai</th>
                  <th>Progress</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="st in selectedPp.stages" :key="st.id">
                  <td><b>{{ st.stage.toUpperCase() }}</b></td>
                  <td>{{ st.mesin_nama }}</td>
                  <td><b>{{ formatMinutesDisplay(st.duration_minutes) }}</b></td>
                  <td>{{ formatDateTimeShort(st.start_datetime) }}</td>
                  <td>{{ formatDateTimeShort(st.planned_end_datetime) }}</td>
                  <td>
                    <span class="badge" :class="st.progress >= 100 ? 'badge-success' : 'badge-primary'">
                      {{ st.progress.toFixed(0) }}%
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Bagian 3: Batas Pengiriman & Rincian Muatan Logistik -->
          <h5 class="sub-sec-head">3. Batas Pengiriman &amp; Rincian Muatan Logistik</h5>
          <div class="grid grid-2 gap-08 mb-05">
            <div class="info-tile" style="background: #fef2f2; border-color: #fecaca;">
              <span class="tile-lbl">Batas Maksimal Pengiriman:</span>
              <b class="text-danger" style="font-size: 0.95rem;">
                {{ selectedPp.deadline_datetime || '03 Nov 2026 16:00' }} WIB
              </b>
            </div>
            <div class="info-tile" style="background: #f0fdf4; border-color: #bbf7d0;">
              <span class="tile-lbl">Status Pengiriman:</span>
              <b class="text-success" style="font-size: 0.95rem;">🟢 On-Schedule (Tepat Waktu)</b>
            </div>
          </div>

          <div class="grid grid-3 gap-08">
            <div class="info-tile">
              <span class="tile-lbl">Muatan Kardus/Koli:</span>
              <b>{{ formatNumber(selectedPp.muatan_koli || 200) }} Koli</b>
            </div>
            <div class="info-tile">
              <span class="tile-lbl">Muatan Pallet:</span>
              <b>{{ selectedPp.muatan_pallet || 10 }} Pallet</b>
            </div>
            <div class="info-tile">
              <span class="tile-lbl">Armada Ekspedisi:</span>
              <b>{{ selectedPp.ritase || 1 }} Ritase {{ selectedPp.armada || 'CDD' }}</b>
            </div>
          </div>
        </div>

        <footer class="modal-card-foot">
          <button
            type="button"
            class="btn btn-secondary btn-sm"
            @click="openEditModal(selectedPp); selectedPp = null"
          >
            ✏️ Edit PP Ini
          </button>
          <button type="button" class="btn btn-primary btn-sm" @click="selectedPp = null">
            Tutup
          </button>
        </footer>
      </div>
    </div>

    <!-- ============ MODAL 2: EDIT DATA PP ============ -->
    <div v-if="editingPp" class="modal-overlay" @click.self="editingPp = null">
      <div class="modal-card">
        <header class="modal-card-head">
          <div>
            <div class="d-flex align-center gap-05">
              <span class="badge badge-primary">EDIT JOB ORDER</span>
              <span class="badge badge-secondary">{{ editForm.no_pp }}</span>
            </div>
            <h3 style="margin: 6px 0 0; font-size: 1.15rem; color: #0f172a;">Edit Parameter Job Order (PP)</h3>
          </div>
          <button class="modal-close-btn" @click="editingPp = null">✕</button>
        </header>

        <form @submit.prevent="saveEditPp" class="modal-card-body">
          <div class="grid grid-2 gap-1 mb-1">
            <div class="form-group">
              <label>Nomor Job Order (PP)</label>
              <input type="text" :value="editForm.no_pp" class="form-control" disabled style="background: #f1f5f9; font-weight: 700;" />
            </div>
            <div class="form-group">
              <label>Nama Produk <span class="text-danger">*</span></label>
              <input type="text" v-model="editForm.produk_nama" class="form-control" required />
            </div>
          </div>

          <div class="grid grid-3 gap-1 mb-1">
            <div class="form-group">
              <label>Target Oplaag (Pcs) <span class="text-danger">*</span></label>
              <input type="number" v-model.number="editForm.oplaag_pcs" class="form-control" required min="100" />
            </div>
            <div class="form-group">
              <label>1 Plano Isi (Up) <span class="text-danger">*</span></label>
              <input type="number" v-model.number="editForm.up_per_plano" class="form-control" required min="1" max="200" />
            </div>
            <div class="form-group">
              <label>Hitungan Plano Netto</label>
              <input
                type="text"
                :value="formatNumber(Math.ceil((editForm.oplaag_pcs || 0) / (editForm.up_per_plano || 1))) + ' plano'"
                class="form-control"
                disabled
                style="background: #f8fafc; font-weight: 700; color: #1e40af;"
              />
            </div>
          </div>

          <div class="grid grid-2 gap-1 mb-1">
            <div class="form-group">
              <label>Jam &amp; Tanggal Mulai Masuk Mesin <span class="text-danger">*</span></label>
              <input type="text" v-model="editForm.start_datetime" class="form-control" required placeholder="YYYY-MM-DD HH:mm" />
              <small class="text-muted">Format: YYYY-MM-DD HH:mm (contoh: 2026-11-01 08:00)</small>
            </div>
            <div class="form-group">
              <label>Batas Maksimal Pengiriman (Deadline) <span class="text-danger">*</span></label>
              <input type="text" v-model="editForm.deadline_datetime" class="form-control" required placeholder="YYYY-MM-DD HH:mm" />
              <small class="text-muted">Format: YYYY-MM-DD HH:mm (contoh: 2026-11-03 16:00)</small>
            </div>
          </div>

          <div class="grid grid-3 gap-1 mb-1">
            <div class="form-group">
              <label>Muatan Koli (Dus)</label>
              <input type="number" v-model.number="editForm.muatan_koli" class="form-control" min="1" />
            </div>
            <div class="form-group">
              <label>Muatan Pallet</label>
              <input type="number" v-model.number="editForm.muatan_pallet" class="form-control" min="1" />
            </div>
            <div class="form-group">
              <label>Armada Ekspedisi</label>
              <input type="text" v-model="editForm.armada" class="form-control" placeholder="Contoh: CDD Double" />
            </div>
          </div>

          <footer class="modal-card-foot" style="padding: 1rem 0 0; margin-top: 1rem; border-top: 1px solid #e2e8f0;">
            <button type="button" class="btn btn-secondary" @click="editingPp = null">
              Batal
            </button>
            <button type="submit" class="btn btn-primary" style="font-weight: 700; padding: 0.5rem 1.5rem;">
              💾 Simpan Perubahan PP
            </button>
          </footer>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  schedulesState,
  evaluatedSchedules,
  updatePpicJobOrder,
  formatMinutesToHuman,
  fetchSchedulesFromApi
} from '../../data/schedulePPIC'
import { showAlert } from '../../view/modal/modalService'

onMounted(() => {
  fetchSchedulesFromApi()
})

const filterStatus = ref('')
const searchQuery = ref('')
const selectedPp = ref(null)
const editingPp = ref(null)

const editForm = ref({
  no_pp: '',
  produk_nama: '',
  oplaag_pcs: 0,
  up_per_plano: 4,
  start_datetime: '',
  deadline_datetime: '',
  muatan_koli: 0,
  muatan_pallet: 0,
  armada: '',
  ritase: 1
})

// Mengelompokkan seluruh task berdasarkan No PP menjadi entitas Job Order tunggal
const uniquePpList = computed(() => {
  const map = new Map()

  evaluatedSchedules.value.forEach(task => {
    const no = task.no_pp
    if (!map.has(no)) {
      map.set(no, {
        no_pp: no,
        produk_nama: task.produk_nama,
        oplaag_pcs: task.oplaag_pcs || 100000,
        up_per_plano: task.up_per_plano || 4,
        total_plano: task.plan_plano || (task.stage === 'cetak' ? task.plan_qty : Math.ceil((task.oplaag_pcs || 100000) / (task.up_per_plano || 4))),
        start_datetime: task.start_datetime || `${task.start_date} 08:00`,
        deadline_datetime: task.deadline_datetime || '2026-11-03 16:00',
        muatan_koli: task.muatan_koli || 200,
        muatan_pallet: task.muatan_pallet || 10,
        armada: task.armada || 'Colt Diesel Double (CDD)',
        ritase: task.ritase || 1,
        stages: [],
        is_extended: false,
        totalProgressSum: 0
      })
    }

    const item = map.get(no)
    item.stages.push({
      id: task.id,
      stage: task.stage,
      stage_name: task.stage.toUpperCase(),
      mesin_nama: task.mesin_nama,
      duration_minutes: task.duration_minutes || 180,
      start_datetime: task.start_datetime || task.start_date,
      planned_end_datetime: task.planned_end_datetime || task.planned_end_date,
      progress: task.progress || 0
    })
    item.totalProgressSum += (task.progress || 0)
    if (task.is_extended) item.is_extended = true
  })

  // Hitung overall status dan progress rata-rata
  return Array.from(map.values()).map(pp => {
    const avgProg = pp.stages.length > 0 ? (pp.totalProgressSum / pp.stages.length) : 0
    let status = 'antri'
    if (avgProg >= 100) {
      status = 'selesai'
    } else if (avgProg > 0) {
      status = 'running'
    }
    if (pp.is_extended) {
      status = 'extended'
    }

    return {
      ...pp,
      overallProgress: avgProg,
      computedStatus: status
    }
  })
})

// Filter data list
const filteredPpList = computed(() => {
  return uniquePpList.value.filter(pp => {
    if (filterStatus.value && pp.computedStatus !== filterStatus.value) return false
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase()
      const mNo = pp.no_pp.toLowerCase().includes(q)
      const mProd = pp.produk_nama.toLowerCase().includes(q)
      if (!mNo && !mProd) return false
    }
    return true
  })
})

// KPI Totals
const totalOplaagAll = computed(() => {
  return uniquePpList.value.reduce((acc, p) => acc + (p.oplaag_pcs || 0), 0)
})

const totalPlanoAll = computed(() => {
  return uniquePpList.value.reduce((acc, p) => acc + (p.total_plano || 0), 0)
})

const totalExtendedPpCount = computed(() => {
  return uniquePpList.value.filter(p => p.is_extended).length
})

// Helpers & Styling
const getCardStatusClass = (pp) => {
  if (pp.computedStatus === 'selesai') return 'card-done'
  if (pp.computedStatus === 'extended') return 'card-extended'
  if (pp.computedStatus === 'running') return 'card-running'
  return 'card-queue'
}

const getStatusBadgeClass = (pp) => {
  if (pp.computedStatus === 'selesai') return 'badge-success'
  if (pp.computedStatus === 'extended') return 'badge-danger'
  if (pp.computedStatus === 'running') return 'badge-primary'
  return 'badge-secondary'
}

const getStatusText = (pp) => {
  if (pp.computedStatus === 'selesai') return 'Selesai 100%'
  if (pp.computedStatus === 'extended') return '⚠️ Auto-Extend'
  if (pp.computedStatus === 'running') return 'Sedang Running'
  return 'Antri Terjadwal'
}

const formatNumber = (num) => {
  if (num === null || num === undefined) return '0'
  return Number(num).toLocaleString('id-ID')
}

const formatDateTimeShort = (dtStr) => {
  if (!dtStr) return '-'
  return dtStr
}

const formatMinutesDisplay = (minutes) => {
  if (!minutes) return '0 Menit'
  return formatMinutesToHuman(minutes)
}

// Modal View
const openViewModal = (pp) => {
  selectedPp.value = pp
}

// Modal Edit
const openEditModal = (pp) => {
  editingPp.value = pp
  editForm.value = {
    no_pp: pp.no_pp,
    produk_nama: pp.produk_nama,
    oplaag_pcs: pp.oplaag_pcs,
    up_per_plano: pp.up_per_plano,
    start_datetime: pp.start_datetime,
    deadline_datetime: pp.deadline_datetime,
    muatan_koli: pp.muatan_koli,
    muatan_pallet: pp.muatan_pallet,
    armada: pp.armada,
    ritase: pp.ritase
  }
}

const saveEditPp = async () => {
  updatePpicJobOrder({
    no_pp: editForm.value.no_pp,
    produk_nama: editForm.value.produk_nama,
    oplaag_pcs: editForm.value.oplaag_pcs,
    up_per_plano: editForm.value.up_per_plano,
    start_datetime: editForm.value.start_datetime,
    deadline_datetime: editForm.value.deadline_datetime,
    muatan_koli: editForm.value.muatan_koli,
    muatan_pallet: editForm.value.muatan_pallet,
    armada: editForm.value.armada,
    ritase: editForm.value.ritase
  })

  editingPp.value = null

  await showAlert(
    `Perubahan Job Order ${editForm.value.no_pp} berhasil disimpan!\n` +
    `• Produk: ${editForm.value.produk_nama}\n` +
    `• Oplaag: ${formatNumber(editForm.value.oplaag_pcs)} pcs (${editForm.value.up_per_plano} Up)\n` +
    `• Jam Masuk: ${editForm.value.start_datetime} WIB\n` +
    `• Batas Kirim: ${editForm.value.deadline_datetime} WIB`,
    {
      title: 'Job Order Berhasil Diperbarui',
      type: 'success'
    }
  )
}
</script>

<style scoped>
.data-pp-view {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

/* KPI Mini Cards */
.kpi-mini-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.7rem 0.9rem;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.kpi-mini-card span {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.02em;
}

/* Status Filter Segments (Seperti LKH Sortir) */
.status-seg-group {
  display: inline-flex;
  background: #f1f5f9;
  border-radius: 8px;
  padding: 3px;
  gap: 3px;
}
.status-seg-btn {
  border: none;
  background: transparent;
  padding: 6px 12px;
  font-size: 0.78rem;
  font-weight: 700;
  color: #64748b;
  border-radius: 6px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  transition: all 0.15s;
}
.status-seg-btn i {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}
.status-seg-btn.btn-proc i { background: #4f46e5; }
.status-seg-btn.btn-done i { background: #10b981; }
.status-seg-btn.btn-ext i { background: #dc2626; }

.status-seg-btn.active {
  background: #ffffff;
  color: #0f172a;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
}

/* PP Cards List (Model lv-item Sortir) */
.pp-cards-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.pp-card-item {
  position: relative;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.05);
  overflow: hidden;
  transition: all 0.2s ease;
}
.pp-card-item:hover {
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
  border-color: #cbd5e1;
}

.pp-item-bar {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  width: 5px;
}
.card-done .pp-item-bar { background: #10b981; }
.card-running .pp-item-bar { background: #4f46e5; }
.card-extended .pp-item-bar { background: #dc2626; }
.card-queue .pp-item-bar { background: #94a3b8; }

.pp-item-body {
  padding: 12px 16px 12px 20px;
  display: grid;
  grid-template-columns: 140px 1.5fr 1fr 1.3fr 130px 140px;
  align-items: center;
  gap: 1rem;
}

@media (max-width: 1024px) {
  .pp-item-body {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }
}

.col-main-status {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
}
.pp-no-badge {
  background: #eff6ff;
  color: #1e40af;
  font-size: 0.88rem;
  padding: 2px 8px;
  border-radius: 6px;
}
.pp-prod-name {
  font-weight: 700;
  font-size: 0.88rem;
  color: #1e293b;
  margin-top: 3px;
}
.pp-stages-chain {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-wrap: wrap;
  margin-top: 4px;
}
.stage-chip {
  font-size: 0.65rem;
  font-weight: 800;
  padding: 1px 5px;
  border-radius: 4px;
}
.chip-cetak { background: #dbeafe; color: #1e40af; }
.chip-completing { background: #fef3c7; color: #92400e; }
.chip-cabut { background: #ffedd5; color: #9a3412; }
.chip-sortir { background: #dcfce7; color: #166534; }
.chip-lipat { background: #e0f2fe; color: #0369a1; }

.col-qty-metric {
  display: flex;
  flex-direction: column;
}
.metric-lbl {
  font-size: 0.72rem;
  color: #64748b;
  font-weight: 600;
}
.col-logistics-info {
  display: flex;
  flex-direction: column;
}

.col-progress-bar {
  display: flex;
  flex-direction: column;
}
.progress-track {
  width: 100%;
  height: 8px;
  background: #e2e8f0;
  border-radius: 99px;
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  border-radius: 99px;
  transition: width 0.3s ease;
}
.fill-active { background: #4f46e5; }
.fill-done { background: #10b981; }

.col-action-btns {
  display: flex;
  gap: 6px;
  justify-content: flex-end;
}

/* Modals */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(2px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.modal-card {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 680px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}
.modal-card-head {
  padding: 1rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}
.modal-close-btn {
  border: none;
  background: transparent;
  font-size: 1.25rem;
  color: #64748b;
  cursor: pointer;
}
.modal-card-body {
  padding: 1.25rem;
  overflow-y: auto;
}
.modal-card-foot {
  padding: 0.85rem 1.25rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.sub-sec-head {
  font-size: 0.82rem;
  font-weight: 800;
  color: #334155;
  margin: 0 0 0.5rem;
  padding-bottom: 3px;
  border-bottom: 1px solid #e2e8f0;
  text-transform: uppercase;
}
.info-tile {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 0.5rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: 0.82rem;
}
.tile-lbl {
  font-size: 0.7rem;
  color: #64748b;
  font-weight: 600;
}
.alert-extend-box {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
  padding: 0.75rem;
  border-radius: 6px;
  margin-bottom: 1rem;
}
</style>
