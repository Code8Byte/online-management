<template>
  <div class="card">
    <div class="d-flex justify-between align-center mb-1 flex-wrap gap-05">
      <div>
        <h2 class="card-title" style="margin:0; border:none; padding:0;">
          <span>📋</span> Data Laporan Kerja Harian (LKH)
        </h2>
        <small class="text-muted">Riwayat pencatatan sortir, downtime, dan kalkulasi OEE</small>
      </div>
      <div class="d-flex gap-05 flex-wrap">
        <router-link v-if="authState.role !== 'viewer'" to="/lkh/baru" class="btn btn-primary">
          <span>✍️</span> + LKH Baru
        </router-link>
      </div>
    </div>
    
    <!-- Filter Card -->
    <div class="card mb-1" style="background: var(--bg); padding: 0.85rem; border: 1px solid var(--border);">
      <div class="grid grid-5 gap-1 mb-1">
        <div class="form-group" style="margin-bottom: 0;">
          <label>Tanggal</label>
          <input type="date" v-model="filter.tanggal" class="form-control" title="Filter Tanggal" @change="loadData" />
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Shift</label>
          <select v-model="filter.shift" class="form-control" title="Filter Shift" @change="loadData">
            <option value="">Semua Shift</option>
            <option value="1">Shift 1</option>
            <option value="2">Shift 2</option>
            <option value="3">Shift 3</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Tahap Sortir</label>
          <select v-model="filter.tahap_sortir" class="form-control" title="Filter Tahap Sortir" @change="loadData">
            <option value="">Semua Tahap</option>
            <option value="S1">S1 (Sortir Normal)</option>
            <option value="S2">S2 (Sortir Ulang 2)</option>
            <option value="S3">S3 (Sortir Ulang 3)</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Mesin</label>
          <select v-model="filter.mesin_id" class="form-control" title="Filter Mesin" @change="loadData">
            <option value="">Semua Mesin</option>
            <option v-for="m in master.mesin" :key="m.id" :value="m.id">{{ m.nama }}</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Nomor PP</label>
          <input type="text" v-model="filter.no_pp" class="form-control" placeholder="Ketik No PP..." list="lkhPpList" @keyup.enter="loadData" />
          <datalist id="lkhPpList">
            <option v-for="pp in master.pp" :key="pp.id" :value="pp.no_pp">{{ pp.no_pp }} - {{ pp.produk_nama }}</option>
          </datalist>
        </div>
      </div>
      <div class="d-flex justify-between align-center flex-wrap gap-05" style="border-top: 1px solid var(--border-light); padding-top: 0.5rem;">
        <span class="text-muted" style="font-size: 0.8rem;">Filter otomatis saat tanggal/shift diubah atau tekan tombol Cari.</span>
        <div class="d-flex gap-05">
          <button class="btn btn-primary btn-sm" @click="loadData">🔍 Cari / Filter</button>
          <button class="btn btn-secondary btn-sm" @click="resetFilter" title="Reset Filter">Reset</button>
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Shift</th>
            <th>Mesin</th>
            <th>Operator</th>
            <th>PIC</th>
            <th>No PP</th>
            <th>Produk</th>
            <th>Proses (pcs)</th>
            <th>DT (menit)</th>
            <th class="text-center" width="160">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="l in list" :key="l.pp_item_id || l.id">
            <td class="text-nowrap">{{ l.tanggal }}</td>
            <td class="text-center">
              <span class="badge badge-primary">Shift {{ l.shift }}</span>
            </td>
            <td class="text-nowrap">{{ l.mesin_nama }}</td>
            <td>{{ l.operator }}</td>
            <td class="text-nowrap">
              <span class="badge badge-info" style="font-size: 0.72rem; padding: 0.15rem 0.45rem;">
                👤 {{ l.pic || '-' }}
              </span>
            </td>
            <td class="text-nowrap">
              <span class="font-bold">{{ l.no_pp }}</span>
              <span class="badge" :class="l.tahap_sortir === 'S2' ? 'badge-warning' : (l.tahap_sortir === 'S3' ? 'badge-danger' : 'badge-primary')" style="margin-left: 5px; font-size: 0.72rem;">
                {{ l.tahap_sortir || 'S1' }}
              </span>
              <span v-if="l.is_pending || l.pp_status === 'req'" class="badge badge-warning" style="background: #fef3c7; color: #b45309; margin-left: 4px; font-size: 0.68rem; font-weight: bold;">
                Req (Pending OEE)
              </span>
              <span v-if="l.pp_count > 1" class="badge" style="background: #e0e7ff; color: #4338ca; margin-left: 4px; font-size: 0.7rem;" :title="`Bagian dari shift dengan ${l.pp_count} PP (Job Order #${l.urutan})`">
                PP #{{ l.urutan }}
              </span>
            </td>
            <td>{{ l.produk_nama }}</td>
            <td class="text-nowrap"><b>{{ Number(l.total_proses).toLocaleString('id-ID') }}</b></td>
            <td class="text-nowrap">
              <span v-if="Number(l.total_dt_menit) > 0" class="badge badge-warning">
                ⏱️ {{ l.total_dt_menit }} m
              </span>
              <span v-else class="text-muted">0</span>
            </td>
            <td class="text-center text-nowrap">
              <button class="btn btn-info btn-sm" style="margin-right: 3px;" @click="viewLkh(l)" title="Lihat Ringkasan & Downtime">
                👁️ View
              </button>
              
              <!-- Tombol Edit: hanya aktif jika PIC sesuai nama akun login, atau jika admin -->
              <router-link 
                v-if="canEdit(l)" 
                :to="`/lkh/edit/${l.id}`" 
                class="btn btn-primary btn-sm" 
                style="margin-right: 3px;" 
                title="Edit LKH ini"
              >
                ✏️ Edit
              </router-link>
              <span 
                v-else-if="authState.role !== 'viewer'" 
                class="badge badge-secondary" 
                style="font-size: 0.72rem; padding: 0.25rem 0.45rem; cursor: not-allowed; opacity: 0.6; margin-right: 3px; display: inline-block;" 
                :title="`Terkunci: Data ini diinput oleh PIC '${l.pic || '-'}' (bukan akun Anda).`"
              >
                🔒 Edit
              </span>

              <button v-if="authState.role === 'admin'" class="btn btn-danger btn-sm" @click="deleteLkh(l.id)" title="Hapus LKH">
                🗑️
              </button>
            </td>
          </tr>
          <tr v-if="list.length === 0">
            <td colspan="10" class="text-center text-muted" style="padding: 1.5rem;">Tidak ada data LKH yang sesuai filter.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-between align-center mt-1 flex-wrap gap-05">
      <button class="btn btn-secondary btn-sm" :disabled="page <= 1" @click="page--; loadData()">◀ Sebelumnya</button>
      <span class="text-muted" style="font-size: 0.85rem; font-weight: 600;">Halaman {{ page }}</span>
      <button class="btn btn-secondary btn-sm" :disabled="list.length < limit" @click="page++; loadData()">Selanjutnya ▶</button>
    </div>
  </div>

  <!-- Modal View LKH -->
  <div v-if="showModal" class="modal-overlay" @click.self="closeViewModal">
    <div class="modal-content" style="max-width: 780px;">
      <div class="d-flex justify-between align-center mb-1" style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
        <h3 style="margin: 0; font-size: 1.15rem;">
          <span>📋</span> Ringkasan LKH #{{ selectedLkh?.id }}
          <span v-if="selectedLkh?.urutan" style="font-size: 0.82rem; font-weight: normal; color: #4338ca; margin-left: 6px;">
            (Job Order #{{ selectedLkh.urutan }}: {{ selectedLkh.no_pp }})
          </span>
        </h3>
        <button class="btn btn-secondary btn-sm" @click="closeViewModal">✕</button>
      </div>

      <!-- Switcher jika shift memiliki lebih dari 1 Job Order (PP) -->
      <div v-if="selectedLkhDetail?.pp_items && selectedLkhDetail.pp_items.length > 1" class="d-flex gap-05 mb-1 p-05 border-rounded align-center flex-wrap" style="background: #eff6ff; border: 1px solid #bfdbfe;">
        <span class="text-muted" style="font-size: 0.8rem; font-weight: 600; color: #1e40af;">Job Order di Shift Ini:</span>
        <button 
          v-for="pItem in selectedLkhDetail.pp_items" 
          :key="pItem.id"
          type="button" 
          class="btn btn-xs" 
          :class="selectedLkh?.pp_item_id == pItem.id ? 'btn-primary' : 'btn-outline-primary'"
          style="font-size: 0.75rem;"
          @click="selectModalPpItem(pItem)"
        >
          PP #{{ pItem.urutan }}: {{ pItem.no_pp }} [{{ pItem.tahap_sortir || 'S1' }}]
        </button>
      </div>

      <div v-if="selectedLkh" style="font-size: 0.875rem; line-height: 1.5;">
        <!-- Basic Info -->
        <div v-if="selectedLkh.is_pending || selectedLkh.pp_status === 'req'" class="mb-1" style="background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; padding: 0.65rem 0.85rem; border-radius: 6px; font-size: 0.85rem; color: #92400e;">
          ⚠️ <b>Job Order ini masih berstatus Request (req).</b> Target Shift, Performance, dan OEE ditandai <b>Pending</b> sampai Admin menentukan Standar Speed (JPH) & Kuota Cetak di Master Data lalu menekan <b>ACC</b>.
        </div>

        <div class="grid grid-2 gap-1 mb-1" style="background: #f8fafc; padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border);">
          <div>
            <div><span class="text-muted">Tanggal:</span> <b>{{ selectedLkh.tanggal }}</b> (Shift {{ selectedLkh.shift }})</div>
            <div><span class="text-muted">Mesin:</span> <b>{{ selectedLkh.mesin_nama }}</b></div>
            <div><span class="text-muted">Operator:</span> <b>{{ selectedLkh.operator }}</b></div>
          </div>
          <div>
            <div>
              <span class="text-muted">Job Order:</span> <b>{{ selectedLkh.no_pp }}</b>
              <span class="badge" :class="selectedLkh.tahap_sortir === 'S2' ? 'badge-warning' : (selectedLkh.tahap_sortir === 'S3' ? 'badge-danger' : 'badge-primary')" style="margin-left: 6px; font-size: 0.72rem;">
                Tahap: {{ selectedLkh.tahap_sortir || 'S1' }}
              </span>
            </div>
            <div><span class="text-muted">Produk:</span> <b>{{ selectedLkh.produk_nama }}</b> ({{ selectedLkh.famili || '-' }})</div>
            <div style="font-size: 0.8rem; color: #475569;">
              <span class="text-muted">Jam:</span> {{ (selectedLkh.start || '').replace('T', ' ').substring(0, 16) }} s/d {{ (selectedLkh.finish || '').replace('T', ' ').substring(0, 16) }}
            </div>
          </div>
        </div>

        <!-- Production Stats (Responsive 2x2 on Mobile, 4-col on Desktop) -->
        <div class="grid grid-4 gap-1 mb-1">
          <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 0.65rem; border-radius: 8px; text-align: center;">
            <div class="kpi-label" style="color: #166534;">GOOD</div>
            <b class="text-success" style="font-size: 1.25rem;">{{ Number(selectedLkh.good || 0).toLocaleString('id-ID') }}</b> <span style="font-size: 0.75rem;">pcs</span>
          </div>
          <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 0.65rem; border-radius: 8px; text-align: center;">
            <div class="kpi-label" style="color: #991b1b;">REJECT WIP</div>
            <b class="text-danger" style="font-size: 1.25rem;">{{ Number(selectedLkh.reject || 0).toLocaleString('id-ID') }}</b> <span style="font-size: 0.75rem;">pcs</span>
          </div>
          <div style="background: #faf5ff; border: 1px solid #e9d5ff; padding: 0.65rem; border-radius: 8px; text-align: center;">
            <div class="kpi-label" style="color: #6b21a8;">STELL MESIN</div>
            <b style="color: #7c3aed; font-size: 1.25rem;">{{ Number(selectedLkh.setup_reject || 0).toLocaleString('id-ID') }}</b> <span style="font-size: 0.75rem;">pcs</span>
          </div>
          <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 0.65rem; border-radius: 8px; text-align: center;">
            <div class="kpi-label" style="color: #1e40af;">TOTAL PROSES</div>
            <b style="color: #1e3a8a; font-size: 1.25rem;">{{ Number(selectedLkh.total_proses || 0).toLocaleString('id-ID') }}</b> <span style="font-size: 0.75rem;">pcs</span>
          </div>
        </div>

        <!-- Times & Targets (Responsive Grid) -->
        <div class="grid grid-6 gap-05 mb-1" style="background: #f1f5f9; padding: 0.65rem; border-radius: 8px; text-align: center;">
          <div>
            <span class="text-muted" style="font-size: 0.75rem;">Target Shift</span><br/>
            <span v-if="selectedLkh.is_pending || selectedLkh.pp_status === 'req'" class="badge badge-warning" style="font-size: 0.72rem; padding: 2px 6px;">Pending (Req)</span>
            <b v-else>{{ Number(selectedLkh.plan_shift || 0).toLocaleString('id-ID') }}</b>
          </div>
          <div><span class="text-muted" style="font-size: 0.75rem;">5S & Laporan</span><br/><b style="color: #0f766e;">{{ selectedLkh['5s_menit'] || 0 }} m</b></div>
          <div><span class="text-muted" style="font-size: 0.75rem;">Waktu Prod</span><br/><b>{{ Number(selectedLkh.waktu_prod_jam || 0).toFixed(2) }} j</b></div>
          <div><span class="text-muted" style="font-size: 0.75rem;">Persiapan</span><br/><b style="color: #0f766e;">{{ selectedLkh.setting_menit || 0 }} m</b></div>
          <div><span class="text-muted" style="font-size: 0.75rem;">Total DT</span><br/><b style="color: #b45309;">{{ selectedLkh.total_dt_menit || 0 }} m</b></div>
          <div><span class="text-muted" style="font-size: 0.75rem;">Jam Jalan</span><br/><b style="color: #1e3a8a;">{{ Number(selectedLkh.jam_jalan || 0).toFixed(2) }} j</b></div>
        </div>

        <!-- OEE & 3 Pillars -->
        <div class="grid grid-5 gap-05 mb-1" style="background: #f8fafc; border: 1px solid var(--border); padding: 0.65rem; border-radius: 8px; text-align: center;">
          <div><span class="text-muted" style="font-size: 0.75rem;">Availability</span><br/><b>{{ (Number(selectedLkh.availability || 0) * 100).toFixed(1) }}%</b></div>
          <div>
            <span class="text-muted" style="font-size: 0.75rem;">Performance</span><br/>
            <span v-if="selectedLkh.is_pending || selectedLkh.pp_status === 'req'" class="badge badge-warning" style="font-size: 0.72rem; padding: 2px 6px;">Pending (Req)</span>
            <b v-else>{{ (Number(selectedLkh.performance || 0) * 100).toFixed(1) }}%</b>
          </div>
          <div><span class="text-muted" style="font-size: 0.75rem;">Quality OEE</span><br/><b style="color: #2563eb;">{{ (Number(selectedLkh.quality_oee || 0) * 100).toFixed(1) }}%</b></div>
          <div><span class="text-muted" style="font-size: 0.75rem;">Quality WIP</span><br/><b style="color: #7c3aed;">{{ (Number(selectedLkh.quality_wip || 0) * 100).toFixed(1) }}%</b></div>
          <div>
            <span class="text-muted" style="font-size: 0.75rem;">OEE</span><br/>
            <span v-if="selectedLkh.is_pending || selectedLkh.pp_status === 'req'" class="badge badge-warning" style="font-size: 0.75rem; padding: 2px 6px; font-weight: 700;">Pending (Req)</span>
            <b v-else style="color: #16a34a; font-size: 1.15rem;">{{ (Number(selectedLkh.oee || 0) * 100).toFixed(1) }}%</b>
          </div>
        </div>

        <!-- Detail Toggle Buttons -->
        <div class="d-flex gap-05 mb-1 flex-wrap">
          <button type="button" class="btn btn-sm" :class="showDtPanel ? 'btn-warning' : 'btn-primary'" @click="showDtPanel = !showDtPanel">
            ⏱️ {{ showDtPanel ? 'Tutup Rincian DT' : 'Lihat Rincian DT' }} ({{ selectedLkh.downtime?.length || 0 }} item)
          </button>
          <button type="button" class="btn btn-sm" :class="showCacatPanel ? 'btn-warning' : 'btn-secondary'" @click="showCacatPanel = !showCacatPanel">
            🔍 {{ showCacatPanel ? 'Tutup Detail Reject' : 'Lihat Detail Reject' }} ({{ rejectDetails.length }} jenis)
          </button>
        </div>

        <!-- DOWNTIME DETAIL PANEL -->
        <div v-if="showDtPanel" class="mb-1" style="background: #fffbeb; border: 1px solid #fef3c7; padding: 0.75rem; border-radius: 8px;">
          <div class="d-flex justify-between align-center mb-1 flex-wrap gap-05">
            <h4 style="margin: 0; color: #92400e; font-size: 0.95rem;">⏱️ Rincian Downtime (Total: {{ totalDtMenit }} Menit)</h4>
            <span class="text-muted" style="font-size: 0.75rem;">Setting: {{ selectedLkh.setting_menit || 0 }}m | 5S: {{ selectedLkh['5s_menit'] || 0 }}m</span>
          </div>

          <div class="table-responsive" v-if="selectedLkh.downtime && selectedLkh.downtime.length > 0" style="margin-bottom: 0;">
            <table>
              <thead>
                <tr style="background: #fef3c7; color: #78350f;">
                  <th>Kode</th>
                  <th>Arti / Deskripsi</th>
                  <th>Kategori</th>
                  <th>Waste</th>
                  <th class="text-right">Durasi</th>
                  <th>Keterangan</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(dt, i) in selectedLkh.downtime" :key="i">
                  <td><b>[{{ dt.kode }}]</b></td>
                  <td>{{ dt.arti }}</td>
                  <td><span class="badge badge-info">{{ dt.kategori || '-' }}</span></td>
                  <td><span class="badge" style="background: #e2e8f0;">{{ dt.jenis_waste || '-' }}</span></td>
                  <td class="text-right font-bold" style="color: #b45309;">{{ dt.menit }} m</td>
                  <td style="color: #475569;">{{ dt.keterangan || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else style="margin: 0.5rem 0 0; color: #92400e; font-style: italic;">Tidak ada catatan downtime pada shift ini.</p>

          <!-- Problem & Root Cause Notes -->
          <div v-if="selectedLkh.problem || selectedLkh.root_cause || selectedLkh.tindakan_perbaikan" style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #fde68a; font-size: 0.825rem; line-height: 1.5;">
            <div v-if="selectedLkh.problem"><b>Problem:</b> {{ selectedLkh.problem }}</div>
            <div v-if="selectedLkh.root_cause"><b>Root Cause:</b> {{ selectedLkh.root_cause }}</div>
            <div v-if="selectedLkh.tindakan_sementara"><b>Tindakan Sementara:</b> {{ selectedLkh.tindakan_sementara }}</div>
            <div v-if="selectedLkh.tindakan_perbaikan"><b>Tindakan Perbaikan:</b> {{ selectedLkh.tindakan_perbaikan }}</div>
            <div v-if="selectedLkh.pic"><b>PIC:</b> {{ selectedLkh.pic }}</div>
          </div>
        </div>

        <!-- REJECT DETAIL PANEL -->
        <div v-if="showCacatPanel" class="mb-1" style="background: #fef2f2; border: 1px solid #fee2e2; padding: 0.75rem; border-radius: 8px;">
          <h4 style="margin: 0 0 0.5rem 0; color: #991b1b; font-size: 0.95rem;">🔍 Rincian Reject WIP (Defect Cetak) &amp; Rusakan Stell</h4>
          <div class="grid grid-2 gap-1" style="font-size: 0.85rem;">
            <div style="background: white; padding: 0.75rem; border-radius: 6px; border: 1px solid #fecaca;">
              <div class="mb-1"><b style="color: #b91c1c;">Detail Reject WIP Cetak ({{ rejectDetailTotal }} pcs):</b></div>
              <ul v-if="rejectDetails.length" style="margin: 0; padding-left: 1.2rem;">
                <li v-for="(c, i) in rejectDetails" :key="'r'+i">{{ c.jenis_cacat_nama }}: <b>{{ Number(c.qty).toLocaleString('id-ID') }} pcs</b></li>
              </ul>
              <p v-else style="margin: 0; color: var(--text-muted); font-style: italic;">Tidak ada detail per jenis cacat (Total Reject WIP: {{ selectedLkh.reject }} pcs).</p>
            </div>
            <div style="background: white; padding: 0.75rem; border-radius: 6px; border: 1px solid #e9d5ff;">
              <div class="mb-1"><b style="color: #6b21a8;">Rusakan Stell (Setup Mesin Sortir):</b></div>
              <div style="font-size: 1.3rem; font-weight: bold; color: #7c3aed; margin-top: 0.25rem;">
                {{ Number(selectedLkh.setup_reject || 0).toLocaleString('id-ID') }} <span style="font-size: 0.8rem; font-weight: normal; color: #475569;">pcs</span>
              </div>
              <p style="margin: 0.5rem 0 0 0; color: #64748b; font-size: 0.75rem; font-style: italic;">
                Rusakan saat persiapan/setting mesin sortir (cuman 1 angka, beda dengan reject biasa).
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="text-right mt-1" style="border-top: 1px solid var(--border); padding-top: 0.75rem;">
        <button class="btn btn-secondary btn-sm" @click="closeViewModal">Tutup Modal</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { apiFetch, authState } from '../utils/api'

const list = ref([])
const showModal = ref(false)
const showDtPanel = ref(false)
const showCacatPanel = ref(false)
const selectedLkh = ref(null)

const canEdit = (lkh) => {
  if (authState.value.role === 'admin') return true
  if (authState.value.role === 'operator') {
    return !!lkh.pic && lkh.pic.trim().toLowerCase() === (authState.value.username || '').trim().toLowerCase()
  }
  return false
}

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

const selectedLkhDetail = ref(null)

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

const resetFilter = () => {
  filter.value = {
    tanggal: '',
    shift: '',
    tahap_sortir: '',
    mesin_id: '',
    no_pp: ''
  }
  page.value = 1
  loadData()
}

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


onMounted(async () => {
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
</script>
