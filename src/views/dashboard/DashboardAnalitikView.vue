<template>
  <div class="dv">

    <!-- ============ FILTER BAR ============ -->
    <div class="dv-filter no-print">
      <div class="dv-filter-card">
        <div class="dv-filter-row">
          <!-- Preset periode -->
          <div class="dv-seg">
            <button type="button" :class="{ on: activePreset === 7 }" @click="applyPreset(7)">7 hari</button>
            <button type="button" :class="{ on: activePreset === 30 }" @click="applyPreset(30)">30 hari</button>
            <button type="button" :class="{ on: activePreset === 'month' }" @click="applyPreset('month')">Bulan ini</button>
          </div>

          <!-- Desktop: tanggal + shift -->
          <div class="dv-inline-fields desktop-only">
            <input type="date" v-model="filter.start_date" class="dv-input" @change="activePreset = null" />
            <span class="dv-sep">&ndash;</span>
            <input type="date" v-model="filter.end_date" class="dv-input" @change="activePreset = null" />
            <select v-model="filter.shift" class="dv-input">
              <option value="">Semua shift</option>
              <option value="1">Shift 1</option>
              <option value="2">Shift 2</option>
              <option value="3">Shift 3</option>
            </select>
            <button type="button" class="dv-btn dv-btn-ghost" @click="showAdvanced = !showAdvanced">
              Lainnya
              <span v-if="advancedCount" class="dv-count">{{ advancedCount }}</span>
              <span class="dv-caret">{{ showAdvanced ? '▲' : '▼' }}</span>
            </button>
            <button type="button" class="dv-btn dv-btn-primary" @click="loadDashboard">Terapkan</button>
          </div>

          <div class="dv-spacer"></div>

          <!-- Mobile filter & cetak -->
          <div class="mobile-only dv-row-gap">
            <button type="button" class="dv-btn dv-btn-ghost" @click="showMobileFilterSheet = true">
              Filter <span v-if="activeFilterCount" class="dv-count">{{ activeFilterCount }}</span>
            </button>
            <button v-if="canPrint" type="button" class="dv-btn dv-btn-print" @click="printDashboard" title="Cetak Dashboard 1 Lembar (A4 Landscape)">
              🖨️ Cetak
            </button>
          </div>

          <!-- Export -->
          <div class="dv-dropdown" ref="exportDropdownRef">
            <button type="button" class="dv-btn dv-btn-dark" @click="showExportDropdown = !showExportDropdown">
              Export <span class="dv-caret">▼</span>
            </button>
            <div v-if="showExportDropdown" class="dv-menu">
              <button type="button" class="dv-menu-item" @click="handleExportAction('excel')">
                <b>Excel LKH</b><small>Rekap produksi &amp; OEE</small>
              </button>
              <button type="button" class="dv-menu-item" @click="handleExportAction('cc')">
                <b>Cost Control (CC)</b><small>Format ISO, 8 Waste &amp; verifikasi</small>
              </button>
              <div class="dv-menu-divider"></div>
              <button type="button" class="dv-menu-item disabled" @click="handleExportAction('kiriman')">
                <b>Kiriman</b><small>Segera hadir</small>
              </button>
              <button type="button" class="dv-menu-item disabled" @click="handleExportAction('stb')">
                <b>STB</b><small>Segera hadir</small>
              </button>
            </div>
          </div>

          <!-- Tombol Cetak Dashboard (Desktop) -->
          <button v-if="canPrint" type="button" class="dv-btn dv-btn-print desktop-only" @click="printDashboard" title="Cetak Dashboard 1 Lembar (A4 Landscape)">
            🖨️ Cetak Dashboard
          </button>
        </div>

        <!-- Filter lanjutan (desktop) -->
        <div v-if="showAdvanced" class="dv-advanced desktop-only">
          <div class="dv-field">
            <label>Tahap sortir</label>
            <select v-model="filter.tahap_sortir" class="dv-input">
              <option value="">Semua tahap</option>
              <option value="S1">S1 (Normal)</option>
              <option value="S2">S2 (Sortir ulang 2)</option>
              <option value="S3">S3 (Sortir ulang 3)</option>
            </select>
          </div>
          <div class="dv-field">
            <label>No PP</label>
            <input
              type="text" v-model="filter.no_pp" class="dv-input" placeholder="Cari No PP..."
              list="ppDatalistRef" @keyup.enter="loadDashboard"
            />
            <datalist id="ppDatalistRef">
              <option v-for="pp in master.pp" :key="pp.id" :value="pp.no_pp">{{ pp.no_pp }} - {{ pp.produk_nama }}</option>
            </datalist>
          </div>
          <div class="dv-field dv-multi" ref="produkDropdownRef">
            <label>Produk</label>
            <div class="dv-input dv-multi-btn" @click="showProdukDropdown = !showProdukDropdown">
              <span class="dv-ellipsis">{{ getProdukFilterLabel }}</span>
              <span class="dv-caret">{{ showProdukDropdown ? '▲' : '▼' }}</span>
            </div>
            <div v-if="showProdukDropdown" class="dv-pop">
              <input type="text" v-model="searchProduk" class="dv-input" placeholder="Cari produk..." @click.stop />
              <div class="dv-pop-actions">
                <button type="button" class="dv-link" @click.stop="selectAllProduk">Pilih semua</button>
                <button type="button" class="dv-link" @click.stop="clearAllProduk">Reset</button>
                <span class="dv-muted">{{ filter.produk_ids.length }}/{{ master.produk.length }}</span>
              </div>
              <div class="dv-pop-list">
                <label v-for="pr in filteredProdukList" :key="pr.id" class="dv-pop-item" @click.stop>
                  <input type="checkbox" :checked="filter.produk_ids.includes(Number(pr.id))" @change="toggleProduk(pr.id)" />
                  <span>{{ pr.nama }} <small class="dv-muted">{{ pr.famili || '' }}</small></span>
                </label>
              </div>
            </div>
          </div>
          <div class="dv-field dv-field-end">
            <button type="button" class="dv-btn dv-btn-ghost" @click="resetFilter">Reset semua</button>
          </div>
        </div>

        <!-- Chip filter aktif -->
        <div v-if="activeFilterCount" class="dv-chips desktop-only">
          <span v-if="filter.shift" class="dv-chip">Shift {{ filter.shift }} <b @click="filter.shift=''; loadDashboard()">✕</b></span>
          <span v-if="filter.tahap_sortir" class="dv-chip">Tahap {{ filter.tahap_sortir }} <b @click="filter.tahap_sortir=''; loadDashboard()">✕</b></span>
          <span v-if="filter.no_pp" class="dv-chip">PP {{ filter.no_pp }} <b @click="filter.no_pp=''; loadDashboard()">✕</b></span>
          <span v-for="pid in filter.produk_ids" :key="pid" class="dv-chip">{{ getProdukName(pid) }} <b @click="removeProduk(pid); loadDashboard()">✕</b></span>
        </div>
      </div>
    </div>

    <!-- ============ TAMPILAN DASHBOARD SCREEN (2x2 STANDAR SEPERTI SEBELUMNYA) ============ -->
    <div class="dv-screen-view no-print">

      <!-- HERO: OEE + 3 pilar -->
      <section class="dv-hero">
        <div class="dv-oee">
          <div class="dv-oee-top">
            <span class="dv-eyebrow">OEE keseluruhan</span>
            <span class="dv-pill" :class="'tone-' + oeeTone">{{ kpiStatusText }}</span>
          </div>
          <div class="dv-oee-value" :class="'txt-' + oeeTone">{{ oeeVal.toFixed(1) }}<small>%</small></div>
          <div class="dv-track dv-track-lg">
            <div class="dv-fill" :class="'bg-' + oeeTone" :style="{ width: Math.min(100, oeeVal) + '%' }"></div>
            <div class="dv-target-mark" style="left: 85%"><span>85%</span></div>
          </div>
          <div class="dv-oee-foot">
            <span v-if="oeeGap >= 0">Di atas target <b>+{{ oeeGap.toFixed(1) }} poin</b></span>
            <span v-else>Di bawah target <b>{{ oeeGap.toFixed(1) }} poin</b></span>
            <span class="dv-muted">Target 85%</span>
          </div>
        </div>

        <div class="dv-pillars">
          <div v-for="p in pillars" :key="p.key" class="dv-pillar">
            <div class="dv-pillar-head">
              <span class="dv-eyebrow">{{ p.label }}</span>
              <span class="dv-pillar-val" :class="'txt-' + p.tone">{{ p.value.toFixed(1) }}%</span>
            </div>
            <div class="dv-track">
              <div class="dv-fill" :class="'bg-' + p.tone" :style="{ width: Math.min(100, p.value) + '%' }"></div>
              <div class="dv-target-mark" :style="{ left: p.target + '%' }"></div>
            </div>
            <div class="dv-pillar-foot">
              <span>{{ p.sub }}</span>
              <span class="dv-muted">Target {{ p.target }}%</span>
            </div>
          </div>
        </div>
      </section>

      <!-- Ringkasan volume -->
      <section class="dv-stats">
        <div class="dv-stat">
          <span class="dv-eyebrow">Total proses</span>
          <b>{{ fmt(data.kpi?.total_proses) }}</b><small>pcs</small>
        </div>
        <div class="dv-stat">
          <span class="dv-eyebrow">Produk baik</span>
          <b class="txt-ok">{{ fmt(data.kpi?.good) }}</b><small>pcs</small>
        </div>
        <div class="dv-stat">
          <span class="dv-eyebrow">Reject total</span>
          <b class="txt-bad">{{ (Number(data.kpi?.ng_percent) || 0).toFixed(2) }}%</b>
          <small>{{ fmt(totalRejectPcs) }} pcs</small>
        </div>
        <div class="dv-stat">
          <span class="dv-eyebrow">Kualitas bahan cetak (WIP)</span>
          <b :class="'txt-' + rateTone(data.kpi?.quality_wip, 95)">{{ (Number(data.kpi?.quality_wip) || 0).toFixed(1) }}%</b>
          <small>target 95%</small>
        </div>
      </section>

      <!-- Baris Grafik 1: Tren OEE & Pareto Downtime -->
      <section class="dv-charts">
        <div class="dv-card">
          <div class="dv-card-head">
            <div>
              <h3>Tren OEE</h3>
              <p>Naik atau turun dibanding target 85%</p>
            </div>
            <div class="dv-toggles">
              <button type="button" :class="{ on: trendShow.availability }" @click="toggleTrend('availability')">Availability</button>
              <button type="button" :class="{ on: trendShow.performance }" @click="toggleTrend('performance')">Performance</button>
            </div>
          </div>
          <div class="dv-canvas">
            <canvas id="chartTrendOeeRef"></canvas>
            <div v-if="!(data.trend_timeline || []).length" class="dv-empty">Belum ada data pada periode ini</div>
          </div>
        </div>

        <div class="dv-card">
          <div class="dv-card-head">
            <div>
              <h3>Penyebab downtime terbesar</h3>
              <p>Klik batang untuk melihat rincian kendala</p>
            </div>
            <span class="dv-pill tone-neutral">{{ fmt(totalDowntimeAll) }} menit</span>
          </div>
          <div class="dv-canvas">
            <canvas id="chartParetoDowntimeRef"></canvas>
            <div v-if="!(data.pareto_downtime || []).length" class="dv-empty">Tidak ada downtime tercatat</div>
          </div>
          <div v-if="(data.pareto_downtime || []).length > TOP_N" class="dv-card-foot">
            Menampilkan {{ TOP_N }} teratas dari {{ data.pareto_downtime.length }} kategori &middot;
            mencakup {{ topCoverage(data.pareto_downtime, 'total_menit') }}% total
          </div>
        </div>
      </section>

      <!-- Baris Grafik 2: Pareto Cacat & 8 Waste -->
      <section class="dv-charts">
        <div class="dv-card">
          <div class="dv-card-head">
            <div>
              <h3>Jenis cacat terbanyak (Reject WIP)</h3>
              <p>Cacat bahan cetakan yang masuk ke sortir</p>
            </div>
            <span class="dv-pill tone-neutral">{{ fmt(totalCacatAll) }} pcs</span>
          </div>
          <div class="dv-canvas">
            <canvas id="chartParetoCacatRef"></canvas>
            <div v-if="!(data.pareto_cacat || []).length" class="dv-empty">Tidak ada cacat tercatat</div>
          </div>
          <div v-if="(data.pareto_cacat || []).length > TOP_N" class="dv-card-foot">
            Menampilkan {{ TOP_N }} teratas dari {{ data.pareto_cacat.length }} jenis &middot;
            mencakup {{ topCoverage(data.pareto_cacat, 'total_qty') }}% total
          </div>
        </div>

        <div class="dv-card">
          <div class="dv-card-head">
            <div>
              <h3>Waktu terbuang per jenis waste</h3>
              <p>Berdasarkan 8 Waste (Lean)</p>
            </div>
          </div>
          <div v-if="wasteTop.length" class="dv-waste">
            <div v-for="w in wasteTop" :key="w.jenis_waste" class="dv-waste-row">
              <div class="dv-waste-name">{{ w.jenis_waste }}</div>
              <div class="dv-waste-bar">
                <div class="dv-track"><div class="dv-fill bg-brand" :style="{ width: w.persentase + '%' }"></div></div>
              </div>
              <div class="dv-waste-num"><b>{{ fmt(w.total_menit) }}</b> mnt <span class="dv-muted">{{ w.persentase.toFixed(0) }}%</span></div>
            </div>
          </div>
          <div v-else class="dv-empty static">Tidak ada data waste tercatat</div>
        </div>
      </section>

      <!-- Baris 3: Insight & Rekomendasi -->
      <section class="dv-card dv-insight">
        <div class="dv-card-head">
          <div>
            <h3>Yang perlu ditindaklanjuti</h3>
            <p>Dihitung otomatis dari data pada filter terpilih</p>
          </div>
        </div>
        <div v-if="!insights.length" class="dv-empty static">Belum cukup data untuk menghasilkan analisis.</div>
        <div v-else class="dv-insight-list">
          <div v-for="(it, i) in insights" :key="i" class="dv-insight-item" :class="'tone-' + it.tone">
            <span class="dv-insight-no">{{ i + 1 }}</span>
            <div>
              <b>{{ it.title }}</b>
              <p>{{ it.text }}</p>
            </div>
          </div>
        </div>
      </section>

    </div>

    <!-- ============ LEMBAR CETAK DASHBOARD (KHUSUS CETAK 1 HALAMAN A4 LANDSCAPE) ============ -->
    <div class="print-only dv-print-sheet">

      <!-- 1. Header Cetak -->
      <div class="dv-p-head">
        <div class="dv-p-brand">DS</div>
        <div class="dv-p-title-wrap">
          <h2>Divisi Sortir &mdash; Laporan Kinerja &amp; OEE</h2>
          <div class="dv-p-meta">
            <span>Periode: <b>{{ filter.start_date || 'Awal' }} s/d {{ filter.end_date || 'Akhir' }}</b></span>
            <span v-if="filter.shift">&middot; Shift: <b>{{ filter.shift }}</b></span>
            <span v-if="filter.tahap_sortir">&middot; Tahap: <b>{{ filter.tahap_sortir }}</b></span>
            <span v-if="filter.no_pp">&middot; PP: <b>{{ filter.no_pp }}</b></span>
            <span v-if="filter.produk_ids.length">&middot; Produk: <b>{{ filter.produk_ids.length }} item</b></span>
          </div>
        </div>
        <div class="dv-p-user">
          <div>Dicetak oleh: <b>{{ currentUsername }}</b></div>
          <div>{{ printDateTime }}</div>
        </div>
      </div>

      <!-- 2. Ringkasan Eksekutif KPI (Strip Horizontal) -->
      <div class="dv-p-kpi-strip">
        <div class="dv-p-kpi-item highlight">
          <span class="lbl">OEE KESELURUHAN</span>
          <span class="val" :class="'txt-' + oeeTone">{{ oeeVal.toFixed(1) }}%</span>
          <span class="sub">{{ kpiStatusText }} &middot; Tgt 85%</span>
        </div>
        <div class="dv-p-kpi-item">
          <span class="lbl">AVAILABILITY</span>
          <span class="val" :class="'txt-' + rateTone(data.kpi?.availability, 90)">{{ (Number(data.kpi?.availability) || 0).toFixed(1) }}%</span>
          <span class="sub">Target 90%</span>
        </div>
        <div class="dv-p-kpi-item">
          <span class="lbl">PERFORMANCE</span>
          <span class="val" :class="'txt-' + rateTone(data.kpi?.performance, 95)">{{ (Number(data.kpi?.performance) || 0).toFixed(1) }}%</span>
          <span class="sub">Target 95%</span>
        </div>
        <div class="dv-p-kpi-item">
          <span class="lbl">QUALITY OEE</span>
          <span class="val" :class="'txt-' + rateTone(data.kpi?.quality_oee, 99)">{{ (Number(data.kpi?.quality_oee) || 0).toFixed(1) }}%</span>
          <span class="sub">Target 99%</span>
        </div>
        <div class="dv-p-kpi-item">
          <span class="lbl">TOTAL PROSES</span>
          <span class="val">{{ fmt(data.kpi?.total_proses) }}</span>
          <span class="sub">lembar / pcs</span>
        </div>
        <div class="dv-p-kpi-item">
          <span class="lbl">PRODUK BAIK</span>
          <span class="val txt-ok">{{ fmt(data.kpi?.good) }}</span>
          <span class="sub">lembar / pcs</span>
        </div>
        <div class="dv-p-kpi-item">
          <span class="lbl">REJECT TOTAL</span>
          <span class="val txt-bad">{{ (Number(data.kpi?.ng_percent) || 0).toFixed(2) }}%</span>
          <span class="sub">{{ fmt(totalRejectPcs) }} pcs</span>
        </div>
        <div class="dv-p-kpi-item">
          <span class="lbl">KUALITAS WIP</span>
          <span class="val" :class="'txt-' + rateTone(data.kpi?.quality_wip, 95)">{{ (Number(data.kpi?.quality_wip) || 0).toFixed(1) }}%</span>
          <span class="sub">Target 95%</span>
        </div>
      </div>

      <!-- 3. Grid Utama 2 Kolom Seimbang -->
      <div class="dv-p-main-grid">

        <!-- ===== KOLOM KIRI ===== -->
        <div class="dv-p-col">
          <!-- Card 1: Tren OEE -->
          <div class="dv-p-card dv-p-chart-card">
            <div class="dv-p-card-head">
              <b>1. Tren OEE</b>
              <span class="dv-p-badge">Target 85% &middot; Realisasi {{ oeeVal.toFixed(1) }}%</span>
            </div>
            <div class="dv-p-chart-body">
              <img v-if="chartOeeImg" :src="chartOeeImg" class="dv-p-chart-img" alt="Tren OEE" />
              <div v-else class="dv-p-no-chart">Memuat grafik...</div>
            </div>
          </div>

          <!-- Card 2: Pareto Cacat -->
          <div class="dv-p-card dv-p-chart-card">
            <div class="dv-p-card-head">
              <b>2. Jenis Cacat Terbanyak (Reject WIP)</b>
              <span class="dv-p-badge">Total {{ fmt(totalCacatAll) }} pcs</span>
            </div>
            <div class="dv-p-chart-body">
              <img v-if="chartCcImg" :src="chartCcImg" class="dv-p-chart-img" alt="Pareto Cacat" />
              <div v-else class="dv-p-no-chart">Tidak ada cacat tercatat</div>
            </div>
          </div>

          <!-- Card 3: Rekomendasi & Analisis -->
          <div class="dv-p-card dv-p-insight-card">
            <div class="dv-p-card-head">
              <b>3. Analisis &amp; Rekomendasi Tindak Lanjut</b>
              <span class="dv-p-badge">Dihitung Otomatis</span>
            </div>
            <div class="dv-p-insight-list">
              <div v-for="(it, i) in insights" :key="i" class="dv-p-insight-item" :class="'tone-' + it.tone">
                <span class="no">{{ i + 1 }}</span>
                <div class="body">
                  <b>{{ it.title }}</b>
                  <p>{{ it.text }}</p>
                </div>
              </div>
              <div v-if="!insights.length" class="dv-muted text-center" style="padding: 8px 0;">Belum cukup data analisis</div>
            </div>
          </div>
        </div>

        <!-- ===== KOLOM KANAN ===== -->
        <div class="dv-p-col">
          <!-- Card 1: Pareto Downtime -->
          <div class="dv-p-card dv-p-chart-card">
            <div class="dv-p-card-head">
              <b>4. Pareto Downtime Terbesar</b>
              <span class="dv-p-badge">Total {{ fmt(totalDowntimeAll) }} menit</span>
            </div>
            <div class="dv-p-chart-body">
              <img v-if="chartDtImg" :src="chartDtImg" class="dv-p-chart-img" alt="Pareto Downtime" />
              <div v-else class="dv-p-no-chart">Tidak ada downtime tercatat</div>
            </div>
          </div>

          <!-- Card 2: Rincian Alasan Downtime -->
          <div class="dv-p-card dv-p-reasons-card">
            <div class="dv-p-card-head">
              <b>5. Rincian Alasan Downtime (Top {{ TOP_N }})</b>
              <span class="dv-p-badge">Durasi &amp; Uraian Kendala</span>
            </div>
            <table class="dv-p-table">
              <thead>
                <tr>
                  <th width="125">Kategori</th>
                  <th width="70" class="text-right">Durasi</th>
                  <th width="35" class="text-right">%</th>
                  <th>Alasan / Kendala Terbanyak</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="dt in topDowntimeWithReasons" :key="dt.id">
                  <td><b>[{{ dt.kode }}]</b> {{ dt.arti }}</td>
                  <td class="text-right"><b>{{ dt.total_menit }}</b>m <small>({{ dt.frekuensi }}x)</small></td>
                  <td class="text-right">{{ dt.persentase }}%</td>
                  <td>
                    <div class="dv-p-chips">
                      <span v-for="(k, kidx) in (dt.top_keterangan || []).slice(0, 3)" :key="kidx" class="dv-p-chip">
                        {{ k.keterangan }} <small>({{ k.total_menit }}m &middot; {{ k.frekuensi }}x)</small>
                      </span>
                      <span v-if="!dt.top_keterangan || !dt.top_keterangan.length" class="dv-muted">-</span>
                    </div>
                  </td>
                </tr>
                <tr v-if="!topDowntimeWithReasons.length">
                  <td colspan="4" class="text-center dv-muted" style="padding: 8px 0;">Tidak ada data downtime</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Card 3: 8 Waste -->
          <div class="dv-p-card dv-p-waste-card">
            <div class="dv-p-card-head">
              <b>6. Waktu Terbuang (8 Waste Lean)</b>
              <span class="dv-p-badge">Distribusi Waste</span>
            </div>
            <div class="dv-p-waste-list">
              <div v-for="w in wasteTop" :key="w.jenis_waste" class="dv-p-waste-row">
                <span class="w-name">{{ w.jenis_waste }}</span>
                <div class="w-bar-wrap"><div class="w-bar" :style="{ width: w.persentase + '%' }"></div></div>
                <span class="w-num"><b>{{ fmt(w.total_menit) }}</b>m <small>{{ w.persentase.toFixed(0) }}%</small></span>
              </div>
              <div v-if="!wasteTop.length" class="dv-muted text-center" style="padding: 8px 0;">Tidak ada waste tercatat</div>
            </div>
          </div>
        </div>

      </div>

      <!-- 4. Footer Cetak -->
      <div class="dv-p-foot">
        <div>Divisi Sortir &mdash; Sistem LKH &amp; OEE (Dokumen Resmi Operasional)</div>
        <div>Dicetak: {{ printDateTime }} &middot; Halaman 1 dari 1</div>
      </div>

    </div>

    <!-- Hidden canvases for high-res print charts -->
    <div class="no-print" style="position: fixed; left: -9999px; top: 0; width: 1000px; height: 360px; pointer-events: none; opacity: 0; overflow: hidden;" aria-hidden="true">
      <canvas id="printCanvasOee" width="1000" height="360"></canvas>
      <canvas id="printCanvasDt" width="1000" height="360"></canvas>
      <canvas id="printCanvasCc" width="1000" height="360"></canvas>
    </div>

    <!-- ============ MODAL DRILL-DOWN DOWNTIME ============ -->
    <div v-if="showDrilldownModal" class="modal-overlay" :class="{ 'bottom-sheet-active': isMobileScreen }" @click.self="showDrilldownModal = false">
      <div class="modal-content dv-modal-wide">
        <div class="dv-modal-head">
          <div>
            <h3>[{{ selectedDrilldownItem?.kode }}] {{ selectedDrilldownItem?.arti }}</h3>
            <div class="dv-muted">
              Total <b>{{ selectedDrilldownItem?.total_menit }} menit</b> &middot; {{ selectedDrilldownList.length }} uraian kejadian
            </div>
          </div>
          <button type="button" class="dv-btn dv-btn-ghost" @click="showDrilldownModal = false">✕</button>
        </div>
        <input type="text" v-model="searchDrilldownQuery" class="dv-input dv-w100" placeholder="Cari uraian kendala..." />
        <div class="dv-drill-table">
          <table>
            <thead>
              <tr>
                <th width="40">No</th>
                <th>Uraian kendala</th>
                <th width="90" class="text-right">Frekuensi</th>
                <th width="110" class="text-right">Durasi</th>
                <th width="90" class="text-right">Porsi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(k, idx) in filteredDrilldownList" :key="idx">
                <td>{{ idx + 1 }}</td>
                <td><b>{{ k.keterangan }}</b></td>
                <td class="text-right">{{ k.frekuensi }}x</td>
                <td class="text-right"><b>{{ k.total_menit }}</b> mnt</td>
                <td class="text-right">{{ Number(k.persentase).toFixed(1) }}%</td>
              </tr>
              <tr v-if="!filteredDrilldownList.length">
                <td colspan="5" class="text-center dv-muted" style="padding: 1.5rem;">Tidak ada catatan yang cocok.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============ MODAL FILTER MOBILE ============ -->
    <div v-if="showMobileFilterSheet" class="modal-overlay bottom-sheet-active" @click.self="showMobileFilterSheet = false">
      <div class="modal-content dv-sheet">
        <div class="dv-modal-head">
          <h3>Filter dashboard</h3>
          <button type="button" class="dv-btn dv-btn-ghost" @click="showMobileFilterSheet = false">✕</button>
        </div>
        <div class="dv-sheet-body">
          <div class="dv-field"><label>Tanggal mulai</label><input type="date" v-model="filter.start_date" class="dv-input" /></div>
          <div class="dv-field"><label>Tanggal selesai</label><input type="date" v-model="filter.end_date" class="dv-input" /></div>
          <div class="dv-field">
            <label>Shift</label>
            <select v-model="filter.shift" class="dv-input">
              <option value="">Semua shift</option>
              <option value="1">Shift 1</option>
              <option value="2">Shift 2</option>
              <option value="3">Shift 3</option>
            </select>
          </div>
          <div class="dv-field">
            <label>Tahap sortir</label>
            <select v-model="filter.tahap_sortir" class="dv-input">
              <option value="">Semua tahap</option>
              <option value="S1">S1 (Normal)</option>
              <option value="S2">S2 (Sortir ulang 2)</option>
              <option value="S3">S3 (Sortir ulang 3)</option>
            </select>
          </div>
          <div class="dv-field"><label>No PP</label><input type="text" v-model="filter.no_pp" class="dv-input" placeholder="Ketik No PP..." /></div>
        </div>
        <div class="dv-sheet-actions">
          <button type="button" class="dv-btn dv-btn-ghost dv-grow" @click="resetFilter(); showMobileFilterSheet = false">Reset</button>
          <button type="button" class="dv-btn dv-btn-primary dv-grow" @click="loadDashboard(); showMobileFilterSheet = false">Terapkan</button>
        </div>
      </div>
    </div>

    <!-- ============ MODAL EXPORT EXCEL ============ -->
    <div v-if="showModalExportExcel" class="modal-overlay" @click.self="showModalExportExcel = false">
      <div class="modal-content dv-modal-sm">
        <div class="dv-modal-head">
          <h3>Export laporan Excel (LKH)</h3>
          <button type="button" class="dv-btn dv-btn-ghost" @click="showModalExportExcel = false">✕</button>
        </div>
        <p class="dv-muted dv-modal-desc">Laporan kerja harian sortir lengkap dengan downtime, detail cacat WIP, dan KPI OEE.</p>
        <form @submit.prevent="submitExportExcel('xlsx')">
          <div class="dv-form-grid">
            <div class="dv-field"><label>Tanggal mulai *</label><input type="date" v-model="formExport.start_date" class="dv-input" required /></div>
            <div class="dv-field"><label>Tanggal selesai *</label><input type="date" v-model="formExport.end_date" class="dv-input" required /></div>
            <div class="dv-field">
              <label>Shift</label>
              <select v-model="formExport.shift" class="dv-input">
                <option value="">Semua shift</option>
                <option value="1">Shift 1</option>
                <option value="2">Shift 2</option>
                <option value="3">Shift 3</option>
              </select>
            </div>
            <div class="dv-field">
              <label>Tahap sortir</label>
              <select v-model="formExport.tahap_sortir" class="dv-input">
                <option value="">Semua tahap</option>
                <option value="S1">S1 (Normal)</option>
                <option value="S2">S2 (Sortir ulang 2)</option>
                <option value="S3">S3 (Sortir ulang 3)</option>
              </select>
            </div>
          </div>
          <div class="dv-modal-actions">
            <button type="button" class="dv-btn dv-btn-ghost" @click="showModalExportExcel = false">Batal</button>
            <div class="dv-row-gap">
              <button type="button" class="dv-btn dv-btn-ghost" @click="submitExportExcel('csv')">CSV</button>
              <button type="submit" class="dv-btn dv-btn-primary">Download Excel</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- ============ MODAL EXPORT CC ============ -->
    <div v-if="showModalCC" class="modal-overlay" @click.self="showModalCC = false">
      <div class="modal-content dv-modal-sm">
        <div class="dv-modal-head">
          <h3>Export Cost Control (CC)</h3>
          <button type="button" class="dv-btn dv-btn-ghost" @click="showModalCC = false">✕</button>
        </div>
        <p class="dv-muted dv-modal-desc">Rekap per Job Order untuk verifikasi Cost Control (jam kerja, downtime termasuk 5S, jam jalan).</p>
        <form @submit.prevent="submitExportCC('xlsx')">
          <div class="dv-form-grid">
            <div class="dv-field"><label>Tanggal *</label><input type="date" v-model="formCC.tanggal" class="dv-input" required /></div>
            <div class="dv-field">
              <label>Shift *</label>
              <select v-model="formCC.shift" class="dv-input" required>
                <option value="1">Shift 1</option>
                <option value="2">Shift 2</option>
                <option value="3">Shift 3</option>
              </select>
            </div>
          </div>
          <div class="dv-modal-actions">
            <button type="button" class="dv-btn dv-btn-ghost" @click="showModalCC = false">Batal</button>
            <div class="dv-row-gap">
              <button type="button" class="dv-btn dv-btn-ghost" @click="submitExportCC('print')">Cetak / PDF</button>
              <button type="submit" class="dv-btn dv-btn-primary">Download Excel</button>
            </div>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed, nextTick } from 'vue'
import Chart from 'chart.js/auto'
import { apiFetch, authState } from '../../utils/api'

const props = defineProps({
  roleTitle: { type: String, default: 'Admin' }
})

const TOP_N = 6
const OEE_TARGET = 85

const canPrint = computed(() => {
  const r = (authState.value?.role || props.roleTitle || '').toLowerCase()
  return r !== 'operator'
})

const currentUsername = computed(() => authState.value?.username || 'Pengguna')
const printDateTime = ref('')

const isMobileScreen = ref(false)
const checkMobileScreen = () => { isMobileScreen.value = window.innerWidth <= 768 }

/* ---------- Tanggal (zona waktu lokal, bukan UTC) ---------- */
const pad = (n) => String(n).padStart(2, '0')
const toLocalDate = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
const getLocalDate = () => toLocalDate(new Date())
const fmt = (n) => (Number(n) || 0).toLocaleString('id-ID')

/* ---------- State ---------- */
const filter = ref({ start_date: '', end_date: '', shift: '', tahap_sortir: '', no_pp: '', produk_ids: [] })
const activePreset = ref(7)
const showAdvanced = ref(false)

const data = ref({
  kpi: {
    total_proses: 0, good: 0, reject: 0, setup_reject: 0, ng_percent: 0,
    oee: 0, availability: 0, performance: 0, quality_oee: 0, quality_wip: 0
  },
  pareto_downtime: [],
  downtime_drilldown: {},
  pareto_cacat: [],
  trend_timeline: [],
  waste_summary: []
})
const master = ref({ pp: [], mesin: [], produk: [] })

const trendShow = reactive({ availability: false, performance: false })

let chartTrendOee = null
let chartParetoDt = null
let chartParetoCc = null

let printChartOeeInstance = null
let printChartDtInstance = null
let printChartCcInstance = null

const chartOeeImg = ref('')
const chartDtImg = ref('')
const chartCcImg = ref('')

const captureChartImages = () => {
  try {
    // 1. Tren OEE khusus cetak (1000x360 px)
    const elOee = document.getElementById('printCanvasOee')
    if (elOee) {
      if (printChartOeeInstance) printChartOeeInstance.destroy()
      const tl = data.value.trend_timeline || []
      const labels = tl.map(t => (t.tanggal || '').substring(5))
      const datasets = [
        {
          label: 'OEE',
          data: tl.map(t => t.oee),
          borderColor: '#4f46e5',
          backgroundColor: 'rgba(79, 70, 229, 0.12)',
          fill: true,
          tension: 0.25,
          borderWidth: 2.5,
          pointRadius: tl.length > 20 ? 0 : 3,
          pointBackgroundColor: '#4f46e5'
        },
        {
          label: 'Target 85%',
          data: tl.map(() => OEE_TARGET),
          borderColor: '#e11d48',
          borderWidth: 1.5,
          borderDash: [6, 4],
          pointRadius: 0,
          fill: false
        },
        {
          label: 'Availability',
          data: tl.map(t => t.availability),
          borderColor: '#10b981',
          borderWidth: 1.5,
          pointRadius: 0,
          tension: 0.25,
          fill: false
        },
        {
          label: 'Performance',
          data: tl.map(t => t.performance),
          borderColor: '#d97706',
          borderWidth: 1.5,
          pointRadius: 0,
          tension: 0.25,
          fill: false
        }
      ]
      printChartOeeInstance = new Chart(elOee, {
        type: 'line',
        data: { labels, datasets },
        options: {
          responsive: false,
          animation: false,
          layout: { padding: { top: 6, bottom: 4, left: 6, right: 12 } },
          plugins: {
            legend: {
              position: 'top',
              align: 'end',
              labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, font: { size: 10, weight: 'bold' }, color: '#334155', padding: 8 }
            },
            tooltip: { enabled: false }
          },
          scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10, weight: '600' }, color: '#475569', maxTicksLimit: 12 } },
            y: {
              beginAtZero: true, suggestedMax: 100,
              grid: { color: '#f1f5f9' },
              border: { display: false },
              ticks: { callback: v => v + '%', font: { size: 10, weight: '600' }, color: '#475569', stepSize: 25 }
            }
          }
        }
      })
      chartOeeImg.value = elOee.toDataURL('image/png', 1)
    }

    // 2. Pareto Downtime khusus cetak (1000x360 px)
    const elDt = document.getElementById('printCanvasDt')
    if (elDt) {
      if (printChartDtInstance) printChartDtInstance.destroy()
      const items = (data.value.pareto_downtime || []).slice(0, TOP_N)
      const labels = items.map(d => `${d.kode} · ${d.arti}`)
      const vals = items.map(d => Number(d.total_menit) || 0)
      const texts = items.map(d => `${d.total_menit} mnt · ${d.persentase}% (${d.frekuensi}x)`)

      printChartDtInstance = new Chart(elDt, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            data: vals,
            backgroundColor: items.map((_, i) => (i === 0 ? '#e11d48' : '#64748b')),
            borderRadius: 4,
            barThickness: 22
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: false,
          animation: false,
          layout: { padding: { top: 4, bottom: 4, left: 6, right: 14 } },
          plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
          },
          scales: {
            x: { display: false, beginAtZero: true, grace: '38%' },
            y: {
              grid: { display: false },
              border: { display: false },
              ticks: {
                font: { size: 11, weight: 'bold' },
                color: '#1e293b',
                callback(v) { return truncate(this.getLabelForValue(v), 34) }
              }
            }
          }
        },
        plugins: [valueLabelPlugin(texts)]
      })
      chartDtImg.value = elDt.toDataURL('image/png', 1)
    }

    // 3. Pareto Cacat khusus cetak (1000x360 px)
    const elCc = document.getElementById('printCanvasCc')
    if (elCc) {
      if (printChartCcInstance) printChartCcInstance.destroy()
      const items = (data.value.pareto_cacat || []).slice(0, TOP_N)
      const labels = items.map(c => c.nama_cacat || c.nama)
      const vals = items.map(c => Number(c.total_qty) || 0)
      const texts = items.map(c => `${fmt(c.total_qty)} pcs · ${c.persentase}%`)

      printChartCcInstance = new Chart(elCc, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            data: vals,
            backgroundColor: items.map((_, i) => (i === 0 ? '#e11d48' : '#64748b')),
            borderRadius: 4,
            barThickness: 22
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: false,
          animation: false,
          layout: { padding: { top: 4, bottom: 4, left: 6, right: 14 } },
          plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
          },
          scales: {
            x: { display: false, beginAtZero: true, grace: '35%' },
            y: {
              grid: { display: false },
              border: { display: false },
              ticks: {
                font: { size: 11, weight: 'bold' },
                color: '#1e293b',
                callback(v) { return truncate(this.getLabelForValue(v), 34) }
              }
            }
          }
        },
        plugins: [valueLabelPlugin(texts)]
      })
      chartCcImg.value = elCc.toDataURL('image/png', 1)
    }
  } catch (e) {
    console.warn('Could not capture print chart images', e)
  }
}

const showProdukDropdown = ref(false)
const searchProduk = ref('')
const produkDropdownRef = ref(null)
const showExportDropdown = ref(false)
const exportDropdownRef = ref(null)
const showMobileFilterSheet = ref(false)
const showModalExportExcel = ref(false)
const showModalCC = ref(false)
const showDrilldownModal = ref(false)
const selectedDrilldownItem = ref(null)
const searchDrilldownQuery = ref('')

const formExport = ref({ start_date: '', end_date: '', shift: '', tahap_sortir: '', produk_ids: [] })
const formCC = ref({ tanggal: getLocalDate(), shift: '1' })

/* ---------- Event global ---------- */
const handleOutsideClick = (e) => {
  if (produkDropdownRef.value && !produkDropdownRef.value.contains(e.target)) showProdukDropdown.value = false
  if (exportDropdownRef.value && !exportDropdownRef.value.contains(e.target)) showExportDropdown.value = false
}
const handleKeyDown = (e) => {
  if (e.key === 'Escape') {
    showExportDropdown.value = false
    showProdukDropdown.value = false
    showDrilldownModal.value = false
    showMobileFilterSheet.value = false
    showModalExportExcel.value = false
    showModalCC.value = false
  }
}

/* ---------- Filter produk ---------- */
const filteredProdukList = computed(() => {
  const list = master.value.produk || []
  if (!searchProduk.value) return list
  const q = searchProduk.value.toLowerCase()
  return list.filter(p => (p.nama && p.nama.toLowerCase().includes(q)) || (p.famili && p.famili.toLowerCase().includes(q)))
})

const getProdukFilterLabel = computed(() => {
  const sel = filter.value.produk_ids || []
  if (!sel.length) return 'Semua produk'
  if (sel.length === 1) {
    const p = (master.value.produk || []).find(x => Number(x.id) === Number(sel[0]))
    return p ? p.nama : '1 produk'
  }
  return `${sel.length} produk`
})

const getProdukName = (id) => {
  const p = (master.value.produk || []).find(x => Number(x.id) === Number(id))
  return p ? p.nama : `ID ${id}`
}

const toggleProduk = (id) => {
  const n = Number(id)
  const idx = filter.value.produk_ids.indexOf(n)
  if (idx > -1) filter.value.produk_ids.splice(idx, 1)
  else filter.value.produk_ids.push(n)
}
const selectAllProduk = () => { filter.value.produk_ids = (master.value.produk || []).map(p => Number(p.id)) }
const clearAllProduk = () => { filter.value.produk_ids = [] }
const removeProduk = (id) => { filter.value.produk_ids = filter.value.produk_ids.filter(x => x !== Number(id)) }

const activeFilterCount = computed(() => {
  let c = 0
  if (filter.value.shift) c++
  if (filter.value.tahap_sortir) c++
  if (filter.value.no_pp) c++
  c += (filter.value.produk_ids || []).length
  return c
})
const advancedCount = computed(() => {
  let c = 0
  if (filter.value.tahap_sortir) c++
  if (filter.value.no_pp) c++
  c += (filter.value.produk_ids || []).length ? 1 : 0
  return c
})

/* ---------- Preset periode ---------- */
const applyPreset = (p) => {
  const end = new Date()
  const start = new Date()
  if (p === 'month') start.setDate(1)
  else start.setDate(start.getDate() - p)
  filter.value.start_date = toLocalDate(start)
  filter.value.end_date = toLocalDate(end)
  activePreset.value = p
  loadDashboard()
}

/* ---------- KPI helper ---------- */
const rateTone = (val, target) => {
  const n = Number(val) || 0
  if (n >= target) return 'ok'
  if (n >= target - 10) return 'warn'
  return 'bad'
}
const oeeVal = computed(() => Number(data.value.kpi?.oee) || 0)
const oeeTone = computed(() => (oeeVal.value >= 85 ? 'ok' : oeeVal.value >= 70 ? 'warn' : 'bad'))
const oeeGap = computed(() => oeeVal.value - OEE_TARGET)
const kpiStatusText = computed(() => (oeeVal.value >= 85 ? 'Optimal' : oeeVal.value >= 70 ? 'Waspada' : 'Kritis'))

const avgJph = computed(() => {
  const vals = (data.value.trend_timeline || []).map(t => Number(t.actual_jph) || 0).filter(v => v > 0)
  return vals.length ? vals.reduce((a, b) => a + b, 0) / vals.length : 0
})
const targetJph = computed(() => {
  const t = (data.value.trend_timeline || []).find(x => Number(x.target_jph) > 0)
  return t ? Number(t.target_jph) : 45000
})

const pillars = computed(() => {
  const k = data.value.kpi || {}
  const a = Number(k.availability) || 0
  const p = Number(k.performance) || 0
  const q = Number(k.quality_oee) || 0
  return [
    { key: 'a', label: 'Availability', value: a, target: 90, tone: rateTone(a, 90), sub: 'Jam jalan vs waktu produksi' },
    {
      key: 'p', label: 'Performance', value: p, target: 95, tone: rateTone(p, 95),
      sub: avgJph.value ? `Rata-rata ${fmt(Math.round(avgJph.value))} dari ${fmt(targetJph.value)} JPH` : 'Kecepatan aktual vs target'
    },
    { key: 'q', label: 'Quality', value: q, target: 99, tone: rateTone(q, 99), sub: 'Bebas rusakan stell' }
  ]
})

const totalRejectPcs = computed(() => (Number(data.value.kpi?.reject) || 0) + (Number(data.value.kpi?.setup_reject) || 0))
const totalDowntimeAll = computed(() => (data.value.pareto_downtime || []).reduce((a, c) => a + (Number(c.total_menit) || 0), 0))
const totalCacatAll = computed(() => (data.value.pareto_cacat || []).reduce((a, c) => a + (Number(c.total_qty) || 0), 0))

const topCoverage = (list, key) => {
  const total = (list || []).reduce((a, c) => a + (Number(c[key]) || 0), 0)
  const top = (list || []).slice(0, TOP_N).reduce((a, c) => a + (Number(c[key]) || 0), 0)
  return total > 0 ? Math.round((top / total) * 100) : 0
}

const topDowntimeWithReasons = computed(() => {
  return (data.value.pareto_downtime || []).slice(0, TOP_N)
})

/* ---------- Waste ---------- */
const wasteTop = computed(() => {
  const list = data.value.waste_summary || []
  const total = list.reduce((a, c) => a + (Number(c.total_menit) || 0), 0)
  return list
    .map(w => {
      const menit = Number(w.total_menit) || 0
      return { jenis_waste: w.jenis_waste || 'Lainnya', total_menit: menit, persentase: total > 0 ? (menit / total) * 100 : 0 }
    })
    .filter(w => w.total_menit > 0)
    .sort((a, b) => b.total_menit - a.total_menit)
    .slice(0, 5)
})

/* ---------- Drill-down ---------- */
const selectedDrilldownList = computed(() => {
  if (!selectedDrilldownItem.value) return []
  return data.value.downtime_drilldown?.[selectedDrilldownItem.value.id] || []
})
const filteredDrilldownList = computed(() => {
  const list = selectedDrilldownList.value
  if (!searchDrilldownQuery.value) return list
  const q = searchDrilldownQuery.value.toLowerCase()
  return list.filter(i => i.keterangan && i.keterangan.toLowerCase().includes(q))
})
const openDrilldown = (item) => {
  selectedDrilldownItem.value = item
  searchDrilldownQuery.value = ''
  showDrilldownModal.value = true
}

/* ---------- Insight otomatis (teks biasa, tanpa v-html) ---------- */
const insights = computed(() => {
  const out = []
  const k = data.value.kpi || {}
  const dt = data.value.pareto_downtime || []
  const cc = data.value.pareto_cacat || []

  // 1. Pilar terlemah (selisih terbesar ke target)
  const gaps = pillars.value
    .map(p => ({ ...p, gap: p.target - p.value }))
    .filter(p => p.gap > 0)
    .sort((a, b) => b.gap - a.gap)
  if (gaps.length) {
    const w = gaps[0]
    const advice = {
      a: 'Fokus pada waktu setup/changeover (SMED) dan perawatan preventif pada kode downtime teratas.',
      p: 'Periksa kalibrasi feeder dan ban berjalan agar tarikan stabil di kecepatan tinggi.',
      q: 'Telusuri sumber rusakan stell dan lakukan pengecekan awal setting saat mulai job.'
    }[w.key]
    out.push({
      tone: w.gap > 10 ? 'bad' : 'warn',
      title: `${w.label} paling jauh dari target (${w.gap.toFixed(1)} poin)`,
      text: `${w.label} ${w.value.toFixed(1)}% vs target ${w.target}%. ${advice}`
    })
  } else if (oeeVal.value > 0) {
    out.push({ tone: 'ok', title: 'Semua pilar OEE memenuhi target', text: 'Pertahankan kondisi ini dan pantau konsistensinya per shift.' })
  }

  // 2. Downtime dominan
  if (dt.length) {
    const t = dt[0]
    const ket = (t.top_keterangan || []).slice(0, 2).map(x => `${x.keterangan} (${x.total_menit} mnt)`).join('; ')
    out.push({
      tone: 'warn',
      title: `Downtime terbesar: ${t.arti}`,
      text: `${t.total_menit} menit (${t.persentase}% dari total downtime).${ket ? ' Kendala utama: ' + ket + '.' : ''}`
    })
  }

  // 3. Cacat dominan
  if (cc.length) {
    const top3 = cc.slice(0, 3)
    const sum = top3.reduce((a, c) => a + (Number(c.total_qty) || 0), 0)
    const pct = totalCacatAll.value > 0 ? Math.round((sum / totalCacatAll.value) * 100) : 0
    const nama = top3.map(c => c.nama_cacat || c.nama).join(', ')
    out.push({
      tone: Number(k.ng_percent) > 2 ? 'bad' : 'warn',
      title: `3 cacat teratas menyumbang ${pct}% reject`,
      text: `${nama}. Reject total ${(Number(k.ng_percent) || 0).toFixed(2)}%. Bahas dengan divisi cetak mulai dari "${top3[0].nama_cacat || top3[0].nama}".`
    })
  }
  return out
})

/* ---------- Export ---------- */
const handleExportAction = (type) => {
  showExportDropdown.value = false
  if (type === 'excel') openModalExportExcel()
  else if (type === 'cc') openModalCC()
  else if (type === 'kiriman') alert('Export Kiriman segera hadir.')
  else if (type === 'stb') alert('Export STB segera hadir.')
}

const openModalExportExcel = () => {
  const today = getLocalDate()
  formExport.value = {
    start_date: filter.value.start_date || today,
    end_date: filter.value.end_date || today,
    shift: filter.value.shift || '',
    tahap_sortir: filter.value.tahap_sortir || '',
    produk_ids: [...(filter.value.produk_ids || [])]
  }
  showModalExportExcel.value = true
}

const submitExportExcel = (format = 'xlsx') => {
  const q = new URLSearchParams()
  const f = formExport.value
  if (f.start_date) q.append('start_date', f.start_date)
  if (f.end_date) q.append('end_date', f.end_date)
  if (f.shift) q.append('shift', f.shift)
  if (f.tahap_sortir) q.append('tahap_sortir', f.tahap_sortir)
  if (f.produk_ids && f.produk_ids.length) q.append('produk_ids', f.produk_ids.join(','))
  if (format === 'csv') q.append('format', 'csv')
  window.open(`/api/export.php?${q.toString()}`, '_blank')
  showModalExportExcel.value = false
}

const openModalCC = () => {
  formCC.value.tanggal = getLocalDate()
  formCC.value.shift = '1'
  showModalCC.value = true
}

const submitExportCC = (format = 'xlsx') => {
  const q = new URLSearchParams()
  if (formCC.value.tanggal) q.append('tanggal', formCC.value.tanggal)
  if (formCC.value.shift) q.append('shift', formCC.value.shift)
  if (filter.value.produk_ids && filter.value.produk_ids.length) q.append('produk_ids', filter.value.produk_ids.join(','))
  if (format === 'xlsx') q.append('format', 'xlsx')
  window.open(`/api/export_cc.php?${q.toString()}`, '_blank')
  showModalCC.value = false
}

const handleBeforePrint = () => {
  printDateTime.value = new Date().toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' })
  captureChartImages()
}

const handleAfterPrint = () => {
  // Screen charts remain intact
}

const printDashboard = async () => {
  printDateTime.value = new Date().toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' })
  captureChartImages()
  await nextTick()
  setTimeout(() => {
    window.print()
  }, 100)
}

const resetFilter = () => {
  filter.value = { start_date: '', end_date: '', shift: '', tahap_sortir: '', no_pp: '', produk_ids: [] }
  applyPreset(7)
}

/* ---------- Load ---------- */
const loadDashboard = async () => {
  const q = new URLSearchParams()
  const f = filter.value
  if (f.start_date) q.append('start_date', f.start_date)
  if (f.end_date) q.append('end_date', f.end_date)
  if (f.shift) q.append('shift', f.shift)
  if (f.tahap_sortir) q.append('tahap_sortir', f.tahap_sortir)
  if (f.no_pp) q.append('no_pp', f.no_pp)
  if (f.produk_ids && f.produk_ids.length) q.append('produk_ids', f.produk_ids.join(','))

  data.value = await apiFetch(`dashboard.php?${q.toString()}`)
  await nextTick()
  renderAllCharts()
}

/* ---------- Chart ---------- */
const COLORS = {
  brand: '#4f46e5',
  brandSoft: 'rgba(79, 70, 229, 0.10)',
  ok: '#10b981',
  warn: '#d97706',
  bad: '#e11d48',
  slate: '#475569',
  grid: '#eef0f8',
  text: '#666c8a'
}

// Menulis angka di ujung batang horizontal
const valueLabelPlugin = (labels) => ({
  id: 'valueLabel',
  afterDatasetsDraw(chart) {
    const { ctx } = chart
    const meta = chart.getDatasetMeta(0)
    ctx.save()
    ctx.font = '600 11px system-ui, sans-serif'
    ctx.fillStyle = '#334155'
    ctx.textBaseline = 'middle'
    meta.data.forEach((bar, i) => {
      if (labels[i]) ctx.fillText(labels[i], bar.x + 6, bar.y)
    })
    ctx.restore()
  }
})

const truncate = (s, n = 26) => (s && s.length > n ? s.slice(0, n - 1) + '…' : s)

const toggleTrend = (key) => {
  trendShow[key] = !trendShow[key]
  renderTrendChart()
}

const renderTrendChart = () => {
  if (chartTrendOee) chartTrendOee.destroy()
  const el = document.getElementById('chartTrendOeeRef')
  if (!el) return
  const tl = data.value.trend_timeline || []
  const labels = tl.map(t => (t.tanggal || '').substring(5))

  const datasets = [
    {
      label: 'OEE',
      data: tl.map(t => t.oee),
      borderColor: COLORS.brand,
      backgroundColor: COLORS.brandSoft,
      fill: true,
      tension: 0.3,
      borderWidth: 2.5,
      pointRadius: tl.length > 20 ? 0 : 3,
      pointBackgroundColor: COLORS.brand
    },
    {
      label: 'Target 85%',
      data: tl.map(() => OEE_TARGET),
      borderColor: COLORS.bad,
      borderWidth: 1.5,
      borderDash: [6, 4],
      pointRadius: 0,
      fill: false
    }
  ]
  if (trendShow.availability) {
    datasets.push({
      label: 'Availability', data: tl.map(t => t.availability),
      borderColor: COLORS.ok, borderWidth: 1.5, pointRadius: 0, tension: 0.3, fill: false
    })
  }
  if (trendShow.performance) {
    datasets.push({
      label: 'Performance', data: tl.map(t => t.performance),
      borderColor: COLORS.warn, borderWidth: 1.5, pointRadius: 0, tension: 0.3, fill: false
    })
  }

  chartTrendOee = new Chart(el, {
    type: 'line',
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, font: { size: 11 }, color: COLORS.text } },
        tooltip: { callbacks: { label: (c) => `${c.dataset.label}: ${Number(c.parsed.y).toFixed(1)}%` } }
      },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 }, color: COLORS.text, maxTicksLimit: 10 } },
        y: {
          beginAtZero: true, suggestedMax: 100,
          grid: { color: COLORS.grid },
          border: { display: false },
          ticks: { callback: v => v + '%', font: { size: 10 }, color: COLORS.text, stepSize: 25 }
        }
      }
    }
  })
}

const renderParetoDowntime = () => {
  if (chartParetoDt) chartParetoDt.destroy()
  const el = document.getElementById('chartParetoDowntimeRef')
  if (!el) return
  const items = (data.value.pareto_downtime || []).slice(0, TOP_N)
  const labels = items.map(d => `${d.kode} · ${d.arti}`)
  const vals = items.map(d => Number(d.total_menit) || 0)
  const texts = items.map(d => `${d.total_menit} mnt · ${d.persentase}%`)

  chartParetoDt = new Chart(el, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        data: vals,
        backgroundColor: items.map((_, i) => (i === 0 ? COLORS.bad : '#94a3b8')),
        borderRadius: 4,
        barThickness: 20
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { right: 8 } },
      onClick: (e, els) => {
        if (els && els.length) {
          const it = items[els[0].index]
          if (it) openDrilldown(it)
        }
      },
      onHover: (e, els) => { e.native.target.style.cursor = els.length ? 'pointer' : 'default' },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            title: (t) => { const it = items[t[0].dataIndex]; return it ? `[${it.kode}] ${it.arti}` : '' },
            afterTitle: (t) => { const it = items[t[0].dataIndex]; return it ? `${it.kategori || '-'} · ${it.jenis_waste || '-'}` : '' },
            label: (c) => { const it = items[c.dataIndex]; return `${it.total_menit} menit · ${it.persentase}% · ${it.frekuensi}x kejadian` },
            afterBody: (t) => {
              const it = items[t[0].dataIndex]
              if (!it || !it.top_keterangan || !it.top_keterangan.length) return []
              return ['', ...it.top_keterangan.slice(0, 3).map((k, i) => `${i + 1}. ${k.keterangan} (${k.total_menit}m)`), '', 'Klik untuk rincian']
            }
          }
        }
      },
      scales: {
        x: { display: false, beginAtZero: true, grace: '35%' },
        y: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 11 }, color: '#334155', callback(v) { return truncate(this.getLabelForValue(v), 30) } } }
      }
    },
    plugins: [valueLabelPlugin(texts)]
  })
}

const renderParetoCacat = () => {
  if (chartParetoCc) chartParetoCc.destroy()
  const el = document.getElementById('chartParetoCacatRef')
  if (!el) return
  const items = (data.value.pareto_cacat || []).slice(0, TOP_N)
  const labels = items.map(c => c.nama_cacat || c.nama)
  const vals = items.map(c => Number(c.total_qty) || 0)
  const texts = items.map(c => `${fmt(c.total_qty)} pcs · ${c.persentase}%`)

  chartParetoCc = new Chart(el, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        data: vals,
        backgroundColor: items.map((_, i) => (i === 0 ? COLORS.bad : '#94a3b8')),
        borderRadius: 4,
        barThickness: 20
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { right: 8 } },
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (c) => `${fmt(items[c.dataIndex].total_qty)} pcs (${items[c.dataIndex].persentase}%)` } }
      },
      scales: {
        x: { display: false, beginAtZero: true, grace: '35%' },
        y: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 11 }, color: '#334155', callback(v) { return truncate(this.getLabelForValue(v), 30) } } }
      }
    },
    plugins: [valueLabelPlugin(texts)]
  })
}

const renderAllCharts = () => {
  renderTrendChart()
  renderParetoDowntime()
  renderParetoCacat()
  setTimeout(captureChartImages, 350)
}

onMounted(async () => {
  checkMobileScreen()
  window.addEventListener('resize', checkMobileScreen)
  window.addEventListener('beforeprint', handleBeforePrint)
  window.addEventListener('afterprint', handleAfterPrint)
  document.addEventListener('click', handleOutsideClick)
  document.addEventListener('keydown', handleKeyDown)

  const end = new Date()
  const start = new Date()
  start.setDate(start.getDate() - 7)
  filter.value.start_date = toLocalDate(start)
  filter.value.end_date = toLocalDate(end)

  try {
    const [ppRes, mesinRes, produkRes] = await Promise.all([
      apiFetch('master.php?action=pp'),
      apiFetch('master.php?action=mesin'),
      apiFetch('master.php?action=produk')
    ])
    master.value.pp = ppRes || []
    master.value.mesin = mesinRes || []
    master.value.produk = produkRes || []
  } catch (e) {
    console.error('Failed to load master data', e)
  }

  await loadDashboard()
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobileScreen)
  window.removeEventListener('beforeprint', handleBeforePrint)
  window.removeEventListener('afterprint', handleAfterPrint)
  document.removeEventListener('click', handleOutsideClick)
  document.removeEventListener('keydown', handleKeyDown)
  if (chartTrendOee) chartTrendOee.destroy()
  if (chartParetoDt) chartParetoDt.destroy()
  if (chartParetoCc) chartParetoCc.destroy()
  if (printChartOeeInstance) printChartOeeInstance.destroy()
  if (printChartDtInstance) printChartDtInstance.destroy()
  if (printChartCcInstance) printChartCcInstance.destroy()
})
</script>

<style scoped>
.dv {
  --c-brand: #4f46e5;
  --c-brand-2: #8b5cf6;
  --c-ok: #10b981;
  --c-warn: #d97706;
  --c-bad: #e11d48;
  --c-ink: #15172b;
  --c-text: #3a3f5c;
  --c-muted: #666c8a;
  --c-line: #e4e5f1;
  --c-soft: #eef0fb;
  color: var(--c-text);
}

.dv-muted { color: var(--c-muted); font-size: 0.78rem; }
.dv-spacer { flex: 1; }
.dv-w100 { width: 100%; }
.dv-grow { flex: 1; }
.dv-ellipsis { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dv-row-gap { display: flex; gap: 0.5rem; }
.mobile-only { display: none; }

.txt-ok { color: var(--c-ok); }
.txt-warn { color: var(--c-warn); }
.txt-bad { color: var(--c-bad); }
.bg-ok { background: var(--c-ok); }
.bg-warn { background: var(--c-warn); }
.bg-bad { background: var(--c-bad); }
.bg-brand { background: var(--c-brand); }

/* ---------- Tombol & input ---------- */
.dv-btn {
  display: inline-flex; align-items: center; gap: 6px;
  height: 34px; padding: 0 0.85rem;
  font-size: 0.82rem; font-weight: 600;
  border-radius: 8px; border: 1px solid transparent;
  cursor: pointer; white-space: nowrap;
  transition: background 0.15s, border-color 0.15s;
}
.dv-btn-primary { background: linear-gradient(135deg, #6366f1, var(--c-brand)); color: #fff; box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3); }
.dv-btn-primary:hover { background: linear-gradient(135deg, #5457ea, #4338ca); }
.dv-btn-dark { background: linear-gradient(135deg, #272a45, var(--c-ink)); color: #fff; }
.dv-btn-dark:hover { background: #0f1022; }
.dv-btn-ghost { background: #fff; color: var(--c-text); border-color: var(--c-line); }
.dv-btn-ghost:hover { background: var(--c-soft); }
.dv-btn-print { background: #0284c7; color: #fff; border: 1px solid #0369a1; box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25); }
.dv-btn-print:hover { background: #0369a1; color: #fff; }
.dv-caret { font-size: 0.6rem; opacity: 0.7; }
.dv-count {
  background: var(--c-brand); color: #fff; border-radius: 99px;
  font-size: 0.68rem; min-width: 18px; height: 18px; padding: 0 5px;
  display: inline-flex; align-items: center; justify-content: center;
}
.dv-btn-primary .dv-count { background: #fff; color: var(--c-brand); }

.dv-input {
  height: 34px; padding: 0 0.6rem; font-size: 0.82rem;
  border: 1px solid var(--c-line); border-radius: 8px; background: #fff; color: var(--c-ink);
  min-width: 0;
}
.dv-input:focus { outline: 2px solid rgba(79, 70, 229, 0.2); border-color: var(--c-brand); }
.dv-link { background: none; border: none; color: var(--c-brand); font-size: 0.76rem; font-weight: 600; cursor: pointer; padding: 0; }

/* ---------- Filter bar ---------- */
.dv-filter { position: sticky; top: 0; z-index: 25; padding: 4px 0 10px; background: var(--bg, #f4f5fb); }
.dv-filter-card {
  background: #fff; border: 1px solid var(--c-line); border-radius: 14px;
  padding: 0.6rem 0.75rem; box-shadow: 0 2px 10px rgba(21, 23, 55, 0.05);
}
.dv-filter-row { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
.dv-inline-fields { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
.dv-sep { color: var(--c-muted); }

.dv-seg { display: inline-flex; background: var(--c-soft); border-radius: 8px; padding: 3px; }
.dv-seg button {
  border: none; background: transparent; padding: 0 0.7rem; height: 28px;
  font-size: 0.78rem; font-weight: 600; color: var(--c-muted); border-radius: 6px; cursor: pointer;
}
.dv-seg button.on { background: #fff; color: var(--c-brand); box-shadow: 0 1px 2px rgba(15, 23, 42, 0.1); }

.dv-advanced {
  display: grid; grid-template-columns: 1fr 1.2fr 1.4fr auto; gap: 0.6rem; align-items: end;
  margin-top: 0.65rem; padding-top: 0.65rem; border-top: 1px dashed var(--c-line);
}
.dv-field { display: flex; flex-direction: column; gap: 3px; position: relative; }
.dv-field label { font-size: 0.72rem; font-weight: 700; color: var(--c-muted); }
.dv-field .dv-input { width: 100%; }
.dv-multi-btn { display: flex; align-items: center; justify-content: space-between; cursor: pointer; line-height: 32px; }

.dv-pop {
  position: absolute; top: 100%; left: 0; right: 0; min-width: 260px; margin-top: 4px; z-index: 50;
  background: #fff; border: 1px solid var(--c-line); border-radius: 10px; padding: 0.6rem;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
}
.dv-pop-actions { display: flex; gap: 0.75rem; align-items: center; margin: 0.4rem 0; }
.dv-pop-actions .dv-muted { margin-left: auto; }
.dv-pop-list { max-height: 200px; overflow-y: auto; }
.dv-pop-item { display: flex; align-items: center; gap: 6px; padding: 5px 4px; border-radius: 6px; font-size: 0.8rem; cursor: pointer; }
.dv-pop-item:hover { background: var(--c-soft); }

.dv-chips { display: flex; gap: 0.4rem; flex-wrap: wrap; margin-top: 0.6rem; }
.dv-chip {
  display: inline-flex; align-items: center; gap: 6px;
  background: #eef2ff; color: #3730a3; border-radius: 99px; padding: 2px 10px;
  font-size: 0.74rem; font-weight: 600;
}
.dv-chip b { cursor: pointer; opacity: 0.6; font-size: 0.7rem; }
.dv-chip b:hover { opacity: 1; color: var(--c-bad); }

/* Export dropdown */
.dv-dropdown { position: relative; }
.dv-menu {
  position: absolute; top: 100%; right: 0; margin-top: 6px; z-index: 50; min-width: 240px;
  background: #fff; border: 1px solid var(--c-line); border-radius: 10px; padding: 5px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
}
.dv-menu-item {
  display: flex; flex-direction: column; align-items: flex-start; gap: 1px; width: 100%;
  padding: 8px 10px; border: none; background: transparent; border-radius: 7px; cursor: pointer; text-align: left;
}
.dv-menu-item:hover:not(.disabled) { background: var(--c-soft); }
.dv-menu-item b { font-size: 0.82rem; color: var(--c-ink); }
.dv-menu-item small { font-size: 0.72rem; color: var(--c-muted); }
.dv-menu-item.disabled { opacity: 0.55; cursor: not-allowed; }
.dv-menu-divider { height: 1px; background: var(--c-line); margin: 4px 0; }

/* ---------- Hero ---------- */
.dv-hero { display: grid; grid-template-columns: 1.1fr 2fr; gap: 0.9rem; margin-bottom: 0.9rem; }
.dv-oee, .dv-pillars, .dv-stats, .dv-card {
  background: #fff; border: 1px solid var(--c-line); border-radius: 14px;
  box-shadow: 0 2px 10px rgba(21, 23, 55, 0.05);
}
.dv-oee {
  padding: 1.1rem 1.25rem; display: flex; flex-direction: column; gap: 0.5rem;
  background: linear-gradient(165deg, #ffffff 0%, #f8f8fe 60%, #f3f2fd 100%);
  position: relative; overflow: hidden;
}
.dv-oee::before {
  content: ''; position: absolute; inset: 0 0 auto 0; height: 3px;
  background: linear-gradient(90deg, var(--c-brand), var(--c-brand-2));
}
.dv-oee-top { display: flex; justify-content: space-between; align-items: center; }
.dv-eyebrow { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--c-muted); }
.dv-oee-value { font-size: 3.2rem; font-weight: 800; line-height: 1; letter-spacing: -0.03em; }
.dv-oee-value small { font-size: 1.4rem; font-weight: 700; margin-left: 2px; }
.dv-oee-foot { display: flex; justify-content: space-between; font-size: 0.78rem; color: var(--c-text); }

.dv-pillars { display: grid; grid-template-columns: repeat(3, 1fr); padding: 0.4rem 0; }
.dv-pillar { padding: 0.8rem 1.1rem; display: flex; flex-direction: column; gap: 0.5rem; justify-content: center; }
.dv-pillar + .dv-pillar { border-left: 1px solid var(--c-line); }
.dv-pillar-head { display: flex; justify-content: space-between; align-items: baseline; }
.dv-pillar-val { font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; }
.dv-pillar-foot { display: flex; justify-content: space-between; gap: 6px; font-size: 0.72rem; color: var(--c-text); }

.dv-pill { font-size: 0.72rem; font-weight: 700; padding: 3px 9px; border-radius: 99px; }
.dv-pill.tone-ok { background: #d1fae5; color: #065f46; }
.dv-pill.tone-warn { background: #fef3c7; color: #92400e; }
.dv-pill.tone-bad { background: #fee2e2; color: #991b1b; }
.dv-pill.tone-neutral { background: var(--c-soft); color: var(--c-text); border: 1px solid var(--c-line); }

.dv-track { position: relative; height: 8px; background: #eef2f7; border-radius: 99px; }
.dv-track-lg { height: 12px; margin-top: 0.4rem; }
.dv-fill { height: 100%; border-radius: 99px; transition: width 0.4s ease; }
.dv-target-mark { position: absolute; top: -3px; bottom: -3px; width: 2px; background: var(--c-ink); border-radius: 2px; }
.dv-target-mark span { position: absolute; top: -17px; left: 50%; transform: translateX(-50%); font-size: 0.65rem; font-weight: 700; color: var(--c-ink); }
.dv-track-lg { margin-top: 1.1rem; }

/* ---------- Stats strip ---------- */
.dv-stats { display: grid; grid-template-columns: repeat(4, 1fr); margin-bottom: 0.9rem; }
.dv-stat { padding: 0.75rem 1.1rem; display: flex; flex-wrap: wrap; align-items: baseline; gap: 0 6px; }
.dv-stat + .dv-stat { border-left: 1px solid var(--c-line); }
.dv-stat .dv-eyebrow { width: 100%; margin-bottom: 2px; }
.dv-stat b { font-size: 1.3rem; font-weight: 800; color: var(--c-ink); letter-spacing: -0.01em; }
.dv-stat small { font-size: 0.76rem; color: var(--c-muted); }

/* ---------- Charts ---------- */
.dv-charts { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.9rem; margin-bottom: 0.9rem; }
.dv-card { padding: 1rem 1.1rem; display: flex; flex-direction: column; }
.dv-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; margin-bottom: 0.75rem; }
.dv-card-head h3 { margin: 0; font-size: 0.98rem; font-weight: 700; color: var(--c-ink); }
.dv-card-head p { margin: 2px 0 0; font-size: 0.75rem; color: var(--c-muted); }
.dv-card-foot { margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--c-line); font-size: 0.72rem; color: var(--c-muted); }
.dv-canvas { position: relative; height: 270px; width: 100%; }
.dv-empty {
  position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
  font-size: 0.82rem; color: var(--c-muted); background: rgba(255, 255, 255, 0.85);
}
.dv-empty.static { position: static; padding: 2rem 0; background: none; }

.dv-toggles { display: flex; gap: 4px; }
.dv-toggles button {
  border: 1px solid var(--c-line); background: #fff; color: var(--c-muted);
  font-size: 0.7rem; font-weight: 600; padding: 3px 9px; border-radius: 99px; cursor: pointer;
}
.dv-toggles button.on { background: #eef2ff; border-color: #c7d2fe; color: var(--c-brand); }

/* Waste */
.dv-waste { display: flex; flex-direction: column; gap: 0.85rem; padding-top: 0.25rem; }
.dv-waste-row { display: grid; grid-template-columns: 1.1fr 1.4fr 1fr; align-items: center; gap: 0.75rem; }
.dv-waste-name { font-size: 0.82rem; font-weight: 600; color: var(--c-ink); }
.dv-waste-num { font-size: 0.78rem; text-align: right; white-space: nowrap; }

/* Insight */
.dv-insight { margin-bottom: 0; }
.dv-insight-list { display: flex; flex-direction: column; gap: 0.6rem; }
.dv-insight-item {
  display: flex; gap: 0.75rem; padding: 0.7rem 0.9rem;
  border-radius: 10px; background: var(--c-soft); border-left: 4px solid var(--c-muted);
}
.dv-insight-item.tone-ok { border-left-color: var(--c-ok); }
.dv-insight-item.tone-warn { border-left-color: var(--c-warn); }
.dv-insight-item.tone-bad { border-left-color: var(--c-bad); }
.dv-insight-no {
  flex: none; width: 22px; height: 22px; border-radius: 50%; background: #fff; border: 1px solid var(--c-line);
  display: inline-flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 700;
}
.dv-insight-item b { font-size: 0.86rem; color: var(--c-ink); }
.dv-insight-item p { margin: 2px 0 0; font-size: 0.8rem; line-height: 1.5; }

/* ---------- Modal ---------- */
.dv-modal-wide { max-width: 700px; width: 95%; }
.dv-modal-sm { max-width: 500px; width: 95%; }
.dv-modal-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.75rem; }
.dv-modal-head h3 { margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--c-ink); }
.dv-modal-desc { margin: -0.25rem 0 1rem; font-size: 0.82rem; }
.dv-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.dv-modal-actions { display: flex; justify-content: space-between; margin-top: 1.1rem; padding-top: 0.75rem; border-top: 1px solid var(--c-line); }
.dv-drill-table { max-height: 380px; overflow-y: auto; margin-top: 0.75rem; }
.dv-drill-table table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.dv-drill-table th { position: sticky; top: 0; background: var(--c-soft); text-align: left; padding: 8px 10px; font-size: 0.72rem; text-transform: uppercase; color: var(--c-muted); }
.dv-drill-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
.dv-sheet-body { display: flex; flex-direction: column; gap: 0.75rem; }
.dv-sheet-actions { display: flex; gap: 0.5rem; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--c-line); }

/* ---------- Responsive ---------- */
@media (max-width: 1100px) {
  .dv-hero { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
  .desktop-only { display: none !important; }
  .mobile-only { display: inline-flex; }
  .dv-pillars { grid-template-columns: 1fr; }
  .dv-pillar + .dv-pillar { border-left: none; border-top: 1px solid var(--c-line); }
  .dv-stats { grid-template-columns: repeat(2, 1fr); }
  .dv-stat:nth-child(3) { border-left: none; }
  .dv-stat:nth-child(n+3) { border-top: 1px solid var(--c-line); }
  .dv-charts { grid-template-columns: 1fr; }
  .dv-canvas { height: 240px; }
  .dv-oee-value { font-size: 2.6rem; }
  .dv-waste-row { grid-template-columns: 1fr 1fr; }
  .dv-waste-bar { grid-column: 1 / -1; order: 3; }
  .dv-form-grid { grid-template-columns: 1fr; }

  .bottom-sheet-active { align-items: flex-end !important; padding: 0 !important; }
  .dv-modal-wide, .dv-sheet {
    max-width: 100% !important; width: 100% !important; max-height: 85vh !important;
    border-radius: 14px 14px 0 0 !important; animation: dvSlideUp 0.25s ease-out;
  }
}

@keyframes dvSlideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }

/* ============ GAYA KHUSUS LEMBAR CETAK (PRINT VIEW) ============ */
.print-only {
  display: none !important;
}

@media print {
  .print-only {
    display: flex !important;
  }

  .dv-print-sheet {
    display: flex !important;
    flex-direction: column !important;
    justify-content: flex-start !important;
    gap: 2mm !important;
    width: 100% !important;
    height: 198mm !important;
    max-height: 198mm !important;
    box-sizing: border-box !important;
    overflow: hidden !important;
    page-break-after: avoid !important;
    break-after: avoid !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
    background: #ffffff !important;
  }

  /* Header cetak */
  .dv-p-head {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 2px 6px !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 4px !important;
    background: #f8fafc !important;
    height: 9.5mm !important;
    box-sizing: border-box !important;
    flex: none !important;
  }
  .dv-p-brand {
    width: 22px !important;
    height: 22px !important;
    background: #4338ca !important;
    color: #fff !important;
    border-radius: 4px !important;
    font-weight: 800 !important;
    font-size: 7.5pt !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex: none !important;
  }
  .dv-p-title-wrap {
    flex: 1 !important;
    display: flex !important;
    align-items: baseline !important;
    gap: 8px !important;
  }
  .dv-p-title-wrap h2 {
    margin: 0 !important;
    font-size: 8.5pt !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    white-space: nowrap !important;
  }
  .dv-p-meta {
    font-size: 6.5pt !important;
    color: #475569 !important;
    white-space: nowrap !important;
  }
  .dv-p-user {
    text-align: right !important;
    font-size: 6pt !important;
    color: #475569 !important;
    white-space: nowrap !important;
    line-height: 1.1 !important;
  }

  /* Strip KPI */
  .dv-p-kpi-strip {
    display: grid !important;
    grid-template-columns: 1.2fr repeat(7, 1fr) !important;
    gap: 3px !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 4px !important;
    background: #ffffff !important;
    padding: 2px 4px !important;
    box-sizing: border-box !important;
    height: 17.5mm !important;
    flex: none !important;
  }
  .dv-p-kpi-item {
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
    padding: 0 4px !important;
  }
  .dv-p-kpi-item + .dv-p-kpi-item {
    border-left: 1px solid #e2e8f0 !important;
  }
  .dv-p-kpi-item .lbl {
    font-size: 5.2pt !important;
    font-weight: 700 !important;
    color: #64748b !important;
    text-transform: uppercase !important;
    letter-spacing: 0.03em !important;
    line-height: 1 !important;
  }
  .dv-p-kpi-item .val {
    font-size: 9pt !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    line-height: 1.15 !important;
  }
  .dv-p-kpi-item .sub {
    font-size: 5.5pt !important;
    color: #64748b !important;
    line-height: 1 !important;
  }
  .dv-p-kpi-item.highlight {
    background: #f1f5f9 !important;
    border-radius: 3px !important;
  }

  /* Grid Utama 2 Kolom */
  .dv-p-main-grid {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 2.5mm !important;
    height: 161mm !important;
    box-sizing: border-box !important;
    flex: none !important;
  }
  .dv-p-col {
    display: flex !important;
    flex-direction: column !important;
    gap: 2mm !important;
    height: 100% !important;
    box-sizing: border-box !important;
  }

  /* Card generik */
  .dv-p-card {
    border: 1px solid #cbd5e1 !important;
    border-radius: 4px !important;
    background: #ffffff !important;
    padding: 2.5px 5px !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    box-sizing: border-box !important;
  }
  .dv-p-card-head {
    display: flex !important;
    justify-content: space-between !important;
    align-items: baseline !important;
    height: 4.5mm !important;
    margin-bottom: 1mm !important;
    flex: none !important;
  }
  .dv-p-card-head b {
    font-size: 6.8pt !important;
    font-weight: 800 !important;
    color: #0f172a !important;
  }
  .dv-p-card-head span,
  .dv-p-card-head .dv-p-badge {
    font-size: 6pt !important;
    color: #64748b !important;
  }

  /* Chart Card */
  .dv-p-chart-card {
    height: 53.5mm !important;
    flex: none !important;
  }
  .dv-p-chart-body {
    flex: 1 !important;
    height: calc(100% - 5.5mm) !important;
    width: 100% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    overflow: hidden !important;
  }
  .dv-p-chart-img {
    width: 100% !important;
    height: 100% !important;
    max-width: 100% !important;
    max-height: 100% !important;
    object-fit: contain !important;
    display: block !important;
  }
  .dv-p-no-chart {
    font-size: 6.5pt !important;
    color: #94a3b8 !important;
  }

  /* Card Khusus Kolom Kiri: Insight */
  .dv-p-insight-card {
    height: 49mm !important;
    flex: none !important;
  }
  .dv-p-insight-list {
    display: flex !important;
    flex-direction: column !important;
    gap: 2mm !important;
    flex: 1 !important;
  }
  .dv-p-insight-item {
    display: flex !important;
    gap: 4px !important;
    padding: 3px 5px !important;
    background: #f8fafc !important;
    border-radius: 3px !important;
    border-left: 3px solid #94a3b8 !important;
    font-size: 6.2pt !important;
  }
  .dv-p-insight-item.tone-ok { border-left-color: #10b981 !important; }
  .dv-p-insight-item.tone-warn { border-left-color: #d97706 !important; }
  .dv-p-insight-item.tone-bad { border-left-color: #e11d48 !important; }
  .dv-p-insight-item .no {
    width: 13px !important;
    height: 13px !important;
    border-radius: 50% !important;
    background: #fff !important;
    border: 1px solid #cbd5e1 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 5.5pt !important;
    font-weight: 800 !important;
    flex: none !important;
  }
  .dv-p-insight-item .body b {
    display: block !important;
    font-size: 6.4pt !important;
    font-weight: 800 !important;
    color: #0f172a !important;
  }
  .dv-p-insight-item .body p {
    margin: 1px 0 0 !important;
    font-size: 6pt !important;
    line-height: 1.2 !important;
    color: #334155 !important;
  }

  /* Card Khusus Kolom Kanan: Rincian Downtime */
  .dv-p-reasons-card {
    height: 57.5mm !important;
    flex: none !important;
  }
  .dv-p-table {
    width: 100% !important;
    border-collapse: collapse !important;
    font-size: 6.2pt !important;
    line-height: 1.15 !important;
  }
  .dv-p-table thead {
    display: table-row-group !important;
  }
  .dv-p-table th {
    background: #f1f5f9 !important;
    text-align: left !important;
    padding: 2px 4px !important;
    font-size: 5.8pt !important;
    text-transform: uppercase !important;
    color: #475569 !important;
    border-bottom: 1px solid #cbd5e1 !important;
  }
  .dv-p-table td {
    padding: 2.5px 4px !important;
    border-bottom: 1px solid #f1f5f9 !important;
    vertical-align: top !important;
  }
  .dv-p-chips {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 2px !important;
  }
  .dv-p-chip {
    display: inline-flex !important;
    align-items: center !important;
    gap: 2px !important;
    background: #f8fafc !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 3px !important;
    padding: 0 3px !important;
    font-size: 5.8pt !important;
    line-height: 1.2 !important;
  }
  .dv-p-chip small {
    color: #64748b !important;
  }

  /* Card Khusus Kolom Kanan: 8 Waste */
  .dv-p-waste-card {
    height: 45mm !important;
    flex: none !important;
  }
  .dv-p-waste-list {
    display: flex !important;
    flex-direction: column !important;
    gap: 2.5px !important;
    padding-top: 1px !important;
    flex: 1 !important;
  }
  .dv-p-waste-row {
    display: grid !important;
    grid-template-columns: 1fr 1.2fr 0.8fr !important;
    align-items: center !important;
    gap: 6px !important;
    font-size: 6.2pt !important;
  }
  .dv-p-waste-row .w-name {
    font-weight: 700 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
  }
  .dv-p-waste-row .w-bar-wrap {
    height: 5.5px !important;
    background: #e2e8f0 !important;
    border-radius: 99px !important;
    overflow: hidden !important;
  }
  .dv-p-waste-row .w-bar {
    height: 100% !important;
    background: #4f46e5 !important;
    border-radius: 99px !important;
  }
  .dv-p-waste-row .w-num {
    text-align: right !important;
    white-space: nowrap !important;
  }

  /* Footer Cetak */
  .dv-p-foot {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding: 1px 6px !important;
    font-size: 5.5pt !important;
    color: #64748b !important;
    border-top: 1px solid #cbd5e1 !important;
    height: 5mm !important;
    box-sizing: border-box !important;
    flex: none !important;
  }

  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
}
</style>

<!-- Global print style overrides -->
<style>
@media print {
  @page {
    size: A4 landscape;
    margin: 4mm 6mm;
  }
  html, body {
    width: 100% !important;
    height: 100% !important;
    max-height: 100vh !important;
    overflow: hidden !important;
    margin: 0 !important;
    padding: 0 !important;
    background: #ffffff !important;
    font-size: 7pt !important;
  }
  .app-header,
  header,
  nav,
  .desktop-navbar,
  .mobile-topbar,
  .mobile-bottom-nav,
  .no-print,
  .dv-filter,
  .dv-screen-view,
  .modal-overlay {
    display: none !important;
    height: 0 !important;
    min-height: 0 !important;
    max-height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
    box-shadow: none !important;
    position: static !important;
    overflow: hidden !important;
  }
  .container {
    max-width: 100% !important;
    width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
  }
}
</style>
