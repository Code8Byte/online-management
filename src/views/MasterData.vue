<template>
  <div>
    <h2 class="mb-1" style="font-weight: 800; letter-spacing: -0.01em; color: var(--text);">⚙️ Master Data</h2>
    
    <!-- 1. Master Job Order (PP) & Produk -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center mb-1 flex-wrap gap-05">
        <div>
          <h3 class="card-title" style="margin:0; border:none; padding:0;">📦 Master Job Order (PP) &amp; Produk</h3>
          <small class="text-muted">Kelola Job Order (PP), nama produk, famili, kuota cetak, dan standar speed (JPH)</small>
        </div>
        <button class="btn btn-success" @click="openAddProdukPP">+ Tambah Job Order &amp; Produk</button>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>No PP</th>
              <th>Nama Produk</th>
              <th>Famili</th>
              <th>Total Dicetak (Pcs)</th>
              <th>Std Speed (JPH)</th>
              <th>Status</th>
              <th width="190" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in master.pp" :key="p.id">
              <td><b>{{ p.no_pp }}</b></td>
              <td><b>{{ p.produk_nama }}</b></td>
              <td>{{ p.famili }}</td>
              <td>
                <span v-if="p.status === 'req' && (!p.plan_total || Number(p.plan_total) === 0)" class="badge badge-warning" style="font-size: 0.72rem;">Belum Ditentukan</span>
                <b v-else>{{ Number(p.plan_total).toLocaleString('id-ID') }} pcs</b>
              </td>
              <td>
                <span v-if="p.status === 'req' && (!p.std_jph || Number(p.std_jph) === 0)" class="badge badge-warning" style="font-size: 0.72rem;">Belum Ditentukan</span>
                <b v-else>{{ p.std_jph }} pcs/jam</b>
              </td>
              <td>
                <span v-if="p.status === 'req'" class="badge badge-warning" style="background: #fef3c7; color: #b45309; font-weight: bold; padding: 3px 8px; border-radius: 4px;">
                  ⏳ Req (Menunggu ACC)
                </span>
                <span v-else-if="p.status === 'aktif'" class="badge text-success">Aktif</span>
                <span v-else class="badge text-danger">Selesai (Tutup)</span>
              </td>
              <td class="text-nowrap text-center">
                <button v-if="p.status === 'req'" class="btn btn-success" style="padding: 0.25rem 0.65rem; margin-right: 4px; font-weight: bold;" @click="openAccModal(p)" title="Berikan Standar JPH & Kuota, lalu ACC">
                  ✓ ACC
                </button>
                <button class="btn btn-info" style="padding: 0.25rem 0.5rem; margin-right: 4px;" @click="editItem('pp', p)" title="Edit PP & Produk">✎ Edit</button>
                <button v-if="p.status === 'aktif'" class="btn btn-warning" style="padding: 0.25rem 0.5rem; margin-right: 4px;" @click="endJobOrder(p.id)" title="Tutup PP">Tutup</button>
                <button v-else-if="p.status === 'selesai'" class="btn btn-success" style="padding: 0.25rem 0.5rem; margin-right: 4px;" @click="activateJobOrder(p.id)" title="Aktifkan Kembali PP">Aktifkan</button>
                <button class="btn btn-danger" style="padding: 0.25rem 0.5rem;" @click="hardDel('pp', p.id)" title="Hapus Job Order">🗑️ Hapus</button>
              </td>
            </tr>
            <tr v-if="master.pp.length === 0">
              <td colspan="7" class="text-center text-muted" style="padding: 1.5rem;">Belum ada Job Order &amp; Produk.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="grid grid-3 gap-1 mb-1">
      
      <!-- 2. Mesin -->
      <div class="card">
        <div class="d-flex justify-between align-center mb-1">
          <h3 class="card-title" style="margin:0; border:none; padding:0;">Mesin</h3>
          <button class="btn btn-success" @click="addMesin" title="Tambah Mesin">+ Tambah Mesin</button>
        </div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Nama Mesin</th>
                <th>Status</th>
                <th width="120" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in master.mesin" :key="m.id">
                <td><b>{{ m.nama }}</b></td>
                <td>
                  <span v-if="m.aktif != 0" class="badge text-success">Aktif</span>
                  <span v-else class="badge text-danger">Nonaktif</span>
                </td>
                <td class="text-nowrap text-center">
                  <button class="btn btn-info" style="padding: 0.25rem 0.5rem; margin-right: 4px;" @click="editItem('mesin', m)" title="Edit">✎ Edit</button>
                  <button v-if="m.aktif != 0" class="btn btn-warning" style="padding: 0.25rem 0.5rem;" @click="del('mesin', m.id)" title="Nonaktifkan">X</button>
                  <template v-else>
                    <button class="btn btn-success" style="padding: 0.25rem 0.4rem; margin-right: 4px;" @click="activateItem('mesin', m)" title="Aktifkan Kembali">♻️</button>
                    <button class="btn btn-danger" style="padding: 0.25rem 0.4rem;" @click="hardDel('mesin', m.id)" title="Hapus Permanen">🗑️</button>
                  </template>
                </td>
              </tr>
              <tr v-if="master.mesin.length === 0">
                <td colspan="3" class="text-center text-muted">Belum ada data mesin.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 3. Kode Downtime (Dengan 8 Waste) -->
      <div class="card">
        <div class="d-flex justify-between align-center mb-1">
          <div>
            <h3 class="card-title" style="margin:0; border:none; padding:0;">Kode Downtime</h3>
            <small class="text-muted">Kategori 8 Jenis Waste</small>
          </div>
          <button class="btn btn-success" @click="openAddDt" title="Tambah Kode DT">+ Tambah Kode DT</button>
        </div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Kode &amp; Arti</th>
                <th>8 Jenis Waste</th>
                <th>Status</th>
                <th width="120" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in master.kode_downtime" :key="d.id">
                <td>
                  <b>[{{ d.kode }}]</b> {{ d.arti }}
                  <div style="font-size: 0.8rem; color: var(--text-muted);">Kategori 5M: <b>{{ d.kategori || '-' }}</b></div>
                </td>
                <td>
                  <span class="badge" style="background: #e0f2fe; color: #0369a1; font-weight: bold;">
                    {{ d.jenis_waste }}
                  </span>
                </td>
                <td>
                  <span v-if="d.aktif != 0" class="badge text-success">Aktif</span>
                  <span v-else class="badge text-danger">Nonaktif</span>
                </td>
                <td class="text-nowrap text-center">
                  <button class="btn btn-info" style="padding: 0.25rem 0.5rem; margin-right: 4px;" @click="editItem('kode_downtime', d)" title="Edit">✎ Edit</button>
                  <button v-if="d.aktif != 0" class="btn btn-warning" style="padding: 0.25rem 0.5rem;" @click="del('kode_downtime', d.id)" title="Nonaktifkan">X</button>
                  <template v-else>
                    <button class="btn btn-success" style="padding: 0.25rem 0.4rem; margin-right: 4px;" @click="activateItem('kode_downtime', d)" title="Aktifkan Kembali">♻️</button>
                    <button class="btn btn-danger" style="padding: 0.25rem 0.4rem;" @click="hardDel('kode_downtime', d.id)" title="Hapus Permanen">🗑️</button>
                  </template>
                </td>
              </tr>
              <tr v-if="master.kode_downtime.length === 0">
                <td colspan="4" class="text-center text-muted">Belum ada data downtime.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 4. Jenis Cacat (Reject) -->
      <div class="card">
        <div class="d-flex justify-between align-center mb-1">
          <h3 class="card-title" style="margin:0; border:none; padding:0;">Jenis Cacat (Reject)</h3>
          <button class="btn btn-success" @click="openAddCacat" title="Tambah Cacat">+ Tambah Cacat</button>
        </div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Nama Cacat</th>
                <th>Urutan</th>
                <th>Status</th>
                <th width="120" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in master.jenis_cacat" :key="c.id">
                <td><b>{{ c.nama }}</b></td>
                <td>{{ c.urutan }}</td>
                <td>
                  <span v-if="c.aktif != 0" class="badge text-success">Aktif</span>
                  <span v-else class="badge text-danger">Nonaktif</span>
                </td>
                <td class="text-nowrap text-center">
                  <button class="btn btn-info" style="padding: 0.25rem 0.5rem; margin-right: 4px;" @click="editItem('jenis_cacat', c)" title="Edit">✎ Edit</button>
                  <button v-if="c.aktif != 0" class="btn btn-warning" style="padding: 0.25rem 0.5rem;" @click="del('jenis_cacat', c.id)" title="Nonaktifkan">X</button>
                  <template v-else>
                    <button class="btn btn-success" style="padding: 0.25rem 0.4rem; margin-right: 4px;" @click="activateItem('jenis_cacat', c)" title="Aktifkan Kembali">♻️</button>
                    <button class="btn btn-danger" style="padding: 0.25rem 0.4rem;" @click="hardDel('jenis_cacat', c.id)" title="Hapus Permanen">🗑️</button>
                  </template>
                </td>
              </tr>
              <tr v-if="master.jenis_cacat.length === 0">
                <td colspan="4" class="text-center text-muted">Belum ada data jenis cacat.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- Modal Tambah Job Order & Produk -->
    <div v-if="showProdukModal" class="modal-overlay" @click.self="showProdukModal = false">
      <div class="modal-content">
        <h3 class="mb-1">Tambah Job Order (PP) &amp; Produk Baru</h3>
        <form @submit.prevent="submitProdukPP">
          <div class="mb-1">
            <label class="form-label">No PP <span class="text-danger">*</span></label>
            <input type="text" v-model="formProduk.no_pp" class="form-control" required placeholder="Contoh: PP/2026/001">
          </div>
          <div class="mb-1">
            <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
            <input type="text" v-model="formProduk.nama" list="existingProdukList" @input="onProdukNamaChange" class="form-control" required placeholder="Contoh: Sampoerna Mild">
            <datalist id="existingProdukList">
              <option v-for="pr in master.produk" :key="pr.id" :value="pr.nama">
                {{ pr.nama }} (Famili: {{ pr.famili }}, Std: {{ pr.std_jph }} JPH)
              </option>
            </datalist>
          </div>
          <div class="grid grid-2 gap-1 mb-1">
            <div>
              <label class="form-label">Famili (Rokok HL, Berocca, dll) <span class="text-danger">*</span></label>
              <input type="text" v-model="formProduk.famili" class="form-control" required placeholder="Contoh: Rokok HL">
            </div>
            <div>
              <label class="form-label">Standar Speed (Std JPH) <span class="text-danger">*</span></label>
              <input type="number" v-model.number="formProduk.std_jph" class="form-control" required min="1" placeholder="Contoh: 1500">
            </div>
          </div>
          <div class="mb-1">
            <label class="form-label">Total Produk Dicetak (Pcs) <span class="text-danger">*</span></label>
            <input type="number" v-model.number="formProduk.plan_total" class="form-control" required min="1" placeholder="Contoh: 100000">
          </div>
          <div class="d-flex gap-1" style="justify-content: flex-end; margin-top: 15px;">
            <button type="button" class="btn btn-secondary" @click="showProdukModal = false">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Job Order &amp; Produk</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Tambah Kode Downtime Baru (8 Waste Dropdown) -->
    <div v-if="showDtModal" class="modal-overlay" @click.self="showDtModal = false">
      <div class="modal-content">
        <h3 class="mb-1">Tambah Kode Downtime Baru</h3>
        <form @submit.prevent="submitDt">
          <div class="mb-1">
            <label class="form-label">Kode DT <span class="text-danger">*</span></label>
            <input type="text" v-model="formDt.kode" class="form-control" required placeholder="Contoh: DT01">
          </div>
          <div class="mb-1">
            <label class="form-label">Arti / Deskripsi <span class="text-danger">*</span></label>
            <input type="text" v-model="formDt.arti" class="form-control" required placeholder="Contoh: Ganti Roll">
          </div>
          <div class="mb-1">
            <label class="form-label">8 Jenis Waste <span class="text-danger">*</span></label>
            <select v-model="formDt.jenis_waste" class="form-control" required>
              <option v-for="w in wasteOptions" :key="w" :value="w">{{ w }}</option>
            </select>
          </div>
          <div class="mb-1">
            <label class="form-label">Kategori (5M) <span class="text-danger">*</span></label>
            <select v-model="formDt.kategori" class="form-control" required>
              <option v-for="k in kategori5MOptions" :key="k" :value="k">{{ k }}</option>
            </select>
          </div>
          <div class="d-flex gap-1" style="justify-content: flex-end; margin-top: 15px;">
            <button type="button" class="btn btn-secondary" @click="showDtModal = false">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Kode DT</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Tambah Jenis Cacat Baru -->
    <div v-if="showCacatModal" class="modal-overlay" @click.self="showCacatModal = false">
      <div class="modal-content">
        <h3 class="mb-1">Tambah Jenis Cacat (Reject) Baru</h3>
        <form @submit.prevent="submitCacat">
          <div class="mb-1">
            <label class="form-label">Nama Jenis Cacat <span class="text-danger">*</span></label>
            <input type="text" v-model="formCacat.nama" class="form-control" required placeholder="Contoh: Baret, Blobor, Misprint, dll.">
          </div>
          <div class="mb-1">
            <label class="form-label">Urutan Tampil <span class="text-danger">*</span></label>
            <input type="number" v-model.number="formCacat.urutan" class="form-control" required min="1">
            <small class="text-muted">Diisi otomatis berurutan dari urutan terakhir. Anda juga dapat mengubahnya secara manual.</small>
          </div>
          <div class="d-flex gap-1" style="justify-content: flex-end; margin-top: 15px;">
            <button type="button" class="btn btn-secondary" @click="showCacatModal = false">Batal</button>
            <button type="submit" class="btn btn-primary" :disabled="savingCacat">
              {{ savingCacat ? 'Menyimpan...' : 'Simpan Jenis Cacat' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Edit Master Data (Universal) -->
    <div v-if="showEditModal" class="modal-overlay" @click.self="showEditModal = false">
      <div class="modal-content">
        <h3 class="mb-1">{{ editModalTitle }}</h3>
        <form @submit.prevent="submitEdit">
          
          <!-- Edit Mesin -->
          <template v-if="editType === 'mesin'">
            <div class="mb-1">
              <label class="form-label">Nama Mesin <span class="text-danger">*</span></label>
              <input type="text" v-model="editForm.nama" class="form-control" required>
            </div>
            <div class="mb-1">
              <label class="form-label">Status</label>
              <select v-model.number="editForm.aktif" class="form-control">
                <option :value="1">Aktif</option>
                <option :value="0">Nonaktif</option>
              </select>
            </div>
          </template>

          <!-- Edit Job Order (PP) & Produk -->
          <template v-else-if="editType === 'pp'">
            <div class="mb-1">
              <label class="form-label">No PP <span class="text-danger">*</span></label>
              <input type="text" v-model="editForm.no_pp" class="form-control" required placeholder="Contoh: PP/2026/001">
            </div>
            <div class="mb-1">
              <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
              <input type="text" v-model="editForm.nama" class="form-control" required placeholder="Contoh: Sampoerna Mild">
            </div>
            <div class="grid grid-2 gap-1 mb-1">
              <div>
                <label class="form-label">Famili <span class="text-danger">*</span></label>
                <input type="text" v-model="editForm.famili" class="form-control" required placeholder="Contoh: Rokok HL">
              </div>
              <div>
                <label class="form-label">Standar Speed (Std JPH) <span class="text-danger">*</span></label>
                <input type="number" v-model.number="editForm.std_jph" class="form-control" required min="1" placeholder="Contoh: 1500">
                <small class="text-muted">Perubahan std speed otomatis memperbarui OEE LKH terkait.</small>
              </div>
            </div>
            <div class="grid grid-2 gap-1 mb-1">
              <div>
                <label class="form-label">Total Produk Dicetak (Pcs) <span class="text-danger">*</span></label>
                <input type="number" v-model.number="editForm.plan_total" class="form-control" required min="1">
              </div>
              <div>
                <label class="form-label">Status Job Order</label>
                <select v-model="editForm.status" class="form-control">
                  <option value="aktif">Aktif</option>
                  <option value="req">Req (Menunggu ACC)</option>
                  <option value="selesai">Selesai (Tutup)</option>
                </select>
              </div>
            </div>
          </template>

          <!-- Edit Kode Downtime (8 Waste Dropdown) -->
          <template v-else-if="editType === 'kode_downtime'">
            <div class="mb-1">
              <label class="form-label">Kode DT <span class="text-danger">*</span></label>
              <input type="text" v-model="editForm.kode" class="form-control" required placeholder="Contoh: DT01">
            </div>
            <div class="mb-1">
              <label class="form-label">Arti / Deskripsi <span class="text-danger">*</span></label>
              <input type="text" v-model="editForm.arti" class="form-control" required placeholder="Contoh: Ganti Roll">
            </div>
            <div class="mb-1">
              <label class="form-label">8 Jenis Waste <span class="text-danger">*</span></label>
              <select v-model="editForm.jenis_waste" class="form-control" required>
                <option v-for="w in wasteOptions" :key="w" :value="w">{{ w }}</option>
              </select>
            </div>
            <div class="mb-1">
              <label class="form-label">Kategori (5M) <span class="text-danger">*</span></label>
              <select v-model="editForm.kategori" class="form-control" required>
                <option v-for="k in kategori5MOptions" :key="k" :value="k">{{ k }}</option>
              </select>
            </div>
            <div class="mb-1">
              <label class="form-label">Status</label>
              <select v-model.number="editForm.aktif" class="form-control">
                <option :value="1">Aktif</option>
                <option :value="0">Nonaktif</option>
              </select>
            </div>
          </template>

          <!-- Edit Jenis Cacat -->
          <template v-else-if="editType === 'jenis_cacat'">
            <div class="mb-1">
              <label class="form-label">Nama Jenis Cacat <span class="text-danger">*</span></label>
              <input type="text" v-model="editForm.nama" class="form-control" required>
            </div>
            <div class="mb-1">
              <label class="form-label">Urutan Tampil</label>
              <input type="number" v-model.number="editForm.urutan" class="form-control" min="1">
            </div>
            <div class="mb-1">
              <label class="form-label">Status</label>
              <select v-model.number="editForm.aktif" class="form-control">
                <option :value="1">Aktif</option>
                <option :value="0">Nonaktif</option>
              </select>
            </div>
          </template>

          <div class="d-flex gap-1" style="justify-content: space-between; align-items: center; margin-top: 15px;">
            <div>
              <button v-if="editType === 'pp'" type="button" class="btn btn-danger" @click="deleteFromEdit" title="Hapus Job Order Ini">
                🗑️ Hapus PP Ini
              </button>
            </div>
            <div class="d-flex gap-1">
              <button type="button" class="btn btn-secondary" @click="showEditModal = false">Batal</button>
              <button type="submit" class="btn btn-primary" :disabled="savingEdit">
                {{ savingEdit ? 'Menyimpan...' : 'Simpan Perubahan' }}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal ACC Job Order (PP) -->
    <div v-if="showAccModal" class="modal-overlay" @click.self="showAccModal = false">
      <div class="modal-content" style="max-width: 500px;">
        <div class="d-flex justify-between align-center mb-1 pb-05 border-bottom">
          <h3 style="margin: 0; font-size: 1.15rem; color: #15803d;">
            <span>✓</span> ACC Job Order: {{ accForm.no_pp }}
          </h3>
          <button type="button" class="btn btn-secondary btn-sm" @click="showAccModal = false">✕</button>
        </div>

        <p class="text-muted" style="font-size: 0.83rem; margin-top: -0.25rem; margin-bottom: 1rem;">
          Job Order ini diajukan oleh operator dengan status <b>Request (req)</b>. Masukkan Standar JPH dan Total Dicetak untuk menyetujui (ACC) agar perhitungan OEE dan persentase progress aktif.
        </p>

        <form @submit.prevent="submitAcc">
          <div class="mb-1">
            <label class="form-label">Nomor PP</label>
            <input type="text" :value="accForm.no_pp" class="form-control" readonly style="background: #f1f5f9; font-weight: bold;">
          </div>

          <div class="mb-1">
            <label class="form-label">Nama Produk</label>
            <input type="text" :value="accForm.produk_nama" class="form-control" readonly style="background: #f1f5f9; font-weight: bold;">
          </div>

          <div class="mb-1">
            <label class="form-label">Famili Produk <span class="text-danger">*</span></label>
            <input type="text" v-model="accForm.famili" class="form-control" required placeholder="Contoh: HL, SKT, dll.">
          </div>

          <div class="grid grid-2 gap-1 mb-1">
            <div>
              <label class="form-label">Standar Speed (Std JPH) <span class="text-danger">*</span></label>
              <input type="number" v-model.number="accForm.std_jph" class="form-control font-bold text-primary" required min="1" placeholder="Contoh: 1500">
            </div>
            <div>
              <label class="form-label">Total Dicetak (Pcs) <span class="text-danger">*</span></label>
              <input type="number" v-model.number="accForm.plan_total" class="form-control font-bold" required min="1" placeholder="Contoh: 50000">
            </div>
          </div>

          <div class="d-flex justify-between gap-1 mt-15 pt-05 border-top">
            <button type="button" class="btn btn-secondary" @click="showAccModal = false">Batal</button>
            <button type="submit" class="btn btn-success font-bold" :disabled="savingAcc">
              {{ savingAcc ? 'Memproses ACC...' : '✓ Setujui & ACC Job Order' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { apiFetch } from '../utils/api'

// 8 Jenis Waste
const wasteOptions = [
  'Defect',
  'Overproduction',
  'Waiting',
  'Non-utilized Talent',
  'Transportation',
  'Inventory',
  'Motion',
  'Extra-processing'
]

const kategori5MOptions = [
  'Man',
  'Machine',
  'Material',
  'Method',
  'Environment'
]

const master = ref({ mesin: [], produk: [], jenis_cacat: [], kode_downtime: [], pp: [] })
const showProdukModal = ref(false)
const showDtModal = ref(false)
const showCacatModal = ref(false)
const savingCacat = ref(false)
const showEditModal = ref(false)
const savingEdit = ref(false)
const editType = ref('')
const editId = ref(0)
const editForm = ref({})
const formProduk = ref({ no_pp: '', nama: '', famili: '', plan_total: '', std_jph: '' })
const formDt = ref({ kode: '', arti: '', jenis_waste: 'Waiting', kategori: 'Machine' })
const formCacat = ref({ nama: '', urutan: 1 })

// Modal ACC Job Order
const showAccModal = ref(false)
const savingAcc = ref(false)
const accForm = ref({ id: 0, no_pp: '', produk_nama: '', famili: '', std_jph: '', plan_total: '' })

const openAccModal = (p) => {
  accForm.value = {
    id: p.id,
    no_pp: p.no_pp,
    produk_nama: p.produk_nama,
    famili: p.famili && p.famili !== '-' ? p.famili : '',
    std_jph: Number(p.std_jph) > 0 ? Number(p.std_jph) : '',
    plan_total: Number(p.plan_total) > 0 ? Number(p.plan_total) : ''
  }
  showAccModal.value = true
}

const submitAcc = async () => {
  const std_jph = Number(accForm.value.std_jph)
  const plan_total = Number(accForm.value.plan_total)
  const famili = (accForm.value.famili || '').trim()

  if (std_jph <= 0 || plan_total <= 0) {
    alert('Standar JPH dan Total Dicetak wajib diisi dengan angka lebih dari 0!')
    return
  }

  savingAcc.value = true
  try {
    const res = await apiFetch('master.php?action=acc_pp', {
      method: 'POST',
      body: JSON.stringify({
        id: accForm.value.id,
        std_jph,
        plan_total,
        famili
      })
    })

    alert(res.message || 'Job Order berhasil di-ACC!')
    showAccModal.value = false
    load()
  } catch (err) {
    alert('Gagal ACC Job Order: ' + err.message)
  } finally {
    savingAcc.value = false
  }
}

const editModalTitle = computed(() => {
  const titles = {
    mesin: 'Edit Mesin',
    jenis_cacat: 'Edit Jenis Cacat',
    kode_downtime: 'Edit Kode Downtime',
    pp: 'Edit Job Order (PP) & Produk'
  }
  return titles[editType.value] || 'Edit Master Data'
})

const load = async () => {
  try {
    const [mesin, produk, cacat, downtime, pp] = await Promise.all([
      apiFetch('master.php?action=mesin'),
      apiFetch('master.php?action=produk'),
      apiFetch('master.php?action=jenis_cacat'),
      apiFetch('master.php?action=kode_downtime'),
      apiFetch('master.php?action=pp')
    ])
    master.value.mesin = mesin
    master.value.produk = produk
    master.value.jenis_cacat = cacat
    master.value.kode_downtime = downtime
    master.value.pp = pp
  } catch (err) {
    console.error('Gagal memuat master data', err)
  }
}

onMounted(load)

const del = async (table, id) => {
  if (confirm('Nonaktifkan data ini? Data tidak akan muncul di input baru tetapi data lama tetap aman.')) {
    try {
      await apiFetch(`master.php?action=${table}&id=${id}`, { method: 'DELETE' })
      load()
    } catch(e) {
      alert('Gagal: ' + e.message)
    }
  }
}

const activateItem = async (table, item) => {
  if (confirm(`Aktifkan kembali ${table} "${item.nama || item.kode || item.no_pp}"?`)) {
    try {
      await apiFetch(`master.php?action=${table}`, {
        method: 'PUT',
        body: JSON.stringify({ id: item.id, aktif: 1 })
      })
      load()
    } catch (e) {
      alert('Gagal mengaktifkan: ' + e.message)
    }
  }
}

const hardDel = async (table, id) => {
  if (confirm('PERINGATAN! Anda akan menghapus data ini secara permanen dari database. Lanjutkan?')) {
    try {
      await apiFetch(`master.php?action=${table}&id=${id}&hard=1`, { method: 'DELETE' })
      load()
    } catch(e) {
      alert('Gagal menghapus permanen: ' + e.message)
    }
  }
}

const endJobOrder = async (ppId) => {
  if (confirm('Tutup Job Order ini? Setelah ditutup, PP tidak dapat dipilih lagi di LKH baru.')) {
    try {
      await apiFetch('master.php?action=end_pp', {
        method: 'POST',
        body: JSON.stringify({ id: ppId })
      })
      load()
    } catch (e) {
      alert('Gagal menutup PP: ' + e.message)
    }
  }
}

const activateJobOrder = async (ppId) => {
  if (confirm('Buka dan aktifkan kembali Job Order ini?')) {
    try {
      await apiFetch('master.php?action=pp', {
        method: 'PUT',
        body: JSON.stringify({ id: ppId, status: 'aktif' })
      })
      load()
    } catch (e) {
      alert('Gagal mengaktifkan PP: ' + e.message)
    }
  }
}

const deleteFromEdit = async () => {
  if (confirm(`PERINGATAN! Anda akan menghapus Job Order (PP) "${editForm.value.no_pp}" secara permanen dari database. Lanjutkan?`)) {
    try {
      await apiFetch(`master.php?action=pp&id=${editId.value}&hard=1`, { method: 'DELETE' })
      showEditModal.value = false
      load()
    } catch (e) {
      alert('Gagal menghapus PP: ' + e.message)
    }
  }
}

const editItem = (table, item) => {
  editType.value = table
  editId.value = item.id
  if (table === 'mesin') {
    editForm.value = { nama: item.nama, aktif: Number(item.aktif ?? 1) }
  } else if (table === 'pp') {
    editForm.value = {
      no_pp: item.no_pp,
      produk_id: item.produk_id,
      nama: item.produk_nama,
      famili: item.famili,
      std_jph: Number(item.std_jph),
      plan_total: Number(item.plan_total),
      status: item.status || 'aktif'
    }
  } else if (table === 'kode_downtime') {
    editForm.value = { 
      kode: item.kode, 
      arti: item.arti, 
      jenis_waste: item.jenis_waste || 'Waiting', 
      kategori: item.kategori || 'Machine', 
      aktif: Number(item.aktif ?? 1) 
    }
  } else if (table === 'jenis_cacat') {
    editForm.value = { nama: item.nama, urutan: item.urutan ?? 99, aktif: Number(item.aktif ?? 1) }
  }
  showEditModal.value = true
}

const submitEdit = async () => {
  savingEdit.value = true
  try {
    if (editType.value === 'pp') {
      // 1. Update produk (nama, famili, std_jph) jika produk_id ada
      if (editForm.value.produk_id) {
        await apiFetch('master.php?action=produk', {
          method: 'PUT',
          body: JSON.stringify({
            id: editForm.value.produk_id,
            nama: editForm.value.nama,
            famili: editForm.value.famili,
            std_jph: Number(editForm.value.std_jph),
            aktif: 1
          })
        })
      }
      // 2. Update pp (no_pp, plan_total, status)
      await apiFetch('master.php?action=pp', {
        method: 'PUT',
        body: JSON.stringify({
          id: editId.value,
          no_pp: editForm.value.no_pp,
          plan_total: Number(editForm.value.plan_total),
          status: editForm.value.status
        })
      })
    } else {
      await apiFetch(`master.php?action=${editType.value}`, {
        method: 'PUT',
        body: JSON.stringify({ id: editId.value, ...editForm.value })
      })
    }
    showEditModal.value = false
    load()
  } catch (err) {
    alert('Gagal mengedit: ' + err.message)
  } finally {
    savingEdit.value = false
  }
}

const addMesin = async () => {
  const nama = prompt('Nama Mesin:')
  if (nama && nama.trim()) {
    await apiFetch('master.php?action=mesin', { method: 'POST', body: JSON.stringify({ nama: nama.trim(), aktif: 1 }) })
    load()
  }
}

const openAddProdukPP = () => {
  formProduk.value = { no_pp: '', nama: '', famili: '', plan_total: '', std_jph: '' }
  showProdukModal.value = true
}

const onProdukNamaChange = () => {
  const inputVal = (formProduk.value.nama || '').trim().toLowerCase()
  if (!inputVal) return
  const found = master.value.produk.find(p => (p.nama || '').trim().toLowerCase() === inputVal)
  if (found) {
    if (!formProduk.value.famili) formProduk.value.famili = found.famili
    if (!formProduk.value.std_jph) formProduk.value.std_jph = found.std_jph
  }
}

const submitProdukPP = async () => {
  const inputNama = (formProduk.value.nama || '').trim()
  const inputFamili = (formProduk.value.famili || '').trim()
  const inputStdJph = Number(formProduk.value.std_jph)
  const inputNoPp = (formProduk.value.no_pp || '').trim()
  const inputPlanTotal = Number(formProduk.value.plan_total)

  if (!inputNoPp || !inputNama || !inputFamili || !inputStdJph || !inputPlanTotal) {
    alert('Semua field (No PP, Nama Produk, Famili, Std Speed, Total Dicetak) wajib diisi!')
    return
  }

  try {
    let targetProdukId = null
    const existing = master.value.produk.find(p => (p.nama || '').trim().toLowerCase() === inputNama.toLowerCase())

    if (existing) {
      targetProdukId = existing.id
      // Update produk jika ada update famili atau std_jph
      await apiFetch('master.php?action=produk', {
        method: 'PUT',
        body: JSON.stringify({
          id: existing.id,
          nama: inputNama,
          famili: inputFamili,
          std_jph: inputStdJph,
          aktif: 1
        })
      })
    } else {
      const resProduk = await apiFetch('master.php?action=produk', { 
        method: 'POST', 
        body: JSON.stringify({ 
          nama: inputNama, 
          famili: inputFamili, 
          std_jph: inputStdJph, 
          aktif: 1 
        }) 
      })
      targetProdukId = resProduk.id
    }
    
    if (targetProdukId) {
      await apiFetch('master.php?action=pp', { 
        method: 'POST', 
        body: JSON.stringify({ 
          no_pp: inputNoPp, 
          produk_id: targetProdukId, 
          plan_total: inputPlanTotal, 
          status: 'aktif' 
        }) 
      })
      alert('Job Order (PP) & Produk berhasil disimpan')
      showProdukModal.value = false
      formProduk.value = { no_pp: '', nama: '', famili: '', plan_total: '', std_jph: '' }
      load()
    }
  } catch (err) {
    alert('Gagal menyimpan: ' + err.message)
  }
}

const openAddDt = () => {
  formDt.value = { kode: '', arti: '', jenis_waste: 'Waiting', kategori: 'Machine' }
  showDtModal.value = true
}

const submitDt = async () => {
  if (!formDt.value.kode.trim() || !formDt.value.arti.trim()) return
  try {
    await apiFetch('master.php?action=kode_downtime', {
      method: 'POST',
      body: JSON.stringify({
        kode: formDt.value.kode.trim().toUpperCase(),
        arti: formDt.value.arti.trim(),
        jenis_waste: formDt.value.jenis_waste,
        kategori: formDt.value.kategori || 'Machine',
        aktif: 1
      })
    })
    showDtModal.value = false
    load()
  } catch (e) {
    alert('Gagal menambah kode downtime: ' + e.message)
  }
}

const openAddCacat = () => {
  let maxUrutan = 0
  if (master.value.jenis_cacat && master.value.jenis_cacat.length > 0) {
    maxUrutan = Math.max(...master.value.jenis_cacat.map(c => Number(c.urutan) || 0))
  }
  formCacat.value = {
    nama: '',
    urutan: maxUrutan + 1
  }
  showCacatModal.value = true
}

const submitCacat = async () => {
  if (!formCacat.value.nama.trim()) return
  savingCacat.value = true
  try {
    await apiFetch('master.php?action=jenis_cacat', {
      method: 'POST',
      body: JSON.stringify({
        nama: formCacat.value.nama.trim(),
        urutan: Number(formCacat.value.urutan) || 1,
        aktif: 1
      })
    })
    showCacatModal.value = false
    load()
  } catch (e) {
    alert('Gagal menambah jenis cacat: ' + e.message)
  } finally {
    savingCacat.value = false
  }
}
</script>

<style scoped>
.text-success { color: #047857; }
.text-danger { color: #be123c; }
.text-muted { color: var(--text-muted); }
.text-nowrap { white-space: nowrap; }
</style>
