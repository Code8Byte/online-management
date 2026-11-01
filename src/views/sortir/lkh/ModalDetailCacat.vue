<template>
  <div v-if="isOpen" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content" style="max-width: 680px; width: 95%;">
      <div class="d-flex justify-between align-center mb-1" style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
        <h3 style="margin: 0; font-size: 1.1rem; color: #991b1b;">
          <span>🔍</span> Detail Reject WIP - PP #{{ activePpIndex + 1 }}
          <span v-if="currentPpObj" style="font-size: 0.85rem; font-weight: normal; color: #475569;">
            ({{ currentPpObj.no_pp }})
          </span>
        </h3>
        <button type="button" class="btn btn-secondary btn-sm" @click="$emit('close')">✕</button>
      </div>

      <!-- Defect List: 2 Kolom (Kiri & Kanan) agar ringkas tanpa banyak scroll -->
      <div class="defect-grid-container" style="max-height: 480px; overflow-y: auto; padding: 2px;">
        <div class="defect-grid">
          <div 
            v-for="jc in jenisCacatList" 
            :key="jc.id" 
            class="defect-card-item"
            :class="{ 'has-value': (modalDetailCacat[jc.id] > 0) }"
          >
            <div class="defect-label">
              <span class="defect-nama" :title="jc.nama">{{ jc.nama }}</span>
              <span v-if="isTopDefect(jc.nama)" class="badge badge-warning" style="font-size: 0.6rem; padding: 1px 4px;">Top</span>
            </div>
            <div class="defect-input-wrapper">
              <input 
                :id="`defect-input-${jc.id}`"
                type="number" 
                v-model.number="modalDetailCacat[jc.id]" 
                class="form-control text-right font-bold defect-input" 
                min="0" 
                placeholder="0" 
              />
              <span class="defect-unit">pcs</span>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-between align-center mt-1 flex-wrap gap-05" style="border-top: 1px solid var(--border); padding-top: 0.75rem;">
        <div class="d-flex align-center gap-05">
          <button type="button" class="btn btn-danger btn-sm" @click="$emit('reset')">Reset Detail</button>
          <span style="font-size: 0.85rem; color: #475569;">
            Total: <b style="color: #991b1b; font-size: 1rem;">{{ modalCacatTotal }}</b> pcs
          </span>
        </div>
        <button type="button" class="btn btn-primary btn-sm" @click="$emit('apply')">
          Terapkan &amp; Tutup
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  activePpIndex: { type: Number, default: 0 },
  currentPpObj: { type: Object, default: null },
  modalDetailCacat: { type: Object, required: true },
  masterJenisCacat: { type: Array, required: true },
  defectShortcuts: { type: Array, required: true },
  modalCacatTotal: { type: Number, default: 0 },
  isTopDefect: { type: Function, required: true }
})

defineEmits(['close', 'apply', 'reset', 'focus-defect'])

const jenisCacatList = computed(() => {
  return props.masterJenisCacat.filter(c => c.aktif != 0 || props.modalDetailCacat[c.id] > 0)
})
</script>

<style scoped>
.defect-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
}

@media (max-width: 576px) {
  .defect-grid {
    grid-template-columns: 1fr;
  }
}

.defect-card-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding: 0.4rem 0.6rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  transition: all 0.15s ease-in-out;
}

.defect-card-item:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.defect-card-item.has-value {
  background: #fef2f2;
  border-color: #fca5a5;
}

.defect-label {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.82rem;
  font-weight: 600;
  color: #1e293b;
  min-width: 0;
  flex: 1;
}

.defect-nama {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.defect-input-wrapper {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  flex-shrink: 0;
}

.defect-input {
  width: 75px !important;
  padding: 0.25rem 0.4rem !important;
  font-size: 0.85rem !important;
  height: 32px !important;
}

.defect-unit {
  font-size: 0.75rem;
  color: #64748b;
  min-width: 20px;
}
</style>
