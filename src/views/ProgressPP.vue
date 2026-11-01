<template>
  <div class="pv">
    <!-- ============ TOP FILTER & DIVISION TABS BAR ============ -->
    <div class="pv-filter">
      <div class="pv-filter-card">
        <!-- Row 1: Title & Search -->
        <div class="pv-filter-row">
          <div class="pv-title">
            <h2>Progress Job Order (PP) Terpadu Lintas Divisi</h2>
            <p>Pemantauan alur produksi bertingkat: Cetak (Plano) &rarr; Completing (Plano) &rarr; Cabut (Pcs) &rarr; Lipat (Pcs) &rarr; Sortir (Pcs)</p>
          </div>
          <div class="pv-spacer"></div>
          <div class="pv-search-box">
            <input
              type="text"
              v-model="searchQuery"
              class="pv-input pv-search"
              placeholder="🔍 Cari No PP atau nama produk..."
            />
            <button v-if="searchQuery" @click="searchQuery = ''" class="pv-btn-clear" title="Hapus pencarian">✕</button>
          </div>
        </div>

        <!-- Row 2: Division Navigation Tabs -->
        <div class="pv-filter-row pv-division-tabs">
          <button
            type="button"
            class="pv-tab-btn"
            :class="{ active: activeDivisionTab === 'all' }"
            @click="activeDivisionTab = 'all'"
          >
            🌐 Semua Divisi (Alur Terpadu 5 Baris)
          </button>
          <button
            type="button"
            class="pv-tab-btn"
            :class="{ active: activeDivisionTab === 'cetak' }"
            @click="activeDivisionTab = 'cetak'"
          >
            🖨️ Divisi Cetak (Plano)
          </button>
          <button
            type="button"
            class="pv-tab-btn"
            :class="{ active: activeDivisionTab === 'completing' }"
            @click="activeDivisionTab = 'completing'"
          >
            ✨ Divisi Completing (Plano)
          </button>
          <button
            type="button"
            class="pv-tab-btn"
            :class="{ active: activeDivisionTab === 'cabut' }"
            @click="activeDivisionTab = 'cabut'"
          >
            ✂️ Divisi Cabut (Pcs)
          </button>
          <button
            type="button"
            class="pv-tab-btn"
            :class="{ active: activeDivisionTab === 'lipat' }"
            @click="activeDivisionTab = 'lipat'"
          >
            📦 Divisi Lipat (Pcs)
          </button>
          <button
            type="button"
            class="pv-tab-btn"
            :class="{ active: activeDivisionTab === 'sortir' }"
            @click="activeDivisionTab = 'sortir'"
          >
            🔍 Divisi Sortir (Pcs)
          </button>
        </div>

        <!-- Row 3: Status Quick Filter -->
        <div class="pv-filter-row pv-filter-row-status">
          <div class="pv-seg">
            <button type="button" :class="{ on: statusFilter === '' }" @click="statusFilter = ''">
              Semua <span class="pv-seg-count">({{ allOrders.length }})</span>
            </button>
            <button type="button" class="st-aktif" :class="{ on: statusFilter === 'aktif' }" @click="statusFilter = 'aktif'">
              Aktif <span class="pv-seg-count">({{ kpiAktifCount }})</span>
            </button>
            <button type="button" class="st-req" :class="{ on: statusFilter === 'req' }" @click="statusFilter = 'req'">
              Request <span class="pv-seg-count">({{ kpiReqCount }})</span>
            </button>
            <button type="button" class="st-selesai" :class="{ on: statusFilter === 'selesai' }" @click="statusFilter = 'selesai'">
              Selesai <span class="pv-seg-count">({{ kpiSelesaiCount }})</span>
            </button>
          </div>
          <div class="pv-spacer"></div>
          <span class="pv-muted pv-count-label">
            Menampilkan <b>{{ filteredList.length }}</b> dari <b>{{ allOrders.length }}</b> Job Order
          </span>
        </div>
      </div>
    </div>

    <!-- ============ KPI SUMMARY STRIP (SEMUA DIVISI) ============ -->
    <section class="pv-stats">
      <div class="pv-stat">
        <span class="pv-eyebrow">Total Job Order</span>
        <b>{{ allOrders.length }}</b><small>PP terdaftar</small>
      </div>
      <div class="pv-stat">
        <span class="pv-eyebrow">PP Sedang Berjalan</span>
        <b class="txt-brand">{{ kpiAktifCount }}</b><small>di lini produksi</small>
      </div>
      <div class="pv-stat">
        <span class="pv-eyebrow">PP Selesai Rampung</span>
        <b class="txt-ok">{{ kpiSelesaiCount }}</b><small>siap kirim gudang</small>
      </div>
      <div class="pv-stat">
        <span class="pv-eyebrow">Menunggu ACC / Antri</span>
        <b class="txt-warn">{{ kpiReqCount }}</b><small>PP dalam antrian</small>
      </div>
    </section>

    <!-- ============ TAB CONTENT: SEMUA DIVISI (TAMPILAN 5 ROW PER PP) ============ -->
    <div v-if="activeDivisionTab === 'all'">
      <div v-if="filteredList.length === 0" class="pv-card pv-empty">
        Tidak ada Job Order yang sesuai dengan pencarian atau filter status.
      </div>

      <div v-else class="pv-list">
        <article v-for="item in filteredList" :key="item.id" class="pv-card">
          <!-- Card Header -->
          <header class="pv-head">
            <div class="pv-head-main">
              <div class="pv-pills">
                <span class="pv-no">{{ item.no_pp }}</span>
                <span v-if="item.status === 'aktif'" class="pv-pill tone-ok">Aktif di Produksi</span>
                <span v-else-if="item.status === 'req'" class="pv-pill tone-warn">Request &bull; Menunggu ACC</span>
                <span v-else class="pv-pill tone-bad">Selesai Produksi</span>
                <span class="pv-date">Update: {{ item.last_update }}</span>
              </div>
              <h3>{{ item.produk_nama }}</h3>
              <p>Target Kuota Cetak: <b>{{ formatNumber(item.plan_total) }} plano</b></p>
            </div>

            <div class="pv-actions">
              <router-link
                :to="`/lkh?no_pp=${encodeURIComponent(item.no_pp)}`"
                class="pv-btn pv-btn-dark pv-btn-sm"
                title="Lihat riwayat LKH Job Order ini"
              >
                📋 Riwayat LKH
              </router-link>
            </div>
          </header>

          <!-- Rute Alur Produksi Khusus PP Ini (Standar, Sortir Dulu, Tanpa Lipat, Tanpa Sortir) -->
          <div class="pv-routing-box">
            <div class="routing-label">
              <span class="routing-badge">Alur Produksi</span>
              <b>{{ item.alur_label }}</b>
            </div>
          </div>

          <!-- ============ 5 ROWS PER JOB ORDER (SATU ROW PER DIVISI) ============ -->
          <div class="pv-stage-rows">

            <!-- ROW 1: DIVISI CETAK (PLANO) -->
            <div class="stage-row" :class="{ 'row-done': item.cetak.status === 'selesai', 'row-active': item.cetak.status === 'proses' }">
              <div class="row-left">
                <div class="row-badge-wrap">
                  <span class="row-num">1</span>
                  <span class="row-tag tag-ctk">CETAK</span>
                  <span class="row-unit-badge">Satuan: PLANO</span>
                </div>
                <div class="row-details">
                  <div class="row-title">
                    <b>Divisi Cetak</b>
                    <span class="row-status-pill" :class="getStatusTone(item.cetak.status)">{{ getStatusLabel(item.cetak.status) }}</span>
                  </div>
                  <div class="row-sub">
                    <span>Mesin: <b>{{ item.cetak.mesin }}</b></span>
                    <span>Operator: <b>{{ item.cetak.operator }}</b></span>
                    <span v-if="item.cetak.opsi" class="row-opt">Opsi: <i>{{ item.cetak.opsi }}</i></span>
                  </div>
                </div>
              </div>

              <div class="row-right">
                <div class="row-metrics">
                  <div class="metric-cell">
                    <span class="pv-eyebrow">Target Cetak</span>
                    <b>{{ formatNumber(item.cetak.plan) }} <small>plano</small></b>
                  </div>
                  <div class="metric-cell">
                    <span class="pv-eyebrow">Realisasi Druk</span>
                    <b>{{ formatNumber(item.cetak.output) }} <small>plano</small></b>
                  </div>
                  <div class="metric-cell">
                    <span class="pv-eyebrow">Hasil Baik (Good)</span>
                    <b class="txt-ok">{{ formatNumber(item.cetak.good) }} <small>plano</small></b>
                  </div>
                  <div class="metric-cell">
                    <span class="pv-eyebrow">Afval / Reject</span>
                    <b class="txt-bad">{{ formatNumber(item.cetak.reject) }} <small>plano</small></b>
                  </div>
                  <div class="metric-cell cell-pct">
                    <span class="pv-eyebrow">Progress</span>
                    <b :class="item.cetak.progress >= 100 ? 'txt-ok' : 'txt-brand'">{{ item.cetak.progress.toFixed(1) }}%</b>
                  </div>
                </div>
                <div class="row-track">
                  <div
                    class="row-fill"
                    :class="item.cetak.progress >= 100 ? 'bg-ok' : (item.cetak.progress > 0 ? 'bg-brand' : 'bg-idle')"
                    :style="{ width: Math.min(item.cetak.progress, 100) + '%' }"
                  ></div>
                </div>
              </div>
            </div>

            <!-- ROW 2: DIVISI COMPLETING (PLANO) -->
            <div class="stage-row" :class="{ 'row-done': item.completing.status === 'selesai', 'row-active': item.completing.status === 'proses' }">
              <div class="row-left">
                <div class="row-badge-wrap">
                  <span class="row-num">2</span>
                  <span class="row-tag tag-cmp">COMPLETING</span>
                  <span class="row-unit-badge">Satuan: PLANO</span>
                </div>
                <div class="row-details">
                  <div class="row-title">
                    <b>Divisi Completing</b>
                    <span class="row-status-pill" :class="getStatusTone(item.completing.status)">{{ getStatusLabel(item.completing.status) }}</span>
                  </div>
                  <div class="row-sub">
                    <span>Mesin: <b>{{ item.completing.mesin }}</b></span>
                    <span>Operator: <b>{{ item.completing.operator }}</b></span>
                    <span v-if="item.completing.opsi" class="row-opt">Opsi: <i>{{ item.completing.opsi }}</i></span>
                  </div>
                </div>
              </div>

              <div class="row-right">
                <div class="row-metrics">
                  <div class="metric-cell">
                    <span class="pv-eyebrow">Input dari Cetak</span>
                    <b>{{ formatNumber(item.completing.plan) }} <small>plano</small></b>
                  </div>
                  <div class="metric-cell">
                    <span class="pv-eyebrow">Realisasi Finishing</span>
                    <b>{{ formatNumber(item.completing.output) }} <small>plano</small></b>
                  </div>
                  <div class="metric-cell">
                    <span class="pv-eyebrow">Hasil Baik (Good)</span>
                    <b class="txt-ok">{{ formatNumber(item.completing.good) }} <small>plano</small></b>
                  </div>
                  <div class="metric-cell">
                    <span class="pv-eyebrow">Afval / Reject</span>
                    <b class="txt-bad">{{ formatNumber(item.completing.reject) }} <small>plano</small></b>
                  </div>
                  <div class="metric-cell cell-pct">
                    <span class="pv-eyebrow">Progress</span>
                    <b :class="item.completing.progress >= 100 ? 'txt-ok' : 'txt-brand'">{{ item.completing.progress.toFixed(1) }}%</b>
                  </div>
                </div>
                <div class="row-track">
                  <div
                    class="row-fill"
                    :class="item.completing.progress >= 100 ? 'bg-ok' : (item.completing.progress > 0 ? 'bg-brand' : 'bg-idle')"
                    :style="{ width: Math.min(item.completing.progress, 100) + '%' }"
                  ></div>
                </div>
              </div>
            </div>

            <!-- ROW 3: DIVISI CABUT / POND (PLANO ➜ PCS) -->
            <div class="stage-row" :class="{ 'row-done': item.cabut.status === 'selesai', 'row-active': item.cabut.status === 'proses' }">
              <div class="row-left">
                <div class="row-badge-wrap">
                  <span class="row-num">3</span>
                  <span class="row-tag tag-cbt">CABUT / POND</span>
                  <span class="row-unit-badge highlight">Plano &rarr; PCS</span>
                </div>
                <div class="row-details">
                  <div class="row-title">
                    <b>Divisi Cabut (Die-Cutting)</b>
                    <span class="row-status-pill" :class="getStatusTone(item.cabut.status)">{{ getStatusLabel(item.cabut.status) }}</span>
                  </div>
                  <div class="row-sub">
                    <span>Mesin: <b>{{ item.cabut.mesin }}</b></span>
                    <span>Operator: <b>{{ item.cabut.operator }}</b></span>
                    <span v-if="item.cabut.opsi" class="row-opt">Opsi: <i>{{ item.cabut.opsi }}</i></span>
                  </div>
                </div>
              </div>

              <div class="row-right">
                <div class="row-metrics">
                  <div class="metric-cell">
                    <span class="pv-eyebrow">Input Diterima</span>
                    <b>{{ formatNumber(item.cabut.input_plano) }} <small>plano</small></b>
                  </div>
                  <div class="metric-cell">
                    <span class="pv-eyebrow">Realisasi Potong</span>
                    <b>{{ formatNumber(item.cabut.output) }} <small>pcs</small></b>
                  </div>
                  <div class="metric-cell">
                    <span class="pv-eyebrow">Hasil Baik (Good)</span>
                    <b class="txt-ok">{{ formatNumber(item.cabut.good) }} <small>pcs</small></b>
                  </div>
                  <div class="metric-cell">
                    <span class="pv-eyebrow">Afval / Reject</span>
                    <b class="txt-bad">{{ formatNumber(item.cabut.reject) }} <small>pcs</small></b>
                  </div>
                  <div class="metric-cell cell-pct">
                    <span class="pv-eyebrow">Progress</span>
                    <b :class="item.cabut.progress >= 100 ? 'txt-ok' : 'txt-brand'">{{ item.cabut.progress.toFixed(1) }}%</b>
                  </div>
                </div>
                <div class="row-track">
                  <div
                    class="row-fill"
                    :class="item.cabut.progress >= 100 ? 'bg-ok' : (item.cabut.progress > 0 ? 'bg-brand' : 'bg-idle')"
                    :style="{ width: Math.min(item.cabut.progress, 100) + '%' }"
                  ></div>
                </div>
              </div>
            </div>

            <!-- ROW 4: DIVISI LIPAT & LEM (PCS) (Bisa aktif atau dilewati) -->
            <div
              class="stage-row"
              :class="{
                'row-done': item.lipat.status === 'selesai',
                'row-active': item.lipat.status === 'proses',
                'row-skip': item.lipat.status === 'lewati'
              }"
            >
              <div class="row-left">
                <div class="row-badge-wrap">
                  <span class="row-num">4</span>
                  <span class="row-tag tag-lpt">LIPAT &amp; LEM</span>
                  <span class="row-unit-badge">Satuan: PCS</span>
                </div>
                <div class="row-details">
                  <div class="row-title">
                    <b>Divisi Lipat</b>
                    <span class="row-status-pill" :class="getStatusTone(item.lipat.status)">{{ getStatusLabel(item.lipat.status) }}</span>
                    <span v-if="item.lipat.urutan === 'setelah_sortir'" class="row-note-pill">Dikerjakan Setelah Sortir</span>
                  </div>
                  <div class="row-sub">
                    <template v-if="item.lipat.status !== 'lewati'">
                      <span>Mesin: <b>{{ item.lipat.mesin }}</b></span>
                      <span>Operator: <b>{{ item.lipat.operator }}</b></span>
                      <span v-if="item.lipat.opsi" class="row-opt">Opsi: <i>{{ item.lipat.opsi }}</i></span>
                    </template>
                    <template v-else>
                      <span class="txt-muted"><i>{{ item.lipat.opsi }}</i></span>
                    </template>
                  </div>
                </div>
              </div>

              <div class="row-right">
                <template v-if="item.lipat.status !== 'lewati'">
                  <div class="row-metrics">
                    <div class="metric-cell">
                      <span class="pv-eyebrow">Input Diterima</span>
                      <b>{{ formatNumber(item.lipat.plan) }} <small>pcs</small></b>
                    </div>
                    <div class="metric-cell">
                      <span class="pv-eyebrow">Realisasi Lipat</span>
                      <b>{{ formatNumber(item.lipat.output) }} <small>pcs</small></b>
                    </div>
                    <div class="metric-cell">
                      <span class="pv-eyebrow">Hasil Baik (Good)</span>
                      <b class="txt-ok">{{ formatNumber(item.lipat.good) }} <small>pcs</small></b>
                    </div>
                    <div class="metric-cell">
                      <span class="pv-eyebrow">Afval / Reject</span>
                      <b class="txt-bad">{{ formatNumber(item.lipat.reject) }} <small>pcs</small></b>
                    </div>
                    <div class="metric-cell cell-pct">
                      <span class="pv-eyebrow">Progress</span>
                      <b :class="item.lipat.progress >= 100 ? 'txt-ok' : 'txt-brand'">{{ item.lipat.progress.toFixed(1) }}%</b>
                    </div>
                  </div>
                  <div class="row-track">
                    <div
                      class="row-fill"
                      :class="item.lipat.progress >= 100 ? 'bg-ok' : (item.lipat.progress > 0 ? 'bg-brand' : 'bg-idle')"
                      :style="{ width: Math.min(item.lipat.progress, 100) + '%' }"
                    ></div>
                  </div>
                </template>
                <div v-else class="row-skip-msg">
                  <span>Tahap Lipat dilewati pada Job Order ini &bull; Langsung diteruskan ke proses berikutnya / kirim</span>
                </div>
              </div>
            </div>

            <!-- ROW 5: DIVISI SORTIR (PCS) (Dengan opsi S1, S2, S3) -->
            <div
              class="stage-row"
              :class="{
                'row-done': item.sortir.status === 'selesai',
                'row-active': item.sortir.status === 'proses',
                'row-skip': item.sortir.status === 'lewati'
              }"
            >
              <div class="row-left">
                <div class="row-badge-wrap">
                  <span class="row-num">5</span>
                  <span class="row-tag tag-srt">SORTIR &amp; QC</span>
                  <span class="row-unit-badge">Satuan: PCS</span>
                </div>
                <div class="row-details">
                  <div class="row-title">
                    <b>Divisi Sortir</b>
                    <span class="row-status-pill" :class="getStatusTone(item.sortir.status)">{{ getStatusLabel(item.sortir.status) }}</span>
                    <span v-if="item.sortir.urutan === 'sebelum_lipat'" class="row-note-pill">Disortir Sebelum Lipat (Flat Sheet)</span>
                  </div>
                  <div class="row-sub">
                    <template v-if="item.sortir.status !== 'lewati'">
                      <span>Mesin: <b>{{ item.sortir.mesin }}</b></span>
                      <span>Operator: <b>{{ item.sortir.operator }}</b></span>
                      <span v-if="item.sortir.opsi" class="row-opt">Opsi: <i>{{ item.sortir.opsi }}</i></span>
                    </template>
                    <template v-else>
                      <span class="txt-muted"><i>{{ item.sortir.opsi }}</i></span>
                    </template>
                  </div>
                </div>
              </div>

              <div class="row-right">
                <template v-if="item.sortir.status !== 'lewati'">
                  <div class="row-metrics">
                    <div class="metric-cell">
                      <span class="pv-eyebrow">Input Diterima</span>
                      <b>{{ formatNumber(item.sortir.plan) }} <small>pcs</small></b>
                    </div>
                    <div class="metric-cell">
                      <span class="pv-eyebrow">Total Disortir</span>
                      <b>{{ formatNumber(item.sortir.total_output) }} <small>pcs</small></b>
                    </div>
                    <div class="metric-cell">
                      <span class="pv-eyebrow">Hasil Baik (Good)</span>
                      <b class="txt-ok">{{ formatNumber(item.sortir.total_good) }} <small>pcs</small></b>
                    </div>
                    <div class="metric-cell">
                      <span class="pv-eyebrow">Total Reject</span>
                      <b class="txt-bad">{{ formatNumber(item.sortir.total_reject) }} <small>pcs</small></b>
                    </div>
                    <div class="metric-cell cell-pct">
                      <span class="pv-eyebrow">Progress</span>
                      <b :class="item.sortir.progress >= 100 ? 'txt-ok' : 'txt-brand'">{{ item.sortir.progress.toFixed(1) }}%</b>
                    </div>
                  </div>

                  <!-- Sub-Row Opsi Sortir: S1 Normal, S2 Ulang, S3 Ulang -->
                  <div class="sortir-sub-options">
                    <div class="s-opt-item">
                      <span class="s-tag tag-s1">S1 Normal</span>
                      <span>Output: <b>{{ formatNumber(item.sortir.s1.output) }}</b></span>
                      <span>Good: <b class="txt-ok">{{ formatNumber(item.sortir.s1.good) }}</b></span>
                      <span>Reject: <b class="txt-bad">{{ formatNumber(item.sortir.s1.reject) }}</b></span>
                    </div>
                    <div class="s-opt-item" :class="{ muted: !item.sortir.s2 || item.sortir.s2.output === 0 }">
                      <span class="s-tag tag-s2">S2 Ulang</span>
                      <template v-if="item.sortir.s2 && item.sortir.s2.output > 0">
                        <span>Output: <b>{{ formatNumber(item.sortir.s2.output) }}</b></span>
                        <span>Good: <b class="txt-ok">{{ formatNumber(item.sortir.s2.good) }}</b></span>
                        <span>Reject: <b class="txt-bad">{{ formatNumber(item.sortir.s2.reject) }}</b></span>
                      </template>
                      <span v-else class="txt-muted">Tidak ada sortir ulang 2</span>
                    </div>
                    <div class="s-opt-item" :class="{ muted: !item.sortir.s3 || item.sortir.s3.output === 0 }">
                      <span class="s-tag tag-s3">S3 Ulang</span>
                      <template v-if="item.sortir.s3 && item.sortir.s3.output > 0">
                        <span>Output: <b>{{ formatNumber(item.sortir.s3.output) }}</b></span>
                        <span>Good: <b class="txt-ok">{{ formatNumber(item.sortir.s3.good) }}</b></span>
                        <span>Reject: <b class="txt-bad">{{ formatNumber(item.sortir.s3.reject) }}</b></span>
                      </template>
                      <span v-else class="txt-muted">Tidak ada sortir ulang 3</span>
                    </div>
                  </div>

                  <div class="row-track">
                    <div
                      class="row-fill"
                      :class="item.sortir.progress >= 100 ? 'bg-ok' : (item.sortir.progress > 0 ? 'bg-brand' : 'bg-idle')"
                      :style="{ width: Math.min(item.sortir.progress, 100) + '%' }"
                    ></div>
                  </div>
                </template>
                <div v-else class="row-skip-msg">
                  <span>Tahap Sortir dilewati pada Job Order ini &bull; Langsung diteruskan dari Lipat ke Pengiriman</span>
                </div>
              </div>
            </div>

          </div>
        </article>
      </div>
    </div>

    <!-- ============ TAB CONTENT: CALL DIVISION SPECIFIC VIEWS ============ -->
    <div v-else-if="activeDivisionTab === 'cetak'">
      <CetakProgressPP :items="filteredList" :showHeader="false" />
    </div>

    <div v-else-if="activeDivisionTab === 'completing'">
      <CompletingProgressPP :items="filteredList" :showHeader="false" />
    </div>

    <div v-else-if="activeDivisionTab === 'cabut'">
      <CabutProgressPP :items="filteredList" :showHeader="false" />
    </div>

    <div v-else-if="activeDivisionTab === 'lipat'">
      <LipatProgressPP :items="filteredList" :showHeader="false" />
    </div>

    <div v-else-if="activeDivisionTab === 'sortir'">
      <SortirProgressPP :items="filteredList" :showHeader="false" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { jobOrdersState, formatNumber, getStatusTone, getStatusLabel } from '../data/progressPP'

// Memanggil progress PP di masing-masing folder divisi
import CetakProgressPP from './cetak/ProgressPP.vue'
import CompletingProgressPP from './completing/ProgressPP.vue'
import CabutProgressPP from './cabut/ProgressPP.vue'
import LipatProgressPP from './lipat/ProgressPP.vue'
import SortirProgressPP from './sortir/ProgressPP.vue'

const activeDivisionTab = ref('all')
const statusFilter = ref('')
const searchQuery = ref('')

const allOrders = computed(() => jobOrdersState.value)

// KPIs
const kpiAktifCount = computed(() => allOrders.value.filter(x => x.status === 'aktif').length)
const kpiReqCount = computed(() => allOrders.value.filter(x => x.status === 'req').length)
const kpiSelesaiCount = computed(() => allOrders.value.filter(x => x.status === 'selesai').length)

// Filtering (Tanpa Famili)
const filteredList = computed(() => {
  return allOrders.value.filter(item => {
    if (searchQuery.value) {
      const q = searchQuery.value.trim().toLowerCase()
      const noPp = (item.no_pp || '').toLowerCase()
      const prod = (item.produk_nama || '').toLowerCase()
      if (!noPp.includes(q) && !prod.includes(q)) return false
    }
    if (statusFilter.value && item.status !== statusFilter.value) {
      return false
    }
    return true
  })
})
</script>

<style scoped>
.pv {
  --c-brand: #4f46e5;
  --c-ok: #10b981;
  --c-warn: #d97706;
  --c-bad: #e11d48;
  --c-s3: #7c3aed;
  --c-ink: #0f172a;
  --c-text: #334155;
  --c-muted: #64748b;
  --c-line: #e2e8f0;
  --c-soft: #f8fafc;
  color: var(--c-text);
}

.pv-muted { color: var(--c-muted); font-size: 0.78rem; }
.pv-spacer { flex: 1; }
.pv-eyebrow { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: var(--c-muted); }

.txt-ok { color: var(--c-ok); }
.txt-warn { color: var(--c-warn); }
.txt-bad { color: var(--c-bad); }
.txt-brand { color: var(--c-brand); }
.txt-muted { color: var(--c-muted); }
.bg-ok { background: var(--c-ok); }
.bg-brand { background: linear-gradient(90deg, #6366f1, var(--c-brand)); }
.bg-idle { background: #cbd5e1; }

/* Filter Card */
.pv-filter { position: sticky; top: 0; z-index: 25; padding: 4px 0 10px; background: var(--bg, #f4f5fb); }
.pv-filter-card {
  background: #fff; border: 1px solid var(--c-line); border-radius: 14px;
  padding: 0.75rem 1rem; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
  display: flex; flex-direction: column; gap: 0.65rem;
}
.pv-filter-row { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
.pv-title h2 { margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--c-ink); }
.pv-title p { margin: 2px 0 0; font-size: 0.75rem; color: var(--c-muted); }

.pv-search-box { position: relative; display: flex; align-items: center; }
.pv-search {
  height: 34px; padding: 0 1.8rem 0 0.75rem; font-size: 0.82rem;
  border: 1px solid var(--c-line); border-radius: 8px; background: #fff; color: var(--c-ink);
  min-width: 260px;
}
.pv-search:focus { outline: 2px solid rgba(79, 70, 229, 0.2); border-color: var(--c-brand); }
.pv-btn-clear {
  position: absolute; right: 6px; background: none; border: none; font-size: 0.8rem;
  color: var(--c-muted); cursor: pointer; padding: 2px 4px;
}

/* Division Tabs */
.pv-division-tabs {
  display: flex; gap: 0.4rem; overflow-x: auto; padding-bottom: 2px;
  border-top: 1px solid var(--c-line); padding-top: 0.6rem;
}
.pv-tab-btn {
  background: #f8fafc; border: 1px solid var(--c-line); color: var(--c-text);
  font-size: 0.78rem; font-weight: 700; padding: 6px 12px; border-radius: 8px;
  cursor: pointer; white-space: nowrap; transition: all 0.15s;
  display: inline-flex; align-items: center; gap: 6px;
}
.pv-tab-btn:hover { background: #eef2ff; color: var(--c-brand); }
.pv-tab-btn.active {
  background: var(--c-brand); border-color: var(--c-brand); color: #fff;
  box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
}

/* Status Filter */
.pv-filter-row-status {
  padding-top: 0.5rem;
  border-top: 1px dashed var(--c-line);
}
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
.pv-seg-count { font-size: 0.7rem; font-weight: 700; margin-left: 2px; }

/* Stats Strip */
.pv-stats {
  display: grid; grid-template-columns: repeat(4, 1fr);
  background: #fff; border: 1px solid var(--c-line); border-radius: 14px;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05); margin-bottom: 1rem;
}
.pv-stat { padding: 0.8rem 1.1rem; display: flex; flex-wrap: wrap; align-items: baseline; gap: 0 6px; }
.pv-stat + .pv-stat { border-left: 1px solid var(--c-line); }
.pv-stat .pv-eyebrow { width: 100%; margin-bottom: 2px; }
.pv-stat b { font-size: 1.5rem; font-weight: 800; color: var(--c-ink); }
.pv-stat small { font-size: 0.76rem; color: var(--c-muted); }

/* Card List */
.pv-list { display: flex; flex-direction: column; gap: 1.25rem; }
.pv-card {
  background: #fff; border: 1px solid var(--c-line); border-radius: 14px;
  padding: 1.15rem; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
  display: flex; flex-direction: column; gap: 0.9rem; transition: box-shadow 0.15s;
}
.pv-card:hover { box-shadow: 0 6px 20px rgba(15, 23, 42, 0.09); }
.pv-empty { text-align: center; padding: 2.5rem; color: var(--c-muted); font-size: 0.85rem; }

.pv-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; flex-wrap: wrap; }
.pv-head-main { display: flex; flex-direction: column; gap: 2px; }
.pv-pills { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
.pv-no {
  font-size: 0.95rem; font-weight: 800; color: var(--c-brand);
  background: #eef2ff; border: 1px solid #c7d2fe; padding: 2px 10px; border-radius: 8px;
}
.pv-date { font-size: 0.72rem; color: var(--c-muted); margin-left: 4px; }
.pv-pill { font-size: 0.72rem; font-weight: 700; padding: 3px 9px; border-radius: 99px; }
.tone-ok { background: #d1fae5; color: #065f46; }
.tone-brand { background: #e0e7ff; color: #3730a3; }
.tone-warn { background: #fef3c7; color: #92400e; }
.tone-bad { background: #fee2e2; color: #991b1b; }
.tone-skip { background: #f1f5f9; color: #64748b; border: 1px dashed #cbd5e1; }
.tone-muted { background: #f1f5f9; color: #475569; }

.pv-head h3 { margin: 0.4rem 0 0; font-size: 1.12rem; font-weight: 800; color: var(--c-ink); }
.pv-head p { margin: 2px 0 0; font-size: 0.8rem; color: var(--c-muted); }
.pv-head p b { color: var(--c-text); }

.pv-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  height: 32px; padding: 0 0.8rem; font-size: 0.78rem; font-weight: 600; text-decoration: none;
  border-radius: 8px; cursor: pointer; transition: background 0.15s;
}
.pv-btn-dark { background: #1e293b; color: #fff; }
.pv-btn-dark:hover { background: #0f172a; }

/* Routing Box */
.pv-routing-box {
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
  padding: 0.45rem 0.75rem;
}
.routing-label {
  display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;
  font-size: 0.78rem; color: #334155;
}
.routing-badge {
  background: #0284c7; color: #fff; font-size: 0.68rem; font-weight: 800;
  padding: 2px 8px; border-radius: 4px; text-transform: uppercase;
}

/* ============ 5 ROWS (ONE PER DIVISION) ============ */
.pv-stage-rows {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.stage-row {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 1rem;
  align-items: center;
  background: #ffffff;
  border: 1px solid var(--c-line);
  border-radius: 10px;
  padding: 0.75rem 0.9rem;
  transition: all 0.15s;
}
.stage-row.row-done { border-color: #a7f3d0; background: #fcfdfd; }
.stage-row.row-active { border-color: #818cf8; background: #fafbff; box-shadow: 0 2px 6px rgba(99, 102, 241, 0.08); }
.stage-row.row-skip { background: #f8fafc; border-style: dashed; opacity: 0.8; }

.row-left {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.row-badge-wrap {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  flex-wrap: wrap;
}
.row-num {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: var(--c-line);
  color: var(--c-ink);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.72rem;
  font-weight: 800;
}
.row-tag {
  font-size: 0.7rem; font-weight: 800; padding: 2px 7px; border-radius: 99px; letter-spacing: 0.03em;
}
.tag-ctk { background: #e0e7ff; color: #4338ca; }
.tag-cmp { background: #fef3c7; color: #b45309; }
.tag-cbt { background: #e0f2fe; color: #0369a1; }
.tag-lpt { background: #dcfce7; color: #15803d; }
.tag-srt { background: #ede9fe; color: #6d28d9; }

.row-unit-badge {
  font-size: 0.68rem; font-weight: 700; padding: 2px 6px; border-radius: 4px;
  background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;
}
.row-unit-badge.highlight {
  background: #fef08a; color: #854d0e; border-color: #fde047; font-weight: 800;
}

.row-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.row-title b { font-size: 0.92rem; color: var(--c-ink); }
.row-status-pill {
  font-size: 0.68rem; font-weight: 700; padding: 2px 7px; border-radius: 99px;
}
.row-note-pill {
  font-size: 0.68rem; font-weight: 700; padding: 2px 7px; border-radius: 99px;
  background: #fdf4ff; color: #a21caf; border: 1px solid #f0abfc;
}

.row-sub {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: 0.74rem;
  color: var(--c-muted);
}
.row-sub b { color: var(--c-text); }
.row-opt { color: #0284c7; }

.row-right {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.row-metrics {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 0.5rem;
  background: #f8fafc;
  border: 1px solid var(--c-line);
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
}
.metric-cell { display: flex; flex-direction: column; gap: 2px; }
.metric-cell b { font-size: 0.95rem; font-weight: 800; color: var(--c-ink); }
.metric-cell small { font-size: 0.7rem; font-weight: 600; color: var(--c-muted); }
.cell-pct { text-align: right; }
.cell-pct b { font-size: 1.15rem; }

.row-track { position: relative; height: 8px; background: #eef2f7; border-radius: 99px; overflow: hidden; }
.row-fill { height: 100%; border-radius: 99px; transition: width 0.4s ease; }

.row-skip-msg {
  background: #f1f5f9;
  border: 1px dashed #cbd5e1;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  color: #64748b;
  font-size: 0.8rem;
  font-style: italic;
  text-align: center;
}

/* Sortir Sub-Options in Row 5 */
.sortir-sub-options {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
  margin-top: 0.2rem;
}
.s-opt-item {
  background: #fff;
  border: 1px solid var(--c-line);
  border-radius: 6px;
  padding: 0.35rem 0.55rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.72rem;
  flex-wrap: wrap;
}
.s-opt-item.muted { background: #f8fafc; border-style: dashed; }
.s-tag { font-size: 0.65rem; font-weight: 800; padding: 1px 5px; border-radius: 4px; }
.tag-s1 { background: #e0e7ff; color: #4338ca; }
.tag-s2 { background: #fef3c7; color: #b45309; }
.tag-s3 { background: #ede9fe; color: #6d28d9; }

/* Responsive */
@media (max-width: 992px) {
  .stage-row { grid-template-columns: 1fr; gap: 0.6rem; }
  .row-metrics { grid-template-columns: repeat(3, 1fr); }
  .sortir-sub-options { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
  .pv-stats { grid-template-columns: repeat(2, 1fr); }
  .pv-stat:nth-child(3) { border-left: none; }
  .pv-stat:nth-child(n+3) { border-top: 1px solid var(--c-line); }
  .row-metrics { grid-template-columns: repeat(2, 1fr); }
  .pv-filter-row-status { flex-direction: column; align-items: stretch; }
  .pv-search { width: 100%; min-width: auto; }
}
</style>
