<template>
  <div class="lv">

    <!-- ============ HEADER + FILTER ============ -->
    <div class="lv-filter">
      <div class="lv-filter-card">
        <div class="lv-row-flex">
          <div class="lv-title">
            <h2>Laporan Kerja Harian (LKH)</h2>
            <p>Riwayat sortir, downtime, dan kalkulasi OEE</p>
          </div>
          <div class="lv-spacer"></div>
          <button type="button" class="lv-btn lv-btn-ghost lv-mobile-only" @click="showFilter = !showFilter">
            Filter <span v-if="activeFilterCount" class="lv-count">{{ activeFilterCount }}</span>
            <span class="lv-caret">{{ showFilter ? '▲' : '▼' }}</span>
          </button>
          <router-link v-if="authState.role !== 'viewer'" to="/lkh/baru" class="lv-btn lv-btn-primary">+ LKH Baru</router-link>
        </div>

        <!-- Segmen shift berwarna -->
        <div class="lv-row-flex lv-row-shift">
          <div class="lv-seg">
            <button type="button" :class="{ on: filter.shift === '' }" @click="setShift('')">Semua</button>
            <button type="button" class="sh-1" :class="{ on: filter.shift === '1' }" @click="setShift('1')"><i></i>Shift 1</button>
            <button type="button" class="sh-2" :class="{ on: filter.shift === '2' }" @click="setShift('2')"><i></i>Shift 2</button>
            <button type="button" class="sh-3" :class="{ on: filter.shift === '3' }" @click="setShift('3')"><i></i>Shift 3</button>
          </div>
        </div>

        <!-- Filter lainnya -->
        <div class="lv-filter-body" :class="{ open: showFilter }">
          <div class="lv-grid">
            <div class="lv-field">
              <label>Tanggal</label>
              <input type="date" v-model="filter.tanggal" class="lv-input" @change="applyFilter" />
            </div>
            <div class="lv-field">
              <label>Tahap sortir</label>
              <select v-model="filter.tahap_sortir" class="lv-input" @change="applyFilter">
                <option value="">Semua tahap</option>
                <option value="S1">S1 (Normal)</option>
                <option value="S2">S2 (Sortir ulang 2)</option>
                <option value="S3">S3 (Sortir ulang 3)</option>
              </select>
            </div>
            <div class="lv-field">
              <label>Mesin</label>
              <select v-model="filter.mesin_id" class="lv-input" @change="applyFilter">
                <option value="">Semua mesin</option>
                <option v-for="m in master.mesin" :key="m.id" :value="m.id">{{ m.nama }}</option>
              </select>
            </div>
            <div class="lv-field">
              <label>Nomor PP</label>
              <input
                type="text" v-model="filter.no_pp" class="lv-input" placeholder="Ketik No PP..."
                list="lkhPpList" @keyup.enter="applyFilter"
              />
              <datalist id="lkhPpList">
                <option v-for="pp in master.pp" :key="pp.id" :value="pp.no_pp">{{ pp.no_pp }} - {{ pp.produk_nama }}</option>
              </datalist>
            </div>
          </div>
          <div class="lv-filter-actions">
            <button type="button" class="lv-btn lv-btn-ghost" @click="resetFilter">Reset</button>
            <button type="button" class="lv-btn lv-btn-primary" @click="applyFilter">Cari</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Filter Banner untuk PP -->
    <div v-if="filter.no_pp" class="lv-active-pp-banner">
      <div class="lv-active-pp-info">
        <span>Menampilkan riwayat LKH khusus Job Order (PP): <b>{{ filter.no_pp }}</b></span>
      </div>
      <button type="button" class="lv-btn lv-btn-ghost lv-btn-xs" @click="clearPpFilter">
        ✕ Tampilkan Semua PP
      </button>
    </div>

    <!-- ============ DAFTAR LKH ============ -->
    <div v-if="list.length === 0" class="lv-card lv-empty">Tidak ada data LKH yang sesuai filter.</div>

    <div v-else class="lv-list">

      <!-- Judul kolom (hanya tampil di layar lebar) -->
      <div class="lv-cols" aria-hidden="true">
        <span>Shift</span>
        <span>Tanggal</span>
        <span>Mesin</span>
        <span>No PP / Produk</span>
        <span class="r">Proses (pcs)</span>
        <span class="r">DT (menit)</span>
        <span>Operator</span>
        <span>PIC</span>
        <span class="r">Aksi</span>
      </div>

      <article v-for="l in list" :key="l.pp_item_id || l.id" class="lv-item" :class="'sh-' + l.shift">
        <div class="lv-item-bar"></div>
        <div class="lv-item-body">

          <div class="c c-shift"><span class="lv-shift">Shift {{ l.shift }}</span></div>
          <div class="c c-date"><span class="lv-date">{{ l.tanggal }}</span></div>
          <div class="c c-mesin"><span class="lv-mesin">{{ l.mesin_nama }}</span></div>

          <div class="c c-pp">
            <div class="lv-item-pp">
              <b class="lv-pp-no">{{ l.no_pp }}</b>
              <span class="lv-tahap" :class="'tahap-' + (l.tahap_sortir || 'S1')">{{ l.tahap_sortir || 'S1' }}</span>
              <span v-if="l.is_pending || l.pp_status === 'req'" class="lv-pill tone-warn">Req &middot; pending OEE</span>
              <span v-if="l.pp_count > 1" class="lv-pill tone-brand" :title="`Bagian dari shift dengan ${l.pp_count} PP`">PP #{{ l.urutan }}</span>
            </div>
            <div class="lv-produk">{{ l.produk_nama }}</div>
          </div>

          <div class="c c-proses">
            <span class="lv-lbl">Proses</span>
            <b>{{ Number(l.total_proses).toLocaleString('id-ID') }}</b><small>pcs</small>
          </div>
          <div class="c c-dt">
            <span class="lv-lbl">Downtime</span>
            <b v-if="Number(l.total_dt_menit) > 0" class="txt-warn">{{ l.total_dt_menit }}</b>
            <b v-else class="lv-zero">0</b><small>menit</small>
          </div>
          <div class="c c-op">
            <span class="lv-lbl">Operator</span>
            <b class="lv-text">{{ l.operator || '-' }}</b>
          </div>
          <div class="c c-pic">
            <span class="lv-lbl">PIC</span>
            <b class="lv-text">{{ l.pic || '-' }}</b>
          </div>

          <div class="c c-act lv-actions">
            <button type="button" class="lv-btn lv-btn-dark lv-btn-sm" @click="viewLkh(l)">Lihat</button>
            <router-link v-if="canEdit(l)" :to="`/lkh/edit/${l.id}`" class="lv-btn lv-btn-ghost lv-btn-sm">Edit</router-link>
            <span
              v-else-if="authState.role !== 'viewer'"
              class="lv-btn lv-btn-locked lv-btn-sm"
              :title="`Terkunci: data ini diinput oleh PIC '${l.pic || '-'}' (bukan akun Anda).`"
            >🔒 Edit</span>
            <button v-if="authState.role === 'admin'" type="button" class="lv-btn lv-btn-danger lv-btn-sm" @click="deleteLkh(l.id)">Hapus</button>
          </div>
        </div>
      </article>
    </div>

    <!-- Pagination -->
    <div class="lv-pager">
      <button type="button" class="lv-btn lv-btn-ghost" :disabled="page <= 1" @click="page--; loadData()">‹ Sebelumnya</button>
      <span class="lv-muted">Halaman <b>{{ page }}</b></span>
      <button type="button" class="lv-btn lv-btn-ghost" :disabled="list.length < limit" @click="page++; loadData()">Selanjutnya ›</button>
    </div>

    <!-- ============ MODAL VIEW LKH ============ -->
    <div v-if="showModal" class="modal-overlay" :class="{ 'bottom-sheet-active': isMobileScreen }" @click.self="closeViewModal">
      <div class="modal-content lv-modal" :class="'sh-' + selectedLkh?.shift">
        <div class="lv-modal-head">
          <div>
            <h3>
              LKH #{{ selectedLkh?.id }}
              <span class="lv-shift">Shift {{ selectedLkh?.shift }}</span>
            </h3>
            <div class="lv-muted" v-if="selectedLkh?.urutan">Job Order #{{ selectedLkh.urutan }}: {{ selectedLkh.no_pp }}</div>
          </div>
          <button type="button" class="lv-btn lv-btn-ghost lv-btn-sm" @click="closeViewModal">✕</button>
        </div>

        <!-- Switcher PP -->
        <div v-if="selectedLkhDetail?.pp_items && selectedLkhDetail.pp_items.length > 1" class="lv-switch">
          <span class="lv-eyebrow">Job Order di shift ini</span>
          <div class="lv-switch-list">
            <button
              v-for="pItem in selectedLkhDetail.pp_items" :key="pItem.id" type="button"
              :class="{ on: selectedLkh?.pp_item_id == pItem.id }"
              @click="selectModalPpItem(pItem)"
            >#{{ pItem.urutan }} {{ pItem.no_pp }} · {{ pItem.tahap_sortir || 'S1' }}</button>
          </div>
        </div>

        <div v-if="selectedLkh">
          <!-- Peringatan req -->
          <div v-if="isPendingLkh" class="lv-alert">
            <b>Job Order ini masih berstatus Request.</b>
            <p>Target shift, Performance, dan OEE tertunda sampai Admin menetapkan Standar Speed (JPH) &amp; Kuota Cetak lalu menekan ACC.</p>
          </div>

          <!-- Info dasar -->
          <div class="lv-info">
            <div><span>Tanggal</span><b>{{ selectedLkh.tanggal }}</b></div>
            <div><span>Mesin</span><b>{{ selectedLkh.mesin_nama }}</b></div>
            <div><span>Operator</span><b>{{ selectedLkh.operator }}</b></div>
            <div>
              <span>Job Order</span>
              <b>{{ selectedLkh.no_pp }}
                <em class="lv-tahap" :class="'tahap-' + (selectedLkh.tahap_sortir || 'S1')">{{ selectedLkh.tahap_sortir || 'S1' }}</em>
              </b>
            </div>
            <div class="lv-info-wide"><span>Produk</span><b>{{ selectedLkh.produk_nama }} <small>({{ selectedLkh.famili || '-' }})</small></b></div>
            <div class="lv-info-wide">
              <span>Jam</span>
              <b>{{ (selectedLkh.start || '').replace('T', ' ').substring(0, 16) }} s/d {{ (selectedLkh.finish || '').replace('T', ' ').substring(0, 16) }}</b>
            </div>
          </div>

          <!-- Produksi -->
          <div class="lv-stats">
            <div class="lv-stat">
              <span class="lv-eyebrow">Good</span>
              <b class="txt-ok">{{ Number(selectedLkh.good || 0).toLocaleString('id-ID') }}</b><small>pcs</small>
            </div>
            <div class="lv-stat">
              <span class="lv-eyebrow">Reject WIP</span>
              <b class="txt-bad">{{ Number(selectedLkh.reject || 0).toLocaleString('id-ID') }}</b><small>pcs</small>
            </div>
            <div class="lv-stat">
              <span class="lv-eyebrow">Stell mesin</span>
              <b class="txt-violet">{{ Number(selectedLkh.setup_reject || 0).toLocaleString('id-ID') }}</b><small>pcs</small>
            </div>
            <div class="lv-stat">
              <span class="lv-eyebrow">Total proses</span>
              <b>{{ Number(selectedLkh.total_proses || 0).toLocaleString('id-ID') }}</b><small>pcs</small>
            </div>
          </div>

          <!-- Waktu & target -->
          <div class="lv-times">
            <div>
              <span class="lv-eyebrow">Target shift</span>
              <b v-if="isPendingLkh" class="lv-pill tone-warn">Pending</b>
              <b v-else>{{ Number(selectedLkh.plan_shift || 0).toLocaleString('id-ID') }}</b>
            </div>
            <div><span class="lv-eyebrow">5S &amp; laporan</span><b>{{ selectedLkh['5s_menit'] || 0 }} m</b></div>
            <div><span class="lv-eyebrow">Waktu prod</span><b>{{ Number(selectedLkh.waktu_prod_jam || 0).toFixed(2) }} j</b></div>
            <div><span class="lv-eyebrow">Persiapan</span><b>{{ selectedLkh.setting_menit || 0 }} m</b></div>
            <div><span class="lv-eyebrow">Total DT</span><b class="txt-warn">{{ selectedLkh.total_dt_menit || 0 }} m</b></div>
            <div><span class="lv-eyebrow">Jam jalan</span><b>{{ Number(selectedLkh.jam_jalan || 0).toFixed(2) }} j</b></div>
          </div>

          <!-- OEE & pilar -->
          <div class="lv-oee">
            <div class="lv-oee-main">
              <span class="lv-eyebrow">OEE</span>
              <b v-if="isPendingLkh" class="lv-pill tone-warn">Pending</b>
              <b v-else class="lv-oee-val" :class="'txt-' + oeeTone">{{ (Number(selectedLkh.oee || 0) * 100).toFixed(1) }}<small>%</small></b>
            </div>
            <div class="lv-oee-pillars">
              <div>
                <span class="lv-eyebrow">Availability</span>
                <b>{{ (Number(selectedLkh.availability || 0) * 100).toFixed(1) }}%</b>
              </div>
              <div>
                <span class="lv-eyebrow">Performance</span>
                <b v-if="isPendingLkh" class="lv-pill tone-warn">Pending</b>
                <b v-else>{{ (Number(selectedLkh.performance || 0) * 100).toFixed(1) }}%</b>
              </div>
              <div>
                <span class="lv-eyebrow">Quality OEE</span>
                <b>{{ (Number(selectedLkh.quality_oee || 0) * 100).toFixed(1) }}%</b>
              </div>
              <div>
                <span class="lv-eyebrow">Quality WIP</span>
                <b>{{ (Number(selectedLkh.quality_wip || 0) * 100).toFixed(1) }}%</b>
              </div>
            </div>
          </div>

          <!-- Toggle panel -->
          <div class="lv-toggles">
            <button type="button" :class="{ on: showDtPanel }" @click="showDtPanel = !showDtPanel">
              Downtime ({{ selectedLkh.downtime?.length || 0 }})
            </button>
            <button type="button" :class="{ on: showCacatPanel }" @click="showCacatPanel = !showCacatPanel">
              Reject ({{ rejectDetails.length }} jenis)
            </button>
          </div>

          <!-- Downtime -->
          <div v-if="showDtPanel" class="lv-panel">
            <div class="lv-panel-head">
              <b>Rincian downtime &middot; {{ totalDtMenit }} menit</b>
              <span class="lv-muted">Setting {{ selectedLkh.setting_menit || 0 }}m &middot; 5S {{ selectedLkh['5s_menit'] || 0 }}m</span>
            </div>

            <div v-if="selectedLkh.downtime && selectedLkh.downtime.length" class="lv-dt-list">
              <div v-for="(dt, i) in selectedLkh.downtime" :key="i" class="lv-dt">
                <div class="lv-dt-top">
                  <b>[{{ dt.kode }}] {{ dt.arti }}</b>
                  <span class="lv-dt-min">{{ dt.menit }} m</span>
                </div>
                <div class="lv-dt-tags">
                  <span class="lv-pill tone-neutral">{{ dt.kategori || '-' }}</span>
                  <span class="lv-pill tone-neutral">{{ dt.jenis_waste || '-' }}</span>
                </div>
                <p v-if="dt.keterangan">{{ dt.keterangan }}</p>
              </div>
            </div>
            <p v-else class="lv-muted lv-pad">Tidak ada catatan downtime pada shift ini.</p>

            <div v-if="selectedLkh.problem || selectedLkh.root_cause || selectedLkh.tindakan_perbaikan || selectedLkh.tindakan_sementara" class="lv-notes">
              <div v-if="selectedLkh.problem"><span>Problem</span>{{ selectedLkh.problem }}</div>
              <div v-if="selectedLkh.root_cause"><span>Root cause</span>{{ selectedLkh.root_cause }}</div>
              <div v-if="selectedLkh.tindakan_sementara"><span>Tindakan sementara</span>{{ selectedLkh.tindakan_sementara }}</div>
              <div v-if="selectedLkh.tindakan_perbaikan"><span>Tindakan perbaikan</span>{{ selectedLkh.tindakan_perbaikan }}</div>
              <div v-if="selectedLkh.pic"><span>PIC</span>{{ selectedLkh.pic }}</div>
            </div>
          </div>

          <!-- Reject -->
          <div v-if="showCacatPanel" class="lv-panel">
            <div class="lv-reject-grid">
              <div class="lv-reject-box">
                <div class="lv-panel-head"><b>Reject WIP cetak &middot; {{ rejectDetailTotal }} pcs</b></div>
                <ul v-if="rejectDetails.length">
                  <li v-for="(c, i) in rejectDetails" :key="'r' + i">
                    <span>{{ c.jenis_cacat_nama }}</span>
                    <b>{{ Number(c.qty).toLocaleString('id-ID') }} pcs</b>
                  </li>
                </ul>
                <p v-else class="lv-muted lv-pad">Tidak ada detail per jenis cacat (total reject WIP: {{ selectedLkh.reject }} pcs).</p>
              </div>
              <div class="lv-reject-box">
                <div class="lv-panel-head"><b>Rusakan stell (setup mesin)</b></div>
                <div class="lv-stell">{{ Number(selectedLkh.setup_reject || 0).toLocaleString('id-ID') }} <small>pcs</small></div>
                <p class="lv-muted lv-pad">Rusakan saat persiapan/setting mesin sortir, satu angka, terpisah dari reject biasa.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="lv-modal-foot">
          <button type="button" class="lv-btn lv-btn-ghost" @click="closeViewModal">Tutup</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { apiFetch, authState } from '../utils/api'

const route = useRoute()
const router = useRouter()

const list = ref([])
const showModal = ref(false)
const showDtPanel = ref(false)
const showCacatPanel = ref(false)
const showFilter = ref(false)
const selectedLkh = ref(null)
const selectedLkhDetail = ref(null)

const isMobileScreen = ref(false)
const checkMobileScreen = () => { isMobileScreen.value = window.innerWidth <= 768 }

const canEdit = (lkh) => {
  if (authState.value.role === 'admin') return true
  if (authState.value.role === 'operator') {
    return !!lkh.pic && lkh.pic.trim().toLowerCase() === (authState.value.username || '').trim().toLowerCase()
  }
  return false
}

const isPendingLkh = computed(() => !!(selectedLkh.value && (selectedLkh.value.is_pending || selectedLkh.value.pp_status === 'req')))
const oeeTone = computed(() => {
  const v = (Number(selectedLkh.value?.oee) || 0) * 100
  return v >= 85 ? 'ok' : v >= 70 ? 'warn' : 'bad'
})

const rejectDetails = computed(() =>
  (selectedLkh.value?.cacat || []).filter(c => c.kategori === 'reject' && Number(c.qty) > 0)
)
const rejectDetailTotal = computed(() => rejectDetails.value.reduce((s, c) => s + Number(c.qty), 0))
const totalDtMenit = computed(() =>
  (selectedLkh.value?.downtime || []).reduce((s, dt) => s + Number(dt.menit || 0), 0)
)

const closeViewModal = () => {
  showModal.value = false
  showDtPanel.value = false
  showCacatPanel.value = false
}

const selectModalPpItem = (pItem) => {
  if (!selectedLkhDetail.value || !pItem) return
  const detail = selectedLkhDetail.value
  const good = Number(pItem.good) || 0
  const reject = Number(pItem.reject) || 0
  const setup_reject = Number(pItem.setup_reject) || 0
  const total_proses = Number(pItem.total_proses) || (good + reject + setup_reject)
  const waktu_prod_jam = Number(pItem.waktu_prod_jam) || 0
  const jam_jalan = Number(pItem.jam_jalan) || 0
  const std_jph = Number(pItem.std_jph ?? detail.std_jph) || 0
  const is_pending = Boolean(pItem.is_pending || detail.is_pending || pItem.pp_status === 'req' || detail.pp_status === 'req' || std_jph <= 0)

  const actual_jph = jam_jalan > 0 ? (total_proses / jam_jalan) : 0
  const availability = waktu_prod_jam > 0 ? (jam_jalan / waktu_prod_jam) : 0
  const plan_shift = is_pending ? null : ((std_jph > 0 && jam_jalan > 0) ? Math.round(jam_jalan * std_jph) : Number(pItem.plan_shift ?? 0))
  const performance = is_pending ? null : (plan_shift > 0 ? (total_proses / plan_shift) : 0)
  const total_sortir = good + setup_reject
  const quality_oee = total_sortir > 0 ? (good / total_sortir) : (total_proses > 0 ? 1 : 0)
  const total_wip = good + reject
  const quality_wip = total_wip > 0 ? (good / total_wip) : 0
  const oee = is_pending ? null : (availability * (performance || 0) * quality_oee)

  const itemDt = pItem.downtime || []
  const itemCacat = pItem.cacat || []

  selectedLkh.value = {
    ...detail,
    ...pItem,
    is_pending,
    pp_item_id: pItem.id,
    mesin_nama: detail.mesin_nama,
    operator: detail.operator,
    pic: detail.pic,
    tanggal: detail.tanggal,
    shift: detail.shift,
    start: pItem.jam_mulai,
    finish: pItem.jam_selesai,
    downtime: itemDt,
    cacat: itemCacat,
    actual_jph,
    availability,
    performance,
    quality_oee,
    quality_wip,
    oee,
    plan_shift
  }
  showDtPanel.value = (itemDt.length > 0)
}

const viewLkh = async (lkh) => {
  try {
    const detail = await apiFetch(`lkh.php?action=detail&id=${lkh.id}`)
    selectedLkhDetail.value = detail

    const curItem = (detail.pp_items || []).find(x => x.id == lkh.pp_item_id) || detail.pp_items?.[0] || lkh
    selectModalPpItem(curItem)
    showCacatPanel.value = false
    showModal.value = true
  } catch (e) {
    alert('Gagal mengambil detail LKH: ' + e.message)
  }
}

const deleteLkh = async (id) => {
  if (confirm('Yakin ingin menghapus data LKH ini?')) {
    try {
      await apiFetch(`lkh.php?action=delete&id=${id}`, { method: 'DELETE' })
      loadData()
    } catch (e) {
      alert(e.message)
    }
  }
}

const page = ref(1)
const limit = 20
const filter = ref({
  tanggal: '',
  shift: '',
  tahap_sortir: '',
  mesin_id: '',
  no_pp: ''
})

const master = ref({ mesin: [], pp: [] })

const activeFilterCount = computed(() => {
  const f = filter.value
  return [f.tanggal, f.shift, f.tahap_sortir, f.mesin_id, f.no_pp].filter(Boolean).length
})

const setShift = (s) => {
  filter.value.shift = s
  page.value = 1
  loadData()
}

const applyFilter = () => {
  page.value = 1
  loadData()
}

const resetFilter = () => {
  filter.value = { tanggal: '', shift: '', tahap_sortir: '', mesin_id: '', no_pp: '' }
  if (route.query.no_pp) {
    router.replace({ query: {} })
  }
  page.value = 1
  loadData()
}

const clearPpFilter = () => {
  filter.value.no_pp = ''
  if (route.query.no_pp) {
    router.replace({ query: {} })
  }
  page.value = 1
  loadData()
}

watch(() => route.query.no_pp, (newVal) => {
  if (newVal !== undefined) {
    filter.value.no_pp = String(newVal || '')
    page.value = 1
    loadData()
  }
})

const loadData = async () => {
  const query = new URLSearchParams()
  query.append('page', page.value)
  query.append('limit', limit)
  if (filter.value.tanggal) query.append('tanggal', filter.value.tanggal)
  if (filter.value.shift) query.append('shift', filter.value.shift)
  if (filter.value.tahap_sortir) query.append('tahap_sortir', filter.value.tahap_sortir)
  if (filter.value.mesin_id) query.append('mesin_id', filter.value.mesin_id)
  if (filter.value.no_pp) query.append('no_pp', filter.value.no_pp)

  const res = await apiFetch(`lkh.php?action=list&${query.toString()}`)
  list.value = res.data || []
}

const handleKeyDown = (e) => {
  if (e.key === 'Escape') closeViewModal()
}

onMounted(async () => {
  checkMobileScreen()
  window.addEventListener('resize', checkMobileScreen)
  document.addEventListener('keydown', handleKeyDown)

  if (route.query.no_pp) {
    filter.value.no_pp = String(route.query.no_pp)
  }

  try {
    const [mesinRes, ppRes] = await Promise.all([
      apiFetch('master.php?action=mesin'),
      apiFetch('master.php?action=pp')
    ])
    master.value.mesin = mesinRes || []
    master.value.pp = ppRes || []
  } catch (e) {
    console.error('Failed to load master data', e)
  }
  loadData()
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobileScreen)
  document.removeEventListener('keydown', handleKeyDown)
})
</script>

<style scoped>
.lv {
  --c-brand: #4f46e5;
  --c-ok: #10b981;
  --c-warn: #d97706;
  --c-bad: #e11d48;
  --c-violet: #7c3aed;
  --c-ink: #15172b;
  --c-text: #3a3f5c;
  --c-muted: #666c8a;
  --c-line: #e4e5f1;
  --c-soft: #eef0fb;
  color: var(--c-text);
}

/* Warna per shift */
.sh-1 { --sh: #0284c7; --sh-bg: #e0f2fe; --sh-ink: #075985; }
.sh-2 { --sh: #ea580c; --sh-bg: #ffedd5; --sh-ink: #9a3412; }
.sh-3 { --sh: #7c3aed; --sh-bg: #ede9fe; --sh-ink: #5b21b6; }

.lv-muted { color: var(--c-muted); font-size: 0.78rem; }
.lv-spacer { flex: 1; }
.lv-pad { margin: 0.5rem 0 0; }
.lv-btn.lv-mobile-only { display: none; }
.lv-eyebrow { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--c-muted); }
.txt-ok { color: var(--c-ok); }
.txt-warn { color: var(--c-warn); }
.txt-bad { color: var(--c-bad); }
.txt-violet { color: var(--c-violet); }

/* ---------- Tombol & input ---------- */
.lv-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  height: 34px; padding: 0 0.85rem; font-size: 0.82rem; font-weight: 600; text-decoration: none;
  border-radius: 8px; border: 1px solid transparent; cursor: pointer; white-space: nowrap;
  transition: background 0.15s, border-color 0.15s;
}
.lv-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.lv-btn-sm { height: 32px; padding: 0 0.8rem; font-size: 0.78rem; }
.lv-btn-primary { background: linear-gradient(135deg, #6366f1, var(--c-brand)); color: #fff; box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3); }
.lv-btn-primary:hover { background: linear-gradient(135deg, #5457ea, #4338ca); }
.lv-btn-dark { background: linear-gradient(135deg, #272a45, var(--c-ink)); color: #fff; }
.lv-btn-dark:hover { background: #0f1022; }
.lv-btn-ghost { background: #fff; color: var(--c-text); border-color: var(--c-line); }
.lv-btn-ghost:hover:not(:disabled) { background: var(--c-soft); }
.lv-btn-danger { background: #fff; color: var(--c-bad); border-color: #fecdd3; }
.lv-btn-danger:hover { background: #fff1f2; }
.lv-btn-locked { background: #f4f5fa; color: var(--c-muted); border-color: var(--c-line); cursor: not-allowed; }
.lv-caret { font-size: 0.6rem; opacity: 0.7; }
.lv-count {
  background: var(--c-brand); color: #fff; border-radius: 99px;
  font-size: 0.68rem; min-width: 18px; height: 18px; padding: 0 5px;
  display: inline-flex; align-items: center; justify-content: center;
}

.lv-input {
  width: 100%; height: 34px; padding: 0 0.6rem; font-size: 0.82rem;
  border: 1px solid var(--c-line); border-radius: 8px; background: #fff; color: var(--c-ink); min-width: 0;
}
.lv-input:focus { outline: 2px solid rgba(79, 70, 229, 0.2); border-color: var(--c-brand); }
.lv-field { display: flex; flex-direction: column; gap: 3px; }
.lv-field label { font-size: 0.72rem; font-weight: 700; color: var(--c-muted); }

/* ---------- Filter ---------- */
.lv-filter { padding: 4px 0 10px; }
.lv-filter-card {
  background: #fff; border: 1px solid var(--c-line); border-radius: 14px;
  padding: 0.7rem 0.85rem; box-shadow: 0 2px 10px rgba(21, 23, 55, 0.05);
}
.lv-row-flex { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
.lv-row-shift { margin-top: 0.65rem; }
.lv-title h2 { margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--c-ink); }
.lv-title p { margin: 2px 0 0; font-size: 0.75rem; color: var(--c-muted); }

.lv-seg { display: inline-flex; background: var(--c-soft); border-radius: 8px; padding: 3px; gap: 2px; max-width: 100%; }
.lv-seg button {
  display: inline-flex; align-items: center; gap: 6px; border: none; background: transparent;
  padding: 0 0.75rem; height: 30px; font-size: 0.78rem; font-weight: 600; color: var(--c-muted);
  border-radius: 6px; cursor: pointer; white-space: nowrap;
}
.lv-seg button i { width: 8px; height: 8px; border-radius: 50%; background: var(--sh); }
.lv-seg button.on { background: #fff; color: var(--c-brand); box-shadow: 0 1px 2px rgba(15, 23, 42, 0.1); }
.lv-seg button.sh-1.on, .lv-seg button.sh-2.on, .lv-seg button.sh-3.on {
  background: var(--sh); color: #fff;
}
.lv-seg button.on i { background: #fff; }

.lv-filter-body { margin-top: 0.65rem; padding-top: 0.65rem; border-top: 1px dashed var(--c-line); }
.lv-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.6rem; }
.lv-filter-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.65rem; }

/* ---------- Daftar ---------- */
.lv-card { background: #fff; border: 1px solid var(--c-line); border-radius: 14px; box-shadow: 0 2px 10px rgba(21, 23, 55, 0.05); }
.lv-empty { padding: 2.5rem 1rem; text-align: center; color: var(--c-muted); font-size: 0.85rem; }
.lv-list {
  --lv-cols: 84px 92px 96px minmax(220px, 1.8fr) 104px 84px minmax(80px, 1fr) minmax(80px, 1fr) 200px;
  display: flex; flex-direction: column; gap: 0.5rem;
}

/* Judul kolom (desktop) */
.lv-cols {
  display: grid; grid-template-columns: var(--lv-cols); gap: 0.75rem; align-items: center;
  padding: 0 0.9rem 0 calc(6px + 0.9rem);
  font-size: 0.68rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--c-muted);
}
.lv-cols .r { text-align: right; }

.lv-item {
  display: flex; background: #fff; border: 1px solid var(--c-line); border-radius: 12px;
  box-shadow: 0 1px 6px rgba(21, 23, 55, 0.05); overflow: hidden; min-width: 0;
  transition: box-shadow 0.15s;
}
.lv-item:hover { box-shadow: 0 4px 16px rgba(21, 23, 55, 0.09); }
.lv-item-bar { flex: none; width: 6px; background: var(--sh, var(--c-line)); }
.lv-item-body {
  flex: 1; min-width: 0; padding: 0.6rem 0.9rem;
  display: grid; grid-template-columns: var(--lv-cols); gap: 0.75rem; align-items: center;
}
.lv-item-body .c { min-width: 0; }
.lv-item-body .c b { font-size: 0.92rem; font-weight: 800; color: var(--c-ink); }
.lv-item-body .c b.lv-text { font-size: 0.82rem; font-weight: 700; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.lv-item-body .c small { font-size: 0.68rem; color: var(--c-muted); margin-left: 3px; }
.lv-item-body .c-proses, .lv-item-body .c-dt { text-align: right; }
.lv-item-body .c-act { justify-content: flex-end; flex-wrap: nowrap; }
.lv-lbl { display: none; font-size: 0.66rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--c-muted); }
.lv-item-body .lv-mesin { margin-left: 0; text-align: left; }
@media (min-width: 901px) {
  .lv-item-body small { display: none; }
}
.lv-shift {
  display: inline-block; font-size: 0.72rem; font-weight: 800; padding: 2px 10px; border-radius: 99px;
  background: var(--sh-bg); color: var(--sh-ink); letter-spacing: 0.02em;
}
.lv-date { font-size: 0.82rem; font-weight: 700; color: var(--c-ink); }
.lv-mesin { font-size: 0.78rem; color: var(--c-muted); margin-left: auto; text-align: right; }

.lv-item-pp { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
.lv-pp-no { font-size: 0.95rem; font-weight: 800; color: var(--c-brand); }
.lv-produk { font-size: 0.82rem; color: var(--c-text); overflow-wrap: anywhere; }

.lv-tahap { font-style: normal; font-size: 0.68rem; font-weight: 800; padding: 2px 8px; border-radius: 99px; }
.tahap-S1 { background: #e0e7ff; color: #4338ca; }
.tahap-S2 { background: #fef3c7; color: #b45309; }
.tahap-S3 { background: #fee2e2; color: #991b1b; }

.lv-pill { font-size: 0.68rem; font-weight: 700; padding: 2px 8px; border-radius: 99px; }
.lv-pill.tone-warn { background: #fef3c7; color: #92400e; }
.lv-pill.tone-brand { background: #eef2ff; color: #3730a3; }
.lv-pill.tone-neutral { background: var(--c-soft); color: var(--c-text); border: 1px solid var(--c-line); }

.lv-zero { color: var(--c-muted) !important; }

.lv-actions { display: flex; gap: 0.4rem; flex-wrap: wrap; }

.lv-pager { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; margin-top: 0.9rem; }

/* ---------- Modal ---------- */
.lv-modal { max-width: 780px; width: 95%; max-height: 90vh; overflow-y: auto; border-top: 5px solid var(--sh, var(--c-brand)); }
.lv-modal-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.75rem; }
.lv-modal-head h3 { margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--c-ink); display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.lv-modal-foot { display: flex; justify-content: flex-end; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--c-line); }

.lv-switch { background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 10px; padding: 0.55rem 0.7rem; margin-bottom: 0.75rem; }
.lv-switch-list { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.35rem; }
.lv-switch-list button {
  border: 1px solid #c7d2fe; background: #fff; color: var(--c-brand);
  font-size: 0.74rem; font-weight: 600; padding: 4px 10px; border-radius: 99px; cursor: pointer;
}
.lv-switch-list button.on { background: var(--c-brand); color: #fff; border-color: var(--c-brand); }

.lv-alert { padding: 0.65rem 0.85rem; margin-bottom: 0.75rem; border-radius: 10px; background: #fffbeb; border-left: 4px solid var(--c-warn); }
.lv-alert b { font-size: 0.84rem; color: var(--c-ink); }
.lv-alert p { margin: 2px 0 0; font-size: 0.78rem; color: #92400e; line-height: 1.45; }

.lv-info {
  display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.55rem 1rem;
  padding: 0.7rem 0.9rem; margin-bottom: 0.75rem;
  background: var(--c-soft); border: 1px solid var(--c-line); border-radius: 10px;
}
.lv-info > div { display: flex; flex-direction: column; min-width: 0; }
.lv-info span { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--c-muted); }
.lv-info b { font-size: 0.85rem; color: var(--c-ink); overflow-wrap: anywhere; }
.lv-info b small { font-weight: 500; color: var(--c-muted); }
.lv-info-wide { grid-column: 1 / -1; }

.lv-stats { display: grid; grid-template-columns: repeat(4, 1fr); margin-bottom: 0.75rem; border: 1px solid var(--c-line); border-radius: 10px; }
.lv-stat { padding: 0.6rem 0.85rem; display: flex; flex-wrap: wrap; align-items: baseline; gap: 0 5px; }
.lv-stat + .lv-stat { border-left: 1px solid var(--c-line); }
.lv-stat .lv-eyebrow { width: 100%; margin-bottom: 2px; }
.lv-stat b { font-size: 1.15rem; font-weight: 800; color: var(--c-ink); }
.lv-stat small { font-size: 0.7rem; color: var(--c-muted); }

.lv-times { display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.4rem; margin-bottom: 0.75rem; }
.lv-times > div { display: flex; flex-direction: column; gap: 2px; padding: 0.5rem 0.6rem; background: #fff; border: 1px solid var(--c-line); border-radius: 8px; }
.lv-times b { font-size: 0.88rem; font-weight: 800; color: var(--c-ink); }

.lv-oee {
  display: grid; grid-template-columns: 1fr 2.4fr; gap: 0.75rem; align-items: center;
  padding: 0.75rem 0.9rem; margin-bottom: 0.75rem; border: 1px solid var(--c-line); border-radius: 10px;
  background: linear-gradient(165deg, #ffffff 0%, #f8f8fe 60%, #f3f2fd 100%);
}
.lv-oee-main { display: flex; flex-direction: column; gap: 2px; }
.lv-oee-val { font-size: 2.2rem; font-weight: 800; line-height: 1; letter-spacing: -0.03em; }
.lv-oee-val small { font-size: 1rem; }
.lv-oee-pillars { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem; }
.lv-oee-pillars > div { display: flex; flex-direction: column; gap: 2px; }
.lv-oee-pillars b { font-size: 0.95rem; font-weight: 800; color: var(--c-ink); }

.lv-toggles { display: flex; gap: 0.4rem; margin-bottom: 0.75rem; flex-wrap: wrap; }
.lv-toggles button {
  border: 1px solid var(--c-line); background: #fff; color: var(--c-muted);
  font-size: 0.78rem; font-weight: 600; padding: 6px 14px; border-radius: 99px; cursor: pointer;
}
.lv-toggles button.on { background: #eef2ff; border-color: #c7d2fe; color: var(--c-brand); }

.lv-panel { border: 1px solid var(--c-line); border-radius: 10px; padding: 0.75rem; margin-bottom: 0.75rem; background: #fafbff; }
.lv-panel-head { display: flex; justify-content: space-between; align-items: baseline; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.5rem; }
.lv-panel-head b { font-size: 0.88rem; color: var(--c-ink); }

.lv-dt-list { display: flex; flex-direction: column; gap: 0.5rem; }
.lv-dt { background: #fff; border: 1px solid var(--c-line); border-left: 4px solid var(--c-warn); border-radius: 8px; padding: 0.55rem 0.7rem; }
.lv-dt-top { display: flex; justify-content: space-between; align-items: baseline; gap: 0.5rem; }
.lv-dt-top b { font-size: 0.82rem; color: var(--c-ink); overflow-wrap: anywhere; }
.lv-dt-min { flex: none; font-size: 0.85rem; font-weight: 800; color: var(--c-warn); }
.lv-dt-tags { display: flex; gap: 0.3rem; flex-wrap: wrap; margin-top: 4px; }
.lv-dt p { margin: 4px 0 0; font-size: 0.78rem; color: var(--c-text); overflow-wrap: anywhere; }

.lv-notes { margin-top: 0.7rem; padding-top: 0.7rem; border-top: 1px dashed var(--c-line); display: flex; flex-direction: column; gap: 0.35rem; font-size: 0.8rem; line-height: 1.45; }
.lv-notes span { display: block; font-size: 0.66rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--c-muted); }

.lv-reject-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem; }
.lv-reject-box { background: #fff; border: 1px solid var(--c-line); border-radius: 8px; padding: 0.65rem 0.75rem; }
.lv-reject-box ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; }
.lv-reject-box li { display: flex; justify-content: space-between; gap: 0.5rem; padding: 5px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.8rem; }
.lv-reject-box li:last-child { border-bottom: none; }
.lv-stell { font-size: 1.5rem; font-weight: 800; color: var(--c-violet); }
.lv-stell small { font-size: 0.78rem; font-weight: 500; color: var(--c-muted); }

.lv-active-pp-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #eef2ff;
  border: 1px solid #c7d2fe;
  border-radius: 10px;
  padding: 0.55rem 0.9rem;
  margin-bottom: 0.9rem;
  font-size: 0.82rem;
  color: #3730a3;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.lv-active-pp-banner b {
  font-weight: 800;
  color: #1e1b4b;
}
.lv-btn-xs {
  height: 26px;
  padding: 0 0.6rem;
  font-size: 0.74rem;
}

/* ---------- Responsive ---------- */
/* Desktop: shift + semua filter dalam satu baris */
@media (min-width: 769px) {
  .lv-filter-card { display: grid; grid-template-columns: auto 1fr; column-gap: 1rem; row-gap: 0.65rem; align-items: end; }
  .lv-filter-card > .lv-row-flex:first-child { grid-column: 1 / -1; }
  .lv-row-shift { margin-top: 0; }
  .lv-filter-body { margin-top: 0; padding-top: 0; border-top: none; display: flex; align-items: flex-end; gap: 0.6rem; }
  .lv-filter-body .lv-grid { flex: 1; }
  .lv-filter-actions { margin-top: 0; }
}

/* Tablet & HP: baris berubah jadi kartu */
@media (max-width: 900px) {
  .lv-cols { display: none; }
  .lv-item-body {
    grid-template-columns: repeat(4, 1fr); gap: 0.45rem 0.6rem; padding: 0.75rem 0.9rem;
    grid-template-areas:
      "shift date date mesin"
      "pp pp pp pp"
      "proses proses dt dt"
      "op op pic pic"
      "act act act act";
  }
  .lv-item-body .c-shift { grid-area: shift; }
  .lv-item-body .c-date { grid-area: date; }
  .lv-item-body .c-mesin { grid-area: mesin; text-align: right; }
  .lv-item-body .c-pp { grid-area: pp; }
  .lv-item-body .c-proses { grid-area: proses; text-align: left; }
  .lv-item-body .c-dt { grid-area: dt; text-align: left; }
  .lv-item-body .c-op { grid-area: op; }
  .lv-item-body .c-pic { grid-area: pic; }
  .lv-item-body .c-act { grid-area: act; justify-content: stretch; margin-top: 0.2rem; }
  .lv-item-body .c-proses, .lv-item-body .c-dt, .lv-item-body .c-op, .lv-item-body .c-pic {
    background: var(--c-soft); border-radius: 8px; padding: 0.4rem 0.6rem;
  }
  .lv-lbl { display: block; }
  .lv-item-body .c b { font-size: 1rem; }
  .lv-item-body .c b.lv-text { font-size: 0.85rem; }
}

@media (max-width: 768px) {
  .lv-btn.lv-mobile-only { display: inline-flex; }
  .lv-filter-body { display: none; }
  .lv-filter-body.open { display: block; margin-top: 0.65rem; padding-top: 0.65rem; border-top: 1px dashed var(--c-line); }
  .lv-grid { grid-template-columns: 1fr 1fr; }
  .lv-seg { display: flex; width: 100%; }
  .lv-seg button { flex: 1; justify-content: center; padding: 0 0.3rem; }

  .lv-actions .lv-btn { flex: 1; }

  .lv-info { grid-template-columns: 1fr 1fr; }
  .lv-stats { grid-template-columns: 1fr 1fr; }
  .lv-stat:nth-child(3) { border-left: none; }
  .lv-stat:nth-child(n+3) { border-top: 1px solid var(--c-line); }
  .lv-times { grid-template-columns: repeat(3, 1fr); }
  .lv-oee { grid-template-columns: 1fr; }
  .lv-oee-pillars { grid-template-columns: repeat(2, 1fr); }
  .lv-reject-grid { grid-template-columns: 1fr; }

  .bottom-sheet-active { align-items: flex-end !important; padding: 0 !important; }
  .lv-modal {
    max-width: 100% !important; width: 100% !important; max-height: 88vh !important;
    border-radius: 14px 14px 0 0 !important; animation: lvSlideUp 0.25s ease-out;
  }
}

@keyframes lvSlideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
</style>