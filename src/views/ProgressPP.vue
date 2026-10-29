<template>
  <div>
    <!-- Page Header -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-05">
        <div>
          <h2 class="card-title" style="margin:0; border:none; padding:0;">
            <span>📈</span> Progress Data Job Order (PP)
          </h2>
          <small class="text-muted">
            Monitoring pencapaian sortir terhadap kuota cetak dalam 3 tingkatan (S1 Sortir Normal, S2 Sortir Ulang, S3 Sortir Ulang ke-3).
          </small>
        </div>
        <div class="d-flex gap-05 flex-wrap">
          <button class="btn btn-secondary btn-sm" @click="loadData" :disabled="loading" title="Refresh Data">
            <span>🔄</span> Refresh
          </button>
          <router-link to="/lkh/baru" class="btn btn-primary btn-sm">
            <span>✍️</span> + LKH Baru
          </router-link>
        </div>
      </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-4 gap-1 mb-1">
      <div class="kpi-card">
        <div class="kpi-label">Total Job Order</div>
        <div class="kpi-value text-primary">{{ list.length }}</div>
        <div class="kpi-sub">Total PP terdaftar di sistem</div>
      </div>
      <div class="kpi-card kpi-success">
        <div class="kpi-label">PP Aktif</div>
        <div class="kpi-value text-success">{{ kpiAktifCount }}</div>
        <div class="kpi-sub">Job Order sedang berjalan</div>
      </div>
      <div class="kpi-card kpi-danger">
        <div class="kpi-label">PP Selesai / Tutup</div>
        <div class="kpi-value text-danger">{{ kpiSelesaiCount }}</div>
        <div class="kpi-sub">Job Order telah ditutup</div>
      </div>
      <div class="kpi-card kpi-warning">
        <div class="kpi-label">Sortir Ulang (S2 / S3)</div>
        <div class="kpi-value text-warning">{{ kpiSortirUlangCount }}</div>
        <div class="kpi-sub">PP yang mengalami S2 atau S3</div>
      </div>
    </div>

    <!-- Banner jika ada Request PP yang belum di-ACC -->
    <div v-if="kpiReqCount > 0" class="card mb-1" style="background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; padding: 0.75rem 1rem;">
      <div class="d-flex justify-between align-center flex-wrap gap-05">
        <div style="color: #92400e; font-size: 0.9rem;">
          ⏳ <b>Terdapat {{ kpiReqCount }} Job Order berstatus "Request (req)"</b> yang belum di-ACC oleh Admin. Kuota cetak dan persentase progress berstatus Pending.
        </div>
        <router-link to="/master" class="btn btn-warning btn-sm" style="font-weight: 600;">
          Ke Master Data untuk ACC
        </router-link>
      </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="card mb-1" style="background: var(--bg); border: 1px solid var(--border); padding: 0.85rem;">
      <div class="grid grid-4 gap-1 mb-05">
        <div class="form-group" style="margin-bottom: 0;">
          <label>Pencarian</label>
          <input
            type="text"
            v-model="filter.search"
            class="form-control"
            placeholder="Ketik No PP, Produk, Famili..."
          />
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Status PP</label>
          <select v-model="filter.status" class="form-control">
            <option value="">Semua Status</option>
            <option value="aktif">Aktif Saja</option>
            <option value="req">Request PP (Pending ACC)</option>
            <option value="selesai">Selesai (Tutup)</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Tahap Sortir</label>
          <select v-model="filter.tahap" class="form-control">
            <option value="">Semua Tahap</option>
            <option value="s1_only">Hanya S1 (Tanpa Sortir Ulang)</option>
            <option value="has_s2">Memiliki Tahap S2</option>
            <option value="has_s3">Memiliki Tahap S3</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Urutkan Berdasarkan</label>
          <select v-model="filter.sort" class="form-control">
            <option value="progress_desc">Progress S1 Tertinggi</option>
            <option value="progress_asc">Progress S1 Terendah</option>
            <option value="no_pp">Nomor PP (A - Z)</option>
            <option value="target_desc">Kuota Cetak Terbesar</option>
          </select>
        </div>
      </div>

      <div class="d-flex justify-between align-center flex-wrap gap-05" style="border-top: 1px solid var(--border-light); padding-top: 0.5rem; font-size: 0.85rem;">
        <label class="d-flex align-center gap-05" style="cursor: pointer; margin: 0;">
          <input type="checkbox" v-model="showEmptyTiers" style="cursor: pointer;" />
          <span>Tampilkan 3 tingkatan lengkap (S1, S2, S3) meskipun S2/S3 masih 0%</span>
        </label>
        <span class="text-muted">
          Menampilkan <b>{{ filteredList.length }}</b> dari <b>{{ list.length }}</b> Job Order
        </span>
      </div>
    </div>

    <!-- PP Cards List -->
    <div v-if="loading" class="text-center py-2 text-muted">
      Memuat data progress Job Order...
    </div>

    <div v-else-if="filteredList.length === 0" class="card text-center py-2 text-muted">
      Tidak ada Job Order yang sesuai dengan filter pencarian.
    </div>

    <div v-else class="d-flex flex-column gap-1">
      <div v-for="item in filteredList" :key="item.id" class="card pp-progress-card">
        <!-- Top PP Information Header -->
        <div class="d-flex justify-between align-start flex-wrap gap-05 mb-1 pb-05" style="border-bottom: 1px solid var(--border-light);">
          <div>
            <div class="d-flex align-center gap-05 flex-wrap">
              <span class="pp-badge-no">{{ item.no_pp }}</span>
              <span v-if="item.status === 'aktif'" class="badge badge-success">● Aktif</span>
              <span v-else-if="item.status === 'req'" class="badge badge-warning" style="background: #fef3c7; color: #b45309; font-weight: 700;">⏳ Req (Menunggu ACC)</span>
              <span v-else class="badge badge-danger">Selesai (Tutup)</span>
              <span v-if="Number(item.total_s2) > 0" class="badge badge-warning">🔄 Ada S2</span>
              <span v-if="Number(item.total_s3) > 0" class="badge badge-purple">🔄 Ada S3</span>
            </div>
            <h3 style="margin: 0.35rem 0 0 0; font-size: 1.15rem; color: var(--text);">
              {{ item.produk_nama }}
            </h3>
            <div style="font-size: 0.85rem; color: var(--text-muted);">
              Famili: <b>{{ item.famili || '-' }}</b>
            </div>
          </div>

          <!-- Quick Action Buttons -->
          <div class="d-flex gap-05 flex-wrap align-center">
            <router-link
              :to="`/lkh/baru?pp_id=${item.id}&tahap_sortir=S1`"
              class="btn btn-primary btn-sm"
              v-if="item.status !== 'selesai'"
              title="Input LKH untuk PP ini"
            >
              <span>✍️</span> + LKH S1
            </router-link>
            <router-link
              :to="`/lkh/baru?pp_id=${item.id}&tahap_sortir=S2`"
              class="btn btn-warning btn-sm"
              v-if="item.status !== 'selesai'"
              title="Input Sortir Ulang S2"
            >
              <span>🔄</span> + S2
            </router-link>
            <router-link
              :to="`/lkh?no_pp=${encodeURIComponent(item.no_pp)}`"
              class="btn btn-secondary btn-sm"
              title="Lihat riwayat LKH untuk PP ini"
            >
              <span>📋</span> LKH
            </router-link>
          </div>
        </div>

        <!-- Meta Grid -->
        <div class="grid grid-4 gap-05 mb-1 pp-meta-box">
          <div>
            <div class="meta-label">🎯 Kuota Total Cetak</div>
            <div class="meta-value">
              <span v-if="item.status === 'req' || Number(item.plan_total) <= 0" class="badge badge-warning" style="font-size: 0.75rem;">Pending ACC</span>
              <span v-else>{{ formatNumber(item.plan_total) }} pcs</span>
            </div>
          </div>
          <div>
            <div class="meta-label">⚡ Standar Speed</div>
            <div class="meta-value">
              <span v-if="item.status === 'req' || Number(item.std_jph) <= 0" class="badge badge-warning" style="font-size: 0.75rem;">Pending ACC</span>
              <span v-else>{{ formatNumber(item.std_jph) }} pcs/jam</span>
            </div>
          </div>
          <div>
            <div class="meta-label">📋 Riwayat LKH</div>
            <div class="meta-value">{{ item.total_lkh_count || 0 }} Laporan</div>
          </div>
          <div>
            <div class="meta-label">📅 Terakhir Disortir</div>
            <div class="meta-value">{{ item.last_sortir_date || 'Belum Ada' }}</div>
          </div>
        </div>

        <!-- 3-TIER PROGRESS BARS -->
        <div class="d-flex flex-column gap-1">
          <!-- TIER 1: TAHAP S1 (Sortir Normal) -->
          <div class="tier-card tier-s1">
            <div class="d-flex justify-between align-center flex-wrap gap-05 mb-05">
              <div class="d-flex align-center gap-05">
                <span class="stage-pill pill-s1">TAHAP S1</span>
                <span class="tier-title">Sortir Normal</span>
              </div>
              <div class="tier-progress-text">
                <span v-if="item.status === 'req' || Number(item.plan_total) <= 0" class="badge badge-warning" style="font-size: 0.75rem; padding: 3px 8px; font-weight: 700;">
                  Pending (Menunggu ACC Kuota)
                </span>
                <span v-else :class="getPct(item.total_s1, item.plan_total) >= 100 ? 'text-success' : 'text-primary'">
                  <b>{{ formatNumber(item.total_s1) }}</b> / {{ formatNumber(item.plan_total) }} pcs
                  (<b>{{ getPct(item.total_s1, item.plan_total).toFixed(1) }}%</b>)
                </span>
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="tier-bar-bg">
              <div
                v-if="item.status === 'req' || Number(item.plan_total) <= 0"
                class="tier-bar-fill"
                style="width: 100%; background: repeating-linear-gradient(45deg, #fef3c7, #fef3c7 10px, #fde68a 10px, #fde68a 20px); border-radius: 999px;"
                title="Progress Pending menunggu penetapan kuota cetak dari Admin"
              ></div>
              <div
                v-else
                class="tier-bar-fill fill-s1"
                :class="{ 'fill-success': getPct(item.total_s1, item.plan_total) >= 100 }"
                :style="{ width: Math.min(getPct(item.total_s1, item.plan_total), 100) + '%' }"
              ></div>
            </div>

            <!-- Breakdown Detail Chips -->
            <div class="tier-breakdown">
              <span><b>Good:</b> {{ formatNumber(item.good_s1) }} pcs <template v-if="item.status !== 'req' && Number(item.plan_total) > 0">({{ getSubPct(item.good_s1, item.total_s1).toFixed(1) }}%)</template></span>
              <span>•</span>
              <span><b>Reject:</b> {{ formatNumber(item.reject_s1) }} pcs <template v-if="item.status !== 'req' && Number(item.plan_total) > 0">({{ getSubPct(item.reject_s1, item.total_s1).toFixed(1) }}%)</template></span>
              <span>•</span>
              <span v-if="item.status === 'req' || Number(item.plan_total) <= 0" class="text-warning font-bold">
                Sisa Target: Menunggu penetapan Kuota Cetak
              </span>
              <span v-else><b>Sisa Target S1:</b> {{ formatNumber(Math.max(0, item.plan_total - item.total_s1)) }} pcs</span>
            </div>
          </div>

          <!-- TIER 2: TAHAP S2 (Sortir Ulang Ke-2) -->
          <div
            v-if="Number(item.total_s2) > 0 || showEmptyTiers"
            class="tier-card tier-s2"
          >
            <div class="d-flex justify-between align-center flex-wrap gap-05 mb-05">
              <div class="d-flex align-center gap-05">
                <span class="stage-pill pill-s2">TAHAP S2</span>
                <span class="tier-title">Sortir Ulang Ke-2</span>
                <span v-if="Number(item.total_s2) === 0" class="badge" style="background: #f1f5f9; color: #64748b; font-size: 0.7rem;">Belum Ada Data</span>
              </div>
              <div class="tier-progress-text">
                <span v-if="item.status === 'req' || Number(item.plan_total) <= 0" class="badge badge-warning" style="font-size: 0.75rem; padding: 3px 8px;">
                  Pending (Menunggu Kuota)
                </span>
                <span v-else style="color: #b45309;">
                  <b>{{ formatNumber(item.total_s2) }}</b> / {{ formatNumber(item.plan_total) }} pcs
                  (<b>{{ getPct(item.total_s2, item.plan_total).toFixed(1) }}%</b>)
                </span>
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="tier-bar-bg">
              <div
                v-if="item.status === 'req' || Number(item.plan_total) <= 0"
                class="tier-bar-fill"
                style="width: 100%; background: repeating-linear-gradient(45deg, #fef3c7, #fef3c7 10px, #fde68a 10px, #fde68a 20px); border-radius: 999px;"
              ></div>
              <div
                v-else
                class="tier-bar-fill fill-s2"
                :style="{ width: Math.min(getPct(item.total_s2, item.plan_total), 100) + '%' }"
              ></div>
            </div>

            <!-- Breakdown Detail Chips -->
            <div class="tier-breakdown">
              <span><b>Good S2:</b> {{ formatNumber(item.good_s2) }} pcs</span>
              <span>•</span>
              <span><b>Reject S2:</b> {{ formatNumber(item.reject_s2) }} pcs</span>
              <span>•</span>
              <span class="text-muted">Batas acuan: {{ item.status === 'req' || Number(item.plan_total) <= 0 ? 'Pending ACC' : formatNumber(item.plan_total) + ' pcs' }}</span>
            </div>
          </div>

          <!-- TIER 3: TAHAP S3 (Sortir Ulang Ke-3) -->
          <div
            v-if="Number(item.total_s3) > 0 || showEmptyTiers"
            class="tier-card tier-s3"
          >
            <div class="d-flex justify-between align-center flex-wrap gap-05 mb-05">
              <div class="d-flex align-center gap-05">
                <span class="stage-pill pill-s3">TAHAP S3</span>
                <span class="tier-title">Sortir Ulang Ke-3</span>
                <span v-if="Number(item.total_s3) === 0" class="badge" style="background: #f1f5f9; color: #64748b; font-size: 0.7rem;">Belum Ada Data</span>
              </div>
              <div class="tier-progress-text">
                <span v-if="item.status === 'req' || Number(item.plan_total) <= 0" class="badge badge-warning" style="font-size: 0.75rem; padding: 3px 8px;">
                  Pending (Menunggu Kuota)
                </span>
                <span v-else style="color: #6d28d9;">
                  <b>{{ formatNumber(item.total_s3) }}</b> / {{ formatNumber(item.plan_total) }} pcs
                  (<b>{{ getPct(item.total_s3, item.plan_total).toFixed(1) }}%</b>)
                </span>
              </div>
            </div>

            <!-- Progress Bar -->
            <div class="tier-bar-bg">
              <div
                v-if="item.status === 'req' || Number(item.plan_total) <= 0"
                class="tier-bar-fill"
                style="width: 100%; background: repeating-linear-gradient(45deg, #fef3c7, #fef3c7 10px, #fde68a 10px, #fde68a 20px); border-radius: 999px;"
              ></div>
              <div
                v-else
                class="tier-bar-fill fill-s3"
                :style="{ width: Math.min(getPct(item.total_s3, item.plan_total), 100) + '%' }"
              ></div>
            </div>

            <!-- Breakdown Detail Chips -->
            <div class="tier-breakdown">
              <span><b>Good S3:</b> {{ formatNumber(item.good_s3) }} pcs</span>
              <span>•</span>
              <span><b>Reject S3:</b> {{ formatNumber(item.reject_s3) }} pcs</span>
              <span>•</span>
              <span class="text-muted">Batas acuan: {{ item.status === 'req' || Number(item.plan_total) <= 0 ? 'Pending ACC' : formatNumber(item.plan_total) + ' pcs' }}</span>
            </div>
          </div>

          <!-- Subtle Info when no S2/S3 yet -->
          <div
            v-if="Number(item.total_s2) === 0 && Number(item.total_s3) === 0 && !showEmptyTiers"
            class="d-flex align-center gap-05"
            style="font-size: 0.78rem; color: #64748b; padding: 0.25rem 0;"
          >
            <span>ℹ️</span>
            <span>Hanya terdapat proses Sortir Normal (S1). Belum ada riwayat sortir ulang S2 atau S3.</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { apiFetch } from '../utils/api'

const list = ref([])
const loading = ref(false)
const showEmptyTiers = ref(false)

const filter = ref({
  search: '',
  status: '',
  tahap: '',
  sort: 'progress_desc'
})

const formatNumber = (val) => {
  if (val === undefined || val === null || isNaN(val)) return '0'
  return Number(val).toLocaleString('id-ID')
}

const getPct = (current, target) => {
  const c = Number(current) || 0
  const t = Number(target) || 0
  if (t <= 0) return 0
  return (c / t) * 100
}

const getSubPct = (sub, total) => {
  const s = Number(sub) || 0
  const tot = Number(total) || 0
  if (tot <= 0) return 0
  return (s / tot) * 100
}

const loadData = async () => {
  loading.value = true
  try {
    const res = await apiFetch('lkh.php?action=progress_pp')
    list.value = res || []
  } catch (err) {
    console.error('Gagal memuat progress data PP', err)
    alert('Gagal memuat data progress: ' + err.message)
  } finally {
    loading.value = false
  }
}

// KPI Counts
const kpiAktifCount = computed(() => list.value.filter(x => x.status === 'aktif').length)
const kpiReqCount = computed(() => list.value.filter(x => x.status === 'req').length)
const kpiSelesaiCount = computed(() => list.value.filter(x => x.status === 'selesai').length)
const kpiSortirUlangCount = computed(() => list.value.filter(x => Number(x.total_s2) > 0 || Number(x.total_s3) > 0).length)

// Filter & Sort Logic
const filteredList = computed(() => {
  return list.value
    .filter(item => {
      // Search
      if (filter.value.search) {
        const q = filter.value.search.trim().toLowerCase()
        const noPp = (item.no_pp || '').toLowerCase()
        const prod = (item.produk_nama || '').toLowerCase()
        const fam = (item.famili || '').toLowerCase()
        if (!noPp.includes(q) && !prod.includes(q) && !fam.includes(q)) {
          return false
        }
      }
      // Status
      if (filter.value.status) {
        if (item.status !== filter.value.status) return false
      }
      // Tahap
      if (filter.value.tahap) {
        const s2 = Number(item.total_s2) || 0
        const s3 = Number(item.total_s3) || 0
        if (filter.value.tahap === 's1_only' && (s2 > 0 || s3 > 0)) return false
        if (filter.value.tahap === 'has_s2' && s2 <= 0) return false
        if (filter.value.tahap === 'has_s3' && s3 <= 0) return false
      }
      return true
    })
    .sort((a, b) => {
      if (filter.value.sort === 'progress_desc') {
        return getPct(b.total_s1, b.plan_total) - getPct(a.total_s1, a.plan_total)
      } else if (filter.value.sort === 'progress_asc') {
        return getPct(a.total_s1, a.plan_total) - getPct(b.total_s1, b.plan_total)
      } else if (filter.value.sort === 'no_pp') {
        return (a.no_pp || '').localeCompare(b.no_pp || '')
      } else if (filter.value.sort === 'target_desc') {
        return (Number(b.plan_total) || 0) - (Number(a.plan_total) || 0)
      }
      return 0
    })
})

onMounted(loadData)
</script>

<style scoped>
.pp-progress-card {
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.pp-progress-card:hover {
  box-shadow: var(--shadow-md);
}

.pp-badge-no {
  font-family: inherit;
  font-weight: 800;
  font-size: 1.05rem;
  color: var(--primary);
  background: var(--primary-light);
  padding: 0.2rem 0.6rem;
  border-radius: var(--radius-sm);
  border: 1px solid rgba(79, 70, 229, 0.2);
}

.pp-meta-box {
  background: var(--bg);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-md);
  padding: 0.65rem 0.85rem;
}

.meta-label {
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  color: var(--text-muted);
  letter-spacing: 0.03em;
}

.meta-value {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--text);
  margin-top: 1px;
}

/* Tier Progress Cards */
.tier-card {
  padding: 0.75rem 0.9rem;
  border-radius: var(--radius-md);
  border: 1px solid transparent;
}

.tier-s1 {
  background: #f6f6fd;
  border-color: #d8dbee;
}

.tier-s2 {
  background: #fffbeb;
  border-color: #fde68a;
}

.tier-s3 {
  background: #f5f3ff;
  border-color: #ddd6fe;
}

.stage-pill {
  font-size: 0.72rem;
  font-weight: 800;
  padding: 0.15rem 0.5rem;
  border-radius: var(--radius-full);
  letter-spacing: 0.03em;
}

.pill-s1 {
  background: #e0e7ff;
  color: #4338ca;
}

.pill-s2 {
  background: #fef3c7;
  color: #b45309;
}

.pill-s3 {
  background: #ede9fe;
  color: #6d28d9;
}

.tier-title {
  font-weight: 700;
  font-size: 0.88rem;
  color: var(--text);
}

.tier-progress-text {
  font-size: 0.85rem;
}

.tier-bar-bg {
  width: 100%;
  height: 12px;
  background-color: #e2e8f0;
  border-radius: var(--radius-full);
  overflow: hidden;
  margin: 0.4rem 0 0.5rem 0;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.06);
}

.tier-bar-fill {
  height: 100%;
  border-radius: var(--radius-full);
  transition: width 0.4s ease;
}

.fill-s1 {
  background: linear-gradient(90deg, #6366f1, #4f46e5);
}

.fill-s1.fill-success {
  background: linear-gradient(90deg, #22c55e, #16a34a);
}

.fill-s2 {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}

.fill-s3 {
  background: linear-gradient(90deg, #a78bfa, #7c3aed);
}

.tier-breakdown {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  flex-wrap: wrap;
  font-size: 0.78rem;
  color: #475569;
}

@media (max-width: 767px) {
  .pp-meta-box {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
