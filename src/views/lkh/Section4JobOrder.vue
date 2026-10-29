<template>
  <div class="accordion-item mb-1" :class="{ 'is-open': isOpen }" style="border-color: #93c5fd;">
    <div class="accordion-header" @click="$emit('toggle')" style="background: #eff6ff;">
      <div class="accordion-header-left">
        <span class="step-circle" :class="{ 'step-done': isComplete }">
          <span v-if="isComplete">✓</span>
          <span v-else>4</span>
        </span>
        <div class="accordion-header-text">
          <h4 class="accordion-title" style="color: #1e40af;">
            <span>📦</span> 4. Job Order (PP), Hasil &amp; Downtime
          </h4>
          <div v-if="!isOpen" class="accordion-snippet" style="color: #1e3a8a;">
            {{ form.pp_items.length }} Job Order &bull; Total Proses: <b>{{ Number(summaryCalculated.total_proses).toLocaleString('id-ID') }} pcs</b>
          </div>
        </div>
      </div>
      <div class="accordion-header-right">
        <span class="badge badge-primary">{{ form.pp_items.length }} PP</span>
        <span class="accordion-chevron">{{ isOpen ? '▲' : '▼' }}</span>
      </div>
    </div>

    <div v-show="isOpen" class="accordion-body" style="background: #f8fafc; padding: 1rem 0.85rem;">

      <!-- REPEATABLE PP CARDS -->
      <div 
        v-for="(item, idx) in form.pp_items" 
        :key="idx" 
        class="card mb-15 pp-repeatable-card"
        :style="idx > 0 ? 'border-top: 3px solid #3b82f6;' : 'border-top: 3px solid #10b981;'"
      >
        <!-- Card Header PP -->
        <div class="d-flex justify-between align-center flex-wrap gap-05 mb-1 pb-05 border-bottom">
          <div class="d-flex align-center gap-05">
            <span class="badge" :class="idx === 0 ? 'badge-success' : 'badge-primary'" style="font-size: 0.82rem; padding: 0.3rem 0.65rem;">
              PP #{{ idx + 1 }}
            </span>
            <span class="font-bold" style="font-size: 1rem; color: #0f172a;">
              {{ getPpLabel(item.pp_id) || `Job Order ke-${idx + 1}` }}
            </span>
            <span v-if="item.tahap_sortir" class="badge badge-info" style="font-size: 0.72rem;">
              {{ item.tahap_sortir }}
            </span>
          </div>
          <div class="d-flex align-center gap-05">
            <span v-if="perPpCalculated[idx]" class="text-muted" style="font-size: 0.8rem;">
              Jam Jalan: <b>{{ perPpCalculated[idx].jam_jalan.toFixed(2) }} jam</b> &bull; Proses: <b>{{ Number(perPpCalculated[idx].total_proses).toLocaleString('id-ID') }} pcs</b>
            </span>
            <button 
              v-if="form.pp_items.length > 1" 
              type="button" 
              class="btn btn-outline-danger btn-xs" 
              @click="$emit('remove-pp', idx)"
              title="Hapus PP ini dari daftar shift"
            >
              ✕ Hapus PP Ini
            </button>
          </div>
        </div>

        <!-- Jam Pindah (Khusus PP ke-2 dan seterusnya) / Waktu Mulai PP -->
        <div class="time-segment-bar mb-1 p-075 border-rounded" :class="idx === 0 ? 'bg-green-soft' : 'bg-blue-soft'">
          <div v-if="idx === 0" class="d-flex justify-between align-center flex-wrap gap-05" style="font-size: 0.85rem;">
            <div>
              <span>🕒</span> <b>Jam Mulai PP 1:</b> Otomatis mengikuti <b>Start Shift</b>: 
              <b class="text-success">{{ formatTimeSnippet(form.start) }}</b>
              <span v-if="form.setting_menit > 0" class="text-muted" style="margin-left: 6px;">
                (Dikurangi Persiapan Awal: {{ form.setting_menit }}m)
              </span>
            </div>
            <div class="text-muted" style="font-size: 0.78rem;">
              Selesai saat: <b>{{ form.pp_items.length > 1 ? formatTimeSnippet(form.pp_items[1].jam_mulai) : formatTimeSnippet(form.finish) }}</b>
            </div>
          </div>

          <div v-else>
            <div class="form-group" style="margin-bottom: 0;">
              <label style="font-weight: 700; color: #1e40af;">
                <span>⏱️</span> Jam Pindah ke PP Ini <span class="text-danger">*</span>
              </label>
              <div class="d-flex align-center gap-05 flex-wrap">
                <input 
                  type="datetime-local" 
                  v-model="item.jam_mulai" 
                  class="form-control font-bold" 
                  required 
                  style="max-width: 280px; border-color: #93c5fd;" 
                />
                <small class="text-muted">
                  Waktu peralihan mesin ke PP #{{ idx + 1 }} (antara {{ formatTimeSnippet(form.start) }} s/d {{ formatTimeSnippet(form.finish) }})
                </small>
              </div>
              <div v-if="!isJamPindahValid(idx)" class="text-danger mt-02" style="font-size: 0.75rem; font-weight: 600;">
                ⚠️ Jam pindah harus setelah jam mulai PP sebelumnya dan sebelum/sama dengan Finish Shift.
              </div>
            </div>
          </div>
        </div>

        <!-- Pilih PP & Info Produk & Tahap Sortir -->
        <div class="grid grid-2 gap-1 mb-1">
          <div class="form-group" style="margin-bottom: 0;">
            <div class="d-flex justify-between align-center mb-02">
              <label style="margin-bottom: 0;">Nomor PP <span class="text-danger">*</span></label>
              <button 
                type="button" 
                class="btn btn-outline-primary btn-xs" 
                style="font-size: 0.72rem; padding: 2px 7px;"
                @click="$emit('open-request-pp', idx)"
                title="Nomor PP belum ada di master data? Klik untuk request PP baru"
              >
                + Request PP
              </button>
            </div>
            <select v-model="item.pp_id" class="form-control" required @change="$emit('pp-change', idx)">
              <option value="">-- Pilih Nomor PP --</option>
              <option 
                v-for="p in master.pp.filter(x => x.status === 'aktif' || x.status === 'acc' || x.status === 'req' || x.id === item.pp_id)" 
                :key="p.id" 
                :value="p.id"
              >
                {{ p.no_pp }} ({{ p.produk_nama }}) {{ p.status === 'req' ? '— [REQ / Pending ACC]' : '' }}
              </option>
            </select>
          </div>

          <!-- PP Info Card -->
          <div v-if="getPpObj(item.pp_id)" class="pp-info-card">
            <div style="font-size: 0.85rem; line-height: 1.4;">
              <div>Produk: <b>{{ getPpObj(item.pp_id).produk_nama }}</b> <span class="text-muted">({{ getPpObj(item.pp_id).famili || '-' }})</span></div>
              <div v-if="getPpObj(item.pp_id).status === 'req'" class="mt-02">
                <span class="badge badge-warning" style="background: #fef3c7; color: #b45309; font-weight: 700; font-size: 0.75rem;">
                  ⏳ Status: Request PP (Menunggu ACC Admin)
                </span>
                <div class="text-muted mt-02" style="font-size: 0.73rem;">
                  Standar JPH dan Kuota Cetak belum ditetapkan Admin. OEE dihitung setelah di-ACC.
                </div>
              </div>
              <div v-else>
                <div>Std Speed: <b class="text-primary">{{ getPpObj(item.pp_id).std_jph }} JPH</b> | Cetak Total: <b>{{ Number(getPpObj(item.pp_id).plan_total).toLocaleString('id-ID') }}</b> pcs</div>
                <div v-if="item.akumulasiPp?.total_s1 > 0" class="text-muted" style="font-size: 0.75rem;">
                  Progres Tersortir S1: <b>{{ Number(item.akumulasiPp.total_s1).toLocaleString('id-ID') }}</b> pcs
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tahap Sortir Selection (S1 / S2 / S3) -->
        <div class="mb-1 p-075 border-rounded bg-white">
          <label style="font-weight: 700; margin-bottom: 0.35rem; display: block; font-size: 0.82rem;">
            Tahap Sortir PP Ini <span class="text-danger">*</span>
          </label>
          <div class="d-flex gap-05 flex-wrap align-center">
            <button type="button" class="btn btn-sm" :class="item.tahap_sortir === 'S1' ? 'btn-primary' : 'btn-secondary'" @click="item.tahap_sortir = 'S1'">
              ✓ S1 (Normal)
            </button>
            <button type="button" class="btn btn-sm" :class="item.tahap_sortir === 'S2' ? 'btn-warning' : 'btn-secondary'" @click="item.tahap_sortir = 'S2'">
              🔄 S2 (Sortir Ulang 2)
            </button>
            <button type="button" class="btn btn-sm" :class="item.tahap_sortir === 'S3' ? 'btn-danger' : 'btn-secondary'" @click="item.tahap_sortir = 'S3'">
              🔄 S3 (Sortir Ulang 3)
            </button>
          </div>
        </div>

        <!-- Hasil Sortir PP -->
        <div class="mb-1 p-075 border-rounded bg-white">
          <h5 style="margin: 0 0 0.6rem 0; font-size: 0.88rem; color: #1e293b;">
            📊 Hasil Sortir PP #{{ idx + 1 }}
          </h5>

          <div class="grid grid-4 gap-1">
            <div class="form-group" style="margin-bottom: 0;">
              <label>HASIL BAIK (GOOD) <span class="text-danger">*</span></label>
              <input 
                type="number" 
                v-model.number="item.good" 
                class="form-control font-bold" 
                min="0" 
                required 
                style="color: var(--success); font-size: 1.05rem;" 
                placeholder="0"
                @input="$emit('result-change', item)"
              />
            </div>

            <div class="form-group" style="margin-bottom: 0;">
              <div class="d-flex justify-between align-center">
                <label>REJECT WIP</label>
              </div>
              <div class="d-flex align-center gap-05">
                <input 
                  type="number" 
                  :value="item.reject || 0" 
                  class="form-control font-bold text-danger" 
                  readonly 
                  style="font-size: 1.05rem; background: #fff1f2; cursor: pointer;"
                  @click="$emit('open-modal-cacat', idx)"
                />
                <button type="button" class="btn btn-primary btn-xs" @click="$emit('open-modal-cacat', idx)" style="white-space: nowrap;">
                  Rincian
                </button>
              </div>
              <small class="text-muted" style="font-size: 0.72rem;">Klik untuk isi rincian cacat</small>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
              <label>RUSAKAN STELL</label>
              <input 
                type="number" 
                v-model.number="item.setup_reject" 
                class="form-control font-bold" 
                min="0" 
                placeholder="0" 
                style="color: #7c3aed; font-size: 1.05rem;"
                @input="$emit('result-change', item)"
              />
            </div>

            <div class="form-group" style="margin-bottom: 0;">
              <div class="d-flex justify-between align-center">
                <label>TOTAL PROSES</label>
                <button 
                  v-if="item.isTotalLayarManual" 
                  type="button" 
                  class="btn btn-xs btn-outline-primary" 
                  @click="$emit('sync-total-layar', item)"
                  style="font-size: 0.68rem; padding: 1px 4px;"
                  title="Kembalikan ke kalkulasi otomatis"
                >
                  ↺ Reset Auto
                </button>
              </div>
              <input 
                type="number" 
                v-model.number="item.total_layar" 
                class="form-control font-bold" 
                min="0" 
                @input="$emit('manual-total-layar', item)"
                style="color: #1e293b; font-size: 1.05rem; background: #f8fafc;" 
              />
              <small class="text-muted" style="font-size: 0.7rem;">
                {{ item.isTotalLayarManual ? '⚠️ Nilai manual aktif' : '✓ Otomatis terhitung' }}
              </small>
            </div>
          </div>
        </div>

        <!-- Tabel Downtime PP Ini -->
        <div class="p-075 border-rounded" style="background: #fffdf5; border-color: #fde68a;">
          <div class="d-flex justify-between align-center flex-wrap gap-05 mb-05">
            <div class="d-flex align-center gap-05">
              <h5 style="margin: 0; font-size: 0.88rem; color: #92400e;">
                ⏱️ Downtime Khusus PP #{{ idx + 1 }}
              </h5>
              <span class="badge badge-warning" style="font-size: 0.72rem;">
                {{ (item.downtime || []).reduce((acc, c) => acc + (c.menit || 0), 0) }} menit
              </span>
            </div>
            <div class="d-flex align-center gap-05 flex-wrap">
              <button type="button" class="btn btn-success btn-xs" @click="$emit('add-dt', item)">
                + INPUT DOWNTIME
              </button>
            </div>
          </div>

          <!-- Input / Daftar Downtime Baris per Baris -->
          <div v-if="(item.downtime || []).length > 0" class="downtime-list-container">
            <div 
              v-for="(dt, dtIdx) in item.downtime" 
              :key="dtIdx" 
              class="downtime-row-card mb-05 p-05 border-rounded" 
              style="background: white; border: 1px solid #fde68a;"
            >
              <!-- Baris Atas: Kode DT, Durasi (menit), dan Tombol Hapus -->
              <div class="d-flex align-center gap-05 flex-wrap">
                <div style="flex: 1; min-width: 200px;">
                  <label class="text-muted" style="font-size: 0.72rem; margin-bottom: 2px; display: block; font-weight: 600;">
                    Kode Downtime <span class="text-danger">*</span>
                  </label>
                  <select v-model="dt.kode_id" class="form-control" required style="font-size: 0.85rem; padding: 4px 6px;">
                    <option value="">-- Pilih Kode Downtime --</option>
                    <option v-for="kd in master.kode_downtime.filter(x => x.aktif != 0 || x.id === dt.kode_id)" :key="kd.id" :value="kd.id">
                      [{{ kd.kode }}] {{ kd.arti }}
                    </option>
                  </select>
                </div>

                <div style="width: 110px;">
                  <label class="text-muted" style="font-size: 0.72rem; margin-bottom: 2px; display: block; font-weight: 600;">
                    Durasi <span class="text-danger">*</span>
                  </label>
                  <div class="d-flex align-center gap-02">
                    <input 
                      type="number" 
                      v-model.number="dt.menit" 
                      class="form-control font-bold" 
                      min="1" 
                      required 
                      style="padding: 4px 6px; font-size: 0.85rem;" 
                      placeholder="0" 
                    />
                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;">menit</span>
                  </div>
                </div>

                <div style="align-self: flex-end; padding-bottom: 2px;">
                  <button 
                    type="button" 
                    class="btn btn-outline-danger btn-xs" 
                    @click="item.downtime.splice(dtIdx, 1)" 
                    title="Hapus downtime ini"
                    style="padding: 5px 8px; font-size: 0.75rem;"
                  >
                    🗑️ Hapus
                  </button>
                </div>
              </div>

              <!-- Baris Bawah: Keterangan / Penjelasan Kendala (Langsung Terlihat Tanpa Scroll) -->
              <div class="mt-05 pt-05" style="border-top: 1px dashed #fef3c7;">
                <label class="text-muted" style="font-size: 0.72rem; margin-bottom: 2px; display: block; font-weight: 600;">
                  📝 Keterangan / Penyebab Kendala:
                </label>
                <input 
                  type="text" 
                  v-model="dt.keterangan" 
                  class="form-control" 
                  placeholder="Ketik keterangan atau uraian kendala di sini..." 
                  style="font-size: 0.85rem; padding: 5px 8px; background: #fffdf5;" 
                />
              </div>
            </div>
          </div>
          <div v-else class="text-muted text-center p-05" style="font-size: 0.8rem;">
            Tidak ada kendala downtime tercatat untuk PP ini.
          </div>
        </div>

      </div>

      <!-- Bottom Button: Tambah PP Baru -->
      <div class="text-center mt-1 mb-1">
        <button 
          type="button" 
          class="btn btn-outline-primary" 
          @click="$emit('add-pp')"
          style="padding: 0.65rem 1.75rem; font-weight: 700; border-width: 2px;"
        >
          <span>➕</span> Tambah Job Order (PP) Berikutnya
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
defineProps({
  form: { type: Object, required: true },
  master: { type: Object, required: true },
  isOpen: { type: Boolean, default: true },
  isComplete: { type: Boolean, default: false },
  perPpCalculated: { type: Array, required: true },
  summaryCalculated: { type: Object, required: true },
  downtimeShortcuts: { type: Array, required: true },
  getPpLabel: { type: Function, required: true },
  getPpObj: { type: Function, required: true },
  formatTimeSnippet: { type: Function, required: true },
  isJamPindahValid: { type: Function, required: true },
  getDtCat: { type: Function, required: true },
  getDtWaste: { type: Function, required: true },
  getCategoryBadgeStyle: { type: Function, required: true }
})

defineEmits([
  'toggle', 
  'next', 
  'add-pp', 
  'remove-pp', 
  'pp-change', 
  'open-request-pp',
  'result-change', 
  'open-modal-cacat', 
  'sync-total-layar', 
  'manual-total-layar', 
  'quick-add-dt', 
  'add-dt'
])
</script>
