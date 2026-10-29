<template>
  <div class="dashboard-operator-view" style="padding: 1.5rem 0;">
    <div style="max-width: 840px; margin: 1.5rem auto;">

      <div class="grid grid-2 gap-15">
        <!-- 1. Cost Control (CC) -->
        <div
          class="card text-center operator-menu-card"
          @click="openModalCC"
          style="cursor: pointer; padding: 2rem 1.5rem; border-radius: 16px; border: 1.5px solid #c7d2fe; background: linear-gradient(165deg, #ffffff 0%, #f8f8fe 60%, #f3f2fd 100%); transition: all 0.2s ease; box-shadow: 0 6px 18px rgba(79, 70, 229, 0.10);"
        >
          <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #eef2ff, #e0e7ff); color: #4f46e5; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem auto; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.18);">
            📑
          </div>
          <h3 style="font-size: 1.25rem; font-weight: 700; color: #20223d; margin-bottom: 0.4rem;">
            Cost Control
          </h3>
          <button class="btn btn-primary btn-block" style="font-weight: 600; border-radius: 8px;">
            Export Cost Control
          </button>
        </div>

        <!-- 2. LKH Produksi (Export Excel LKH Khusus Operator) -->
        <div
          class="card text-center operator-menu-card"
          @click="openModalLkhOperator"
          style="cursor: pointer; padding: 2rem 1.5rem; border-radius: 16px; border: 1.5px solid #bbf7d0; background: linear-gradient(165deg, #ffffff 0%, #f8fefb 60%, #f1fdf6 100%); transition: all 0.2s ease; box-shadow: 0 6px 18px rgba(16, 185, 129, 0.10);"
        >
          <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem auto; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.18);">
            📗
          </div>
          <h3 style="font-size: 1.25rem; font-weight: 700; color: #20223d; margin-bottom: 0.4rem;">
            LKH Produksi
          </h3>
          <button class="btn btn-success btn-block" style="font-weight: 600; border-radius: 8px;">
            Export LKH Produksi
          </button>
        </div>

        <!-- 3. STB (Blank / Placeholder) -->
        <div
          class="card text-center operator-menu-card blank-card"
          @click="openBlankMenu('STB')"
          style="cursor: pointer; padding: 2rem 1.5rem; border-radius: 16px; border: 1.5px dashed #d8dbee; background: #f6f6fc; transition: all 0.2s ease; opacity: 0.88;"
        >
          <div style="width: 64px; height: 64px; background: #eceef8; color: #666c8a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem auto;">
            📦
          </div>
          <div class="d-flex align-center justify-center gap-05 mb-05">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: #4b4f6e; margin: 0;">
              STB
            </h3>
            <span class="badge" style="background: #e4e5f1; color: #666c8a; font-size: 0.7rem; font-weight: 600; padding: 2px 6px; border-radius: 4px;">Segera Hadir</span>
          </div>
          <button class="btn btn-secondary btn-block" style="font-weight: 600; border-radius: 8px; background: #e4e5f1; color: #4b4f6e; border: none;">
            Belum Tersedia
          </button>
        </div>

        <!-- 4. Kiriman (Blank / Placeholder) -->
        <div
          class="card text-center operator-menu-card blank-card"
          @click="openBlankMenu('Kiriman')"
          style="cursor: pointer; padding: 2rem 1.5rem; border-radius: 16px; border: 1.5px dashed #d8dbee; background: #f6f6fc; transition: all 0.2s ease; opacity: 0.88;"
        >
          <div style="width: 64px; height: 64px; background: #eceef8; color: #666c8a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem auto;">
            🚚
          </div>
          <div class="d-flex align-center justify-center gap-05 mb-05">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: #4b4f6e; margin: 0;">
              Kiriman
            </h3>
            <span class="badge" style="background: #e4e5f1; color: #666c8a; font-size: 0.7rem; font-weight: 600; padding: 2px 6px; border-radius: 4px;">Segera Hadir</span>
          </div>
          <button class="btn btn-secondary btn-block" style="font-weight: 600; border-radius: 8px; background: #e4e5f1; color: #4b4f6e; border: none;">
            Belum Tersedia
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Export Cost Control (CC) -->
    <div v-if="showModalCC" class="modal-overlay" @click.self="showModalCC = false">
      <div class="modal-content" style="max-width: 480px;">
        <div class="d-flex justify-between align-center mb-1">
          <h3 style="margin: 0; font-size: 1.15rem;">📑 Export Cost Control (CC)</h3>
          <button type="button" class="btn btn-sm btn-secondary" @click="showModalCC = false" style="padding: 0.2rem 0.5rem;">✕</button>
        </div>

        <form @submit.prevent="submitExportCC('xlsx')">
          <div class="form-group mb-1">
            <label>Tanggal <span class="text-danger">*</span></label>
            <input type="date" v-model="formCC.tanggal" class="form-control" required />
          </div>

          <div class="form-group mb-1">
            <label>Pilihan Shift <span class="text-danger">*</span></label>
            <select v-model="formCC.shift" class="form-control" required>
              <option value="1">Shift 1</option>
              <option value="2">Shift 2</option>
              <option value="3">Shift 3</option>
            </select>
          </div>

          <div class="d-flex justify-between align-center gap-05 mt-1" style="border-top: 1px solid var(--border-light); padding-top: 1rem;">
            <button type="button" class="btn btn-secondary" @click="showModalCC = false">
              Batal
            </button>
            <div class="d-flex gap-05">
              <button type="button" class="btn btn-secondary" @click="submitExportCC('html')" title="Tampilan cetak / simpan langsung ke PDF">
                🖨️ Cetak / PDF
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Export LKH Produksi (Khusus Operator: Hanya Tanggal & Shift) -->
    <div v-if="showModalLkhOperator" class="modal-overlay" @click.self="showModalLkhOperator = false">
      <div class="modal-content" style="max-width: 480px;">
        <div class="d-flex justify-between align-center mb-1">
          <h3 style="margin: 0; font-size: 1.15rem; display: flex; align-items: center; gap: 6px;">
            <span>📗</span> Export LKH Produksi
          </h3>
          <button type="button" class="btn btn-sm btn-secondary" @click="showModalLkhOperator = false" style="padding: 0.2rem 0.5rem;">✕</button>
        </div>

        <form @submit.prevent="submitExportLkhOperator('xlsx')">
          <div class="form-group mb-1">
            <label>Tanggal <span class="text-danger">*</span></label>
            <input type="date" v-model="formLkhOperator.tanggal" class="form-control" required />
          </div>

          <div class="form-group mb-1">
            <label>Pilihan Shift <span class="text-danger">*</span></label>
            <select v-model="formLkhOperator.shift" class="form-control" required>
              <option value="1">Shift 1</option>
              <option value="2">Shift 2</option>
              <option value="3">Shift 3</option>
            </select>
          </div>

          <div class="d-flex justify-between align-center gap-05 mt-1" style="border-top: 1px solid var(--border-light); padding-top: 1rem;">
            <button type="button" class="btn btn-secondary" @click="showModalLkhOperator = false">
              Batal
            </button>
            <div class="d-flex gap-05">
              <button type="submit" class="btn btn-success">
                <span>📥</span> Download Excel (.xlsx)
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const getLocalDate = () => {
  const d = new Date()
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

// State Modal CC
const showModalCC = ref(false)
const formCC = ref({
  tanggal: getLocalDate(),
  shift: '1'
})

const openModalCC = () => {
  formCC.value.tanggal = getLocalDate()
  formCC.value.shift = '1'
  showModalCC.value = true
}

const submitExportCC = (format = 'xlsx') => {
  const query = new URLSearchParams()
  if (formCC.value.tanggal) query.append('tanggal', formCC.value.tanggal)
  if (formCC.value.shift) query.append('shift', formCC.value.shift)
  if (format === 'xlsx') {
    query.append('format', 'xlsx')
  }
  window.open(`/api/export_cc.php?${query.toString()}`, '_blank')
  showModalCC.value = false
}

// State Modal LKH Produksi Operator
const showModalLkhOperator = ref(false)
const formLkhOperator = ref({
  tanggal: getLocalDate(),
  shift: '1'
})

const openModalLkhOperator = () => {
  formLkhOperator.value = {
    tanggal: getLocalDate(),
    shift: '1'
  }
  showModalLkhOperator.value = true
}

const submitExportLkhOperator = (format = 'xlsx') => {
  const query = new URLSearchParams()
  if (formLkhOperator.value.tanggal) {
    query.append('start_date', formLkhOperator.value.tanggal)
    query.append('end_date', formLkhOperator.value.tanggal)
  }
  if (formLkhOperator.value.shift) {
    query.append('shift', formLkhOperator.value.shift)
  }
  if (format === 'csv') {
    query.append('format', 'csv')
  }
  window.open(`/api/export.php?${query.toString()}`, '_blank')
  showModalLkhOperator.value = false
}

const openBlankMenu = (menuName) => {
  alert(`Menu "${menuName}" masih dalam tahap persiapan / belum tersedia.`)
}
</script>

<style scoped>
.operator-menu-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.operator-menu-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 14px 28px rgba(21, 23, 55, 0.12);
}
.blank-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(21, 23, 55, 0.07);
}
</style>
