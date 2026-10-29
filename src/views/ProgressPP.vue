<template>
  <div class="pv">

    <!-- ============ FILTER BAR ============ -->
    <div class="pv-filter">
      <div class="pv-filter-card">
        <div class="pv-filter-row">
          <div class="pv-title">
            <h2>Progress Job Order (PP)</h2>
            <p>Pencapaian sortir terhadap kuota cetak: S1 normal, S2 sortir ulang, S3 sortir ulang ke-3</p>
          </div>
          <div class="pv-spacer"></div>
          <button
            type="button"
            class="pv-btn pv-btn-ghost"
            :class="{ on: showAdvancedFilter || activeExtraFilterCount > 0 }"
            @click="showAdvancedFilter = !showAdvancedFilter"
            title="Buka / tutup filter pencarian dan pengurutan"
          >
            Filter &amp; Cari <span v-if="activeExtraFilterCount" class="pv-badge-count">{{ activeExtraFilterCount }}</span>
            <span>{{ showAdvancedFilter ? '▲' : '▼' }}</span>
          </button>
        </div>

        <!-- Mode status yang tampil awal: Semua, Aktif, Request, Selesai -->
        <div class="pv-filter-row pv-filter-row-status">
          <div class="pv-seg">
            <button type="button" :class="{ on: filter.status === '' }" @click="filter.status = ''">
              Semua <span class="pv-seg-count">({{ list.length }})</span>
            </button>
            <button type="button" class="st-aktif" :class="{ on: filter.status === 'aktif' }" @click="filter.status = 'aktif'">
              Aktif <span class="pv-seg-count">({{ kpiAktifCount }})</span>
            </button>
            <button type="button" class="st-req" :class="{ on: filter.status === 'req' }" @click="filter.status = 'req'">
              Request <span class="pv-seg-count">({{ kpiReqCount }})</span>
            </button>
            <button type="button" class="st-selesai" :class="{ on: filter.status === 'selesai' }" @click="filter.status = 'selesai'">
              Selesai <span class="pv-seg-count">({{ kpiSelesaiCount }})</span>
            </button>
          </div>
          <div class="pv-spacer"></div>
          <span class="pv-muted pv-count-label">
            Menampilkan <b>{{ filteredList.length }}</b> dari <b>{{ list.length }}</b> Job Order
          </span>
        </div>

        <!-- Filter tambahan (tersembunyi secara default) -->
        <div v-show="showAdvancedFilter" class="pv-filter-advanced">
          <div class="pv-filter-grid">
            <div class="pv-field">
              <label>Cari Job Order</label>
              <input
                type="text"
                v-model="filter.search"
                class="pv-input pv-search"
                placeholder="Cari No PP, nama produk, famili..."
              />
            </div>
            <div class="pv-field">
              <label>Tahap sortir</label>
              <select v-model="filter.tahap" class="pv-input">
                <option value="">Semua tahap</option>
                <option value="s1_only">Hanya S1</option>
                <option value="has_s2">Memiliki S2</option>
                <option value="has_s3">Memiliki S3</option>
              </select>
            </div>
            <div class="pv-field">
              <label>Urutan tampilan</label>
              <select v-model="filter.sort" class="pv-input">
                <option value="progress_desc">Progress S1 tertinggi</option>
                <option value="progress_asc">Progress S1 terendah</option>
                <option value="no_pp">Nomor PP (A-Z)</option>
                <option value="target_desc">Kuota cetak terbesar</option>
              </select>
            </div>
          </div>

          <div class="pv-filter-foot">
            <label class="pv-check">
              <input type="checkbox" v-model="showEmptyTiers" />
              <span>Tampilkan S2 &amp; S3 meski masih kosong</span>
            </label>
            <button
              v-if="activeExtraFilterCount > 0"
              type="button"
              class="pv-btn pv-btn-ghost pv-btn-xs"
              @click="resetExtraFilter"
            >
              ✕ Reset filter tambahan
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ RINGKASAN ============ -->
    <section class="pv-stats">
      <div class="pv-stat">
        <span class="pv-eyebrow">Total job order</span>
        <b>{{ list.length }}</b><small>PP terdaftar</small>
      </div>
      <div class="pv-stat">
        <span class="pv-eyebrow">PP aktif</span>
        <b class="txt-ok">{{ kpiAktifCount }}</b><small>sedang berjalan</small>
      </div>
      <div class="pv-stat">
        <span class="pv-eyebrow">Selesai / tutup</span>
        <b class="txt-bad">{{ kpiSelesaiCount }}</b><small>sudah ditutup</small>
      </div>
      <div class="pv-stat">
        <span class="pv-eyebrow">Sortir ulang (S2/S3)</span>
        <b class="txt-warn">{{ kpiSortirUlangCount }}</b><small>PP terdampak</small>
      </div>
    </section>

    <!-- Banner Request PP -->
    <div v-if="kpiReqCount > 0" class="pv-alert">
      <span class="pv-alert-no">{{ kpiReqCount }}</span>
      <div class="pv-alert-body">
        <b>Job Order berstatus Request belum di-ACC</b>
        <p>Kuota cetak dan persentase progress masih pending sampai Admin menetapkan kuota.</p>
      </div>
      <router-link to="/master" class="pv-btn pv-btn-ghost">Ke Master Data</router-link>
    </div>

    <!-- ============ LIST PP ============ -->
    <div v-if="loading" class="pv-card pv-empty">Memuat data progress Job Order...</div>
    <div v-else-if="filteredList.length === 0" class="pv-card pv-empty">
      Tidak ada Job Order yang sesuai dengan filter.
    </div>

    <div v-else class="pv-list">
      <article v-for="item in filteredList" :key="item.id" class="pv-card">

        <!-- Header -->
        <header class="pv-head">
          <div class="pv-head-main">
            <div class="pv-pills">
              <span class="pv-no">{{ item.no_pp }}</span>
              <span v-if="item.status === 'aktif'" class="pv-pill tone-ok">Aktif</span>
              <span v-else-if="item.status === 'req'" class="pv-pill tone-warn">Request &middot; menunggu ACC</span>
              <span v-else class="pv-pill tone-bad">Selesai</span>
              <span v-if="Number(item.total_s2) > 0" class="pv-pill tone-s2">Ada S2</span>
              <span v-if="Number(item.total_s3) > 0" class="pv-pill tone-s3">Ada S3</span>
            </div>
            <h3>{{ item.produk_nama }}</h3>
            <p>Famili: <b>{{ item.famili || '-' }}</b></p>
          </div>

          <div class="pv-actions">
            <!-- Tombol End Job Order (Khusus Admin & jika PP belum selesai) -->
            <button
              v-if="isAdmin && item.status !== 'selesai'"
              type="button"
              class="pv-btn pv-btn-danger pv-btn-sm"
              :disabled="endingPpId === item.id"
              @click="endJobOrder(item)"
              title="Tutup Job Order ini (Khusus Admin)"
            >
              {{ endingPpId === item.id ? 'Menutup...' : 'End Job Order' }}
            </button>

            <!-- Tombol Riwayat LKH (Pindah ke Data LKH dan memfilter PP ini) -->
            <router-link
              :to="`/lkh?no_pp=${encodeURIComponent(item.no_pp)}`"
              class="pv-btn pv-btn-dark pv-btn-sm"
              title="Lihat riwayat LKH PP ini"
            >Riwayat LKH</router-link>
          </div>
        </header>

        <!-- Meta strip -->
        <div class="pv-meta">
          <div>
            <span class="pv-eyebrow">Kuota total cetak</span>
            <b v-if="isPending(item)" class="pv-pill tone-warn">Pending ACC</b>
            <b v-else>{{ formatNumber(item.plan_total) }} <small>pcs</small></b>
          </div>
          <div>
            <span class="pv-eyebrow">Standar speed</span>
            <b v-if="item.status === 'req' || Number(item.std_jph) <= 0" class="pv-pill tone-warn">Pending ACC</b>
            <b v-else>{{ formatNumber(item.std_jph) }} <small>pcs/jam</small></b>
          </div>
          <div>
            <span class="pv-eyebrow">Riwayat LKH</span>
            <b>{{ item.total_lkh_count || 0 }} <small>laporan</small></b>
          </div>
          <div>
            <span class="pv-eyebrow">Terakhir disortir</span>
            <b>{{ item.last_sortir_date || 'Belum ada' }}</b>
          </div>
        </div>

        <!-- Tiers -->
        <div class="pv-tiers" :class="{ 'pv-tiers-single': !showS2(item) && !showS3(item) }">

          <!-- S1 -->
          <div class="pv-tier">
            <div class="pv-tier-top">
              <span class="pv-tier-tag tag-s1">S1</span>
              <span class="pv-tier-name">Sortir normal</span>
              <span v-if="!isPending(item)" class="pv-tier-pct" :class="pctTone(item.total_s1, item.plan_total)">
                {{ getPct(item.total_s1, item.plan_total).toFixed(1) }}%
              </span>
              <span v-else class="pv-pill tone-warn">Pending</span>
            </div>
            <div class="pv-track" :class="{ pending: isPending(item) }">
              <div
                v-if="!isPending(item)"
                class="pv-fill"
                :class="getPct(item.total_s1, item.plan_total) >= 100 ? 'bg-ok' : 'bg-brand'"
                :style="{ width: Math.min(getPct(item.total_s1, item.plan_total), 100) + '%' }"
              ></div>
              <div v-if="!isPending(item)" class="pv-target-mark" style="left: 100%"></div>
            </div>
            <div class="pv-tier-num">
              <template v-if="!isPending(item)">
                <b>{{ formatNumber(item.total_s1) }}</b> / {{ formatNumber(item.plan_total) }} pcs
              </template>
              <template v-else>Menunggu penetapan kuota cetak</template>
            </div>
            <div class="pv-tier-break">
              <span>Good <b class="txt-ok">{{ formatNumber(item.good_s1) }}</b>
                <i v-if="!isPending(item)">({{ getSubPct(item.good_s1, item.total_s1).toFixed(1) }}%)</i></span>
              <span>Reject <b class="txt-bad">{{ formatNumber(item.reject_s1) }}</b>
                <i v-if="!isPending(item)">({{ getSubPct(item.reject_s1, item.total_s1).toFixed(1) }}%)</i></span>
              <span v-if="!isPending(item)">Sisa <b>{{ formatNumber(Math.max(0, item.plan_total - item.total_s1)) }}</b></span>
            </div>
          </div>

          <!-- S2 -->
          <div v-if="showS2(item)" class="pv-tier">
            <div class="pv-tier-top">
              <span class="pv-tier-tag tag-s2">S2</span>
              <span class="pv-tier-name">Sortir ulang ke-2</span>
              <span v-if="Number(item.total_s2) === 0" class="pv-muted">Belum ada data</span>
              <span v-else-if="!isPending(item)" class="pv-tier-pct txt-warn">
                {{ getPct(item.total_s2, item.plan_total).toFixed(1) }}%
              </span>
              <span v-else class="pv-pill tone-warn">Pending</span>
            </div>
            <div class="pv-track" :class="{ pending: isPending(item) }">
              <div
                v-if="!isPending(item)"
                class="pv-fill bg-s2"
                :style="{ width: Math.min(getPct(item.total_s2, item.plan_total), 100) + '%' }"
              ></div>
            </div>
            <div class="pv-tier-num">
              <template v-if="!isPending(item)">
                <b>{{ formatNumber(item.total_s2) }}</b> / {{ formatNumber(item.plan_total) }} pcs
              </template>
              <template v-else>Menunggu kuota</template>
            </div>
            <div class="pv-tier-break">
              <span>Good <b class="txt-ok">{{ formatNumber(item.good_s2) }}</b></span>
              <span>Reject <b class="txt-bad">{{ formatNumber(item.reject_s2) }}</b></span>
            </div>
          </div>

          <!-- S3 -->
          <div v-if="showS3(item)" class="pv-tier">
            <div class="pv-tier-top">
              <span class="pv-tier-tag tag-s3">S3</span>
              <span class="pv-tier-name">Sortir ulang ke-3</span>
              <span v-if="Number(item.total_s3) === 0" class="pv-muted">Belum ada data</span>
              <span v-else-if="!isPending(item)" class="pv-tier-pct txt-s3">
                {{ getPct(item.total_s3, item.plan_total).toFixed(1) }}%
              </span>
              <span v-else class="pv-pill tone-warn">Pending</span>
            </div>
            <div class="pv-track" :class="{ pending: isPending(item) }">
              <div
                v-if="!isPending(item)"
                class="pv-fill bg-s3"
                :style="{ width: Math.min(getPct(item.total_s3, item.plan_total), 100) + '%' }"
              ></div>
            </div>
            <div class="pv-tier-num">
              <template v-if="!isPending(item)">
                <b>{{ formatNumber(item.total_s3) }}</b> / {{ formatNumber(item.plan_total) }} pcs
              </template>
              <template v-else>Menunggu kuota</template>
            </div>
            <div class="pv-tier-break">
              <span>Good <b class="txt-ok">{{ formatNumber(item.good_s3) }}</b></span>
              <span>Reject <b class="txt-bad">{{ formatNumber(item.reject_s3) }}</b></span>
            </div>
          </div>
        </div>

        <p v-if="!showS2(item) && !showS3(item)" class="pv-note">
          Baru ada proses sortir normal (S1). Belum ada riwayat sortir ulang S2 atau S3.
        </p>
      </article>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { apiFetch, authState } from '../utils/api'

const list = ref([])
const loading = ref(false)
const showEmptyTiers = ref(false)
const showAdvancedFilter = ref(false)
const endingPpId = ref(null)

const isAdmin = computed(() => (authState.value?.role || '').toLowerCase() === 'admin')

const filter = ref({
  search: '',
  status: '',
  tahap: '',
  sort: 'progress_desc'
})

const activeExtraFilterCount = computed(() => {
  let count = 0
  if (filter.value.search) count++
  if (filter.value.tahap) count++
  if (filter.value.sort !== 'progress_desc') count++
  if (showEmptyTiers.value) count++
  return count
})

const resetExtraFilter = () => {
  filter.value.search = ''
  filter.value.tahap = ''
  filter.value.sort = 'progress_desc'
  showEmptyTiers.value = false
}

const endJobOrder = async (item) => {
  if (!isAdmin.value) {
    alert('Akses ditolak. Hanya Admin yang dapat menutup Job Order.')
    return
  }
  const confirmed = confirm(
    `Tutup Job Order (End Job Order)?\n\n` +
    `Nomor PP : ${item.no_pp}\n` +
    `Produk   : ${item.produk_nama}\n` +
    `Total S1 : ${formatNumber(item.total_s1)} / ${formatNumber(item.plan_total)} pcs\n\n` +
    `Status PP akan diubah menjadi 'Selesai' dan operator tidak dapat menginput LKH baru lagi untuk PP ini.`
  )
  if (!confirmed) return

  endingPpId.value = item.id
  try {
    const res = await apiFetch('master.php?action=end_pp', {
      method: 'POST',
      body: JSON.stringify({ id: item.id })
    })
    alert(res.message || `Job Order ${item.no_pp} berhasil ditutup (selesai).`)
    await loadData()
  } catch (err) {
    alert('Gagal menutup Job Order: ' + err.message)
  } finally {
    endingPpId.value = null
  }
}

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

const isPending = (item) => item.status === 'req' || Number(item.plan_total) <= 0
const showS2 = (item) => Number(item.total_s2) > 0 || showEmptyTiers.value
const showS3 = (item) => Number(item.total_s3) > 0 || showEmptyTiers.value
const pctTone = (cur, target) => (getPct(cur, target) >= 100 ? 'txt-ok' : 'txt-brand')

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
      if (filter.value.search) {
        const q = filter.value.search.trim().toLowerCase()
        const noPp = (item.no_pp || '').toLowerCase()
        const prod = (item.produk_nama || '').toLowerCase()
        const fam = (item.famili || '').toLowerCase()
        if (!noPp.includes(q) && !prod.includes(q) && !fam.includes(q)) return false
      }
      if (filter.value.status && item.status !== filter.value.status) return false
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
.pv {
  --c-brand: #4f46e5;
  --c-brand-2: #8b5cf6;
  --c-ok: #10b981;
  --c-warn: #d97706;
  --c-bad: #e11d48;
  --c-s3: #7c3aed;
  --c-ink: #15172b;
  --c-text: #3a3f5c;
  --c-muted: #666c8a;
  --c-line: #e4e5f1;
  --c-soft: #eef0fb;
  color: var(--c-text);
}

.pv-muted { color: var(--c-muted); font-size: 0.78rem; }
.pv-spacer { flex: 1; }
.pv-eyebrow { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--c-muted); }

.txt-ok { color: var(--c-ok); }
.txt-warn { color: var(--c-warn); }
.txt-bad { color: var(--c-bad); }
.txt-brand { color: var(--c-brand); }
.txt-s3 { color: var(--c-s3); }
.bg-ok { background: var(--c-ok); }
.bg-brand { background: linear-gradient(90deg, #6366f1, var(--c-brand)); }
.bg-s2 { background: linear-gradient(90deg, #f59e0b, var(--c-warn)); }
.bg-s3 { background: linear-gradient(90deg, #a78bfa, var(--c-s3)); }

/* ---------- Tombol & input ---------- */
.pv-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  height: 34px; padding: 0 0.85rem;
  font-size: 0.82rem; font-weight: 600; text-decoration: none;
  border-radius: 8px; border: 1px solid transparent;
  cursor: pointer; white-space: nowrap;
  transition: background 0.15s, border-color 0.15s;
}
.pv-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.pv-btn-sm { height: 30px; padding: 0 0.7rem; font-size: 0.78rem; }
.pv-btn-primary { background: linear-gradient(135deg, #6366f1, var(--c-brand)); color: #fff; box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3); }
.pv-btn-primary:hover { background: linear-gradient(135deg, #5457ea, #4338ca); }
.pv-btn-dark { background: linear-gradient(135deg, #272a45, var(--c-ink)); color: #fff; }
.pv-btn-dark:hover { background: #0f1022; }
.pv-btn-danger {
  background: #fee2e2;
  color: #b91c1c;
  border: 1px solid #fca5a5;
}
.pv-btn-danger:hover:not(:disabled) {
  background: #dc2626;
  color: #fff;
  border-color: #dc2626;
  box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
}
.pv-btn-ghost { background: #fff; color: var(--c-text); border-color: var(--c-line); }
.pv-btn-ghost:hover { background: var(--c-soft); }
.pv-btn-ghost.on { background: #eef2ff; color: var(--c-brand); border-color: #c7d2fe; }
.pv-btn-xs { height: 26px; padding: 0 0.55rem; font-size: 0.74rem; }

.pv-badge-count {
  background: var(--c-brand);
  color: #fff;
  font-size: 0.68rem;
  font-weight: 800;
  padding: 1px 6px;
  border-radius: 99px;
  margin-left: 2px;
}

.pv-input {
  height: 34px; padding: 0 0.6rem; font-size: 0.82rem;
  border: 1px solid var(--c-line); border-radius: 8px; background: #fff; color: var(--c-ink);
  min-width: 0;
}
.pv-input:focus { outline: 2px solid rgba(79, 70, 229, 0.2); border-color: var(--c-brand); }
.pv-search { flex: 1; min-width: 200px; }

/* ---------- Filter bar ---------- */
.pv-filter { position: sticky; top: 0; z-index: 25; padding: 4px 0 10px; background: var(--bg, #f4f5fb); }
.pv-filter-card {
  background: #fff; border: 1px solid var(--c-line); border-radius: 14px;
  padding: 0.7rem 0.85rem; box-shadow: 0 2px 10px rgba(21, 23, 55, 0.05);
}
.pv-filter-row { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
.pv-filter-row-status {
  margin-top: 0.6rem;
  padding-top: 0.6rem;
  border-top: 1px solid var(--c-line);
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.pv-title h2 { margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--c-ink); }
.pv-title p { margin: 2px 0 0; font-size: 0.75rem; color: var(--c-muted); }
.pv-count-label {
  font-size: 0.78rem;
  color: var(--c-muted);
}
.pv-seg-count {
  font-size: 0.7rem;
  font-weight: 700;
  opacity: 0.8;
  margin-left: 2px;
}

.pv-filter-advanced {
  margin-top: 0.65rem;
  padding-top: 0.65rem;
  border-top: 1px dashed var(--c-line);
  animation: pvFadeIn 0.2s ease-out;
}
@keyframes pvFadeIn {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}

.pv-filter-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1.2fr;
  gap: 0.6rem;
  align-items: end;
}
.pv-field {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.pv-field label {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--c-muted);
}

.pv-filter-foot {
  display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;
  margin-top: 0.6rem;
}
.pv-check { display: inline-flex; align-items: center; gap: 6px; font-size: 0.78rem; cursor: pointer; color: var(--c-text); }

.pv-seg { display: inline-flex; background: var(--c-soft); border-radius: 8px; padding: 3px; }
.pv-seg button {
  border: none; background: transparent; padding: 0 0.75rem; height: 28px;
  font-size: 0.78rem; font-weight: 600; color: var(--c-muted); border-radius: 6px; cursor: pointer;
  display: inline-flex; align-items: center; justify-content: center;
}
.pv-seg button.on { background: #fff; color: var(--c-brand); box-shadow: 0 1px 2px rgba(15, 23, 42, 0.1); }
.pv-seg button.st-aktif.on { color: #059669; }
.pv-seg button.st-req.on { color: #d97706; }
.pv-seg button.st-selesai.on { color: #475569; }

/* ---------- Stats ---------- */
.pv-stats, .pv-card {
  background: #fff; border: 1px solid var(--c-line); border-radius: 14px;
  box-shadow: 0 2px 10px rgba(21, 23, 55, 0.05);
}
.pv-stats { display: grid; grid-template-columns: repeat(4, 1fr); margin-bottom: 0.9rem; }
.pv-stat { padding: 0.75rem 1.1rem; display: flex; flex-wrap: wrap; align-items: baseline; gap: 0 6px; }
.pv-stat + .pv-stat { border-left: 1px solid var(--c-line); }
.pv-stat .pv-eyebrow { width: 100%; margin-bottom: 2px; }
.pv-stat b { font-size: 1.5rem; font-weight: 800; color: var(--c-ink); letter-spacing: -0.01em; }
.pv-stat small { font-size: 0.76rem; color: var(--c-muted); }

/* ---------- Alert ---------- */
.pv-alert {
  display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;
  padding: 0.7rem 0.9rem; margin-bottom: 0.9rem;
  border-radius: 10px; background: #fffbeb; border-left: 4px solid var(--c-warn);
}
.pv-alert-no {
  flex: none; min-width: 24px; height: 24px; padding: 0 6px; border-radius: 99px;
  background: #fff; border: 1px solid #fde68a; color: #92400e;
  display: inline-flex; align-items: center; justify-content: center; font-size: 0.78rem; font-weight: 800;
}
.pv-alert-body { flex: 1; min-width: 200px; }
.pv-alert-body b { font-size: 0.86rem; color: var(--c-ink); }
.pv-alert-body p { margin: 2px 0 0; font-size: 0.8rem; color: #92400e; }

/* ---------- Pills ---------- */
.pv-pill { font-size: 0.72rem; font-weight: 700; padding: 3px 9px; border-radius: 99px; }
.pv-pill.tone-ok { background: #d1fae5; color: #065f46; }
.pv-pill.tone-warn { background: #fef3c7; color: #92400e; }
.pv-pill.tone-bad { background: #fee2e2; color: #991b1b; }
.pv-pill.tone-s2 { background: #fff7ed; color: #b45309; border: 1px solid #fed7aa; }
.pv-pill.tone-s3 { background: #ede9fe; color: #5b21b6; }

/* ---------- Cards ---------- */
.pv-list { display: flex; flex-direction: column; gap: 0.9rem; }
.pv-card { padding: 1rem 1.1rem; display: flex; flex-direction: column; gap: 0.85rem; transition: box-shadow 0.15s; }
.pv-list .pv-card:hover { box-shadow: 0 6px 20px rgba(21, 23, 55, 0.09); }
.pv-empty { align-items: center; padding: 2.5rem 1rem; text-align: center; color: var(--c-muted); font-size: 0.85rem; }

.pv-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; flex-wrap: wrap; }
.pv-pills { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
.pv-no {
  font-size: 0.95rem; font-weight: 800; color: var(--c-brand);
  background: #eef2ff; border: 1px solid #c7d2fe; padding: 2px 10px; border-radius: 8px;
}
.pv-head h3 { margin: 0.45rem 0 0; font-size: 1.02rem; font-weight: 700; color: var(--c-ink); }
.pv-head p { margin: 2px 0 0; font-size: 0.78rem; color: var(--c-muted); }
.pv-head p b { color: var(--c-text); }
.pv-actions { display: flex; gap: 0.4rem; flex-wrap: wrap; }

/* ---------- Meta strip ---------- */
.pv-meta {
  display: grid; grid-template-columns: repeat(4, 1fr);
  background: var(--c-soft); border: 1px solid var(--c-line); border-radius: 10px;
}
.pv-meta > div { padding: 0.6rem 0.9rem; display: flex; flex-direction: column; gap: 3px; align-items: flex-start; }
.pv-meta > div + div { border-left: 1px solid var(--c-line); }
.pv-meta b { font-size: 0.95rem; font-weight: 800; color: var(--c-ink); }
.pv-meta b small { font-size: 0.72rem; font-weight: 500; color: var(--c-muted); }

/* ---------- Tiers ---------- */
.pv-tiers { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; }
.pv-tiers-single { grid-template-columns: 1fr; }
.pv-tier {
  border: 1px solid var(--c-line); border-radius: 10px; padding: 0.7rem 0.85rem;
  display: flex; flex-direction: column; gap: 0.45rem; min-width: 0;
}
.pv-tier-top { display: flex; align-items: center; gap: 0.5rem; }
.pv-tier-name { font-size: 0.82rem; font-weight: 700; color: var(--c-ink); flex: 1; min-width: 0; }
.pv-tier-pct { font-size: 1.2rem; font-weight: 800; letter-spacing: -0.02em; }
.pv-tier-tag { font-size: 0.7rem; font-weight: 800; padding: 2px 8px; border-radius: 99px; letter-spacing: 0.03em; }
.tag-s1 { background: #e0e7ff; color: #4338ca; }
.tag-s2 { background: #fef3c7; color: #b45309; }
.tag-s3 { background: #ede9fe; color: #6d28d9; }

.pv-track { position: relative; height: 10px; background: #eef2f7; border-radius: 99px; margin: 0.3rem 0; }
.pv-track.pending {
  background: repeating-linear-gradient(45deg, #fef3c7, #fef3c7 8px, #fde68a 8px, #fde68a 16px);
}
.pv-fill { height: 100%; border-radius: 99px; transition: width 0.4s ease; }
.pv-target-mark { position: absolute; top: -3px; bottom: -3px; width: 2px; background: var(--c-ink); border-radius: 2px; transform: translateX(-2px); }

.pv-tier-num { font-size: 0.8rem; color: var(--c-text); }
.pv-tier-num b { color: var(--c-ink); }
.pv-tier-break { display: flex; flex-wrap: wrap; gap: 0.3rem 0.9rem; font-size: 0.74rem; color: var(--c-muted); }
.pv-tier-break b { font-weight: 800; }
.pv-tier-break i { font-style: normal; }

.pv-note { margin: 0; font-size: 0.76rem; color: var(--c-muted); }

/* ---------- Responsive ---------- */
@media (max-width: 1100px) {
  .pv-tiers { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
  .pv-stats { grid-template-columns: repeat(2, 1fr); }
  .pv-stat:nth-child(3) { border-left: none; }
  .pv-stat:nth-child(n+3) { border-top: 1px solid var(--c-line); }
  .pv-meta { grid-template-columns: repeat(2, 1fr); }
  .pv-meta > div:nth-child(3) { border-left: none; }
  .pv-meta > div:nth-child(n+3) { border-top: 1px solid var(--c-line); }
  .pv-seg { width: 100%; }
  .pv-seg button { flex: 1; padding: 0 0.3rem; }
  .pv-filter-grid { grid-template-columns: 1fr; }
  .pv-filter-row-status { flex-direction: column; align-items: stretch; }
  .pv-count-label { margin-left: 0; }
  .pv-actions { width: 100%; }
}
</style>