<template>
  <div id="section-6-card" class="accordion-item mb-1" :class="{ 'is-open': isOpen }" style="border-color: #bfdbfe;">
    <div class="accordion-header" @click="$emit('toggle')" style="background: #eff6ff;">
      <div class="accordion-header-left">
        <span class="step-circle" :class="{ 'step-done': isComplete }">
          <span v-if="isComplete">✓</span>
          <span v-else>6</span>
        </span>
        <div class="accordion-header-text">
          <h4 class="accordion-title" style="color: #1e40af;">
            <span>📈</span> 6. Ringkasan &amp; Kalkulasi OEE
          </h4>
          <div v-if="!isOpen" class="accordion-snippet" style="color: #1e3a8a;">
            Total: <b>{{ Number(summaryCalculated.total_proses).toLocaleString('id-ID') }} pcs</b> &bull; 
            <span v-if="summaryCalculated.has_pending" class="badge badge-warning" style="font-size: 0.72rem; padding: 1px 5px;">
              OEE Pending (Req)
            </span>
            <span v-else>
              OEE Gabungan: <b>{{ (summaryCalculated.oee * 100).toFixed(1) }}%</b>
            </span>
          </div>
        </div>
      </div>
      <div class="accordion-header-right">
        <span v-if="summaryCalculated.has_pending" class="badge badge-warning" style="background:#fef3c7; color:#b45309; font-weight: bold;">
          OEE Pending (Req)
        </span>
        <span v-else class="badge" :class="oeeColorClass(summaryCalculated.oee)">
          OEE {{ (summaryCalculated.oee * 100).toFixed(1) }}%
        </span>
        <span class="accordion-chevron">{{ isOpen ? '▲' : '▼' }}</span>
      </div>
    </div>

    <div v-show="isOpen" class="accordion-body" style="background: #f8fafc;">
      
      <!-- NOTIFIKASI JIKA ADA PP REQ -->
      <div v-if="summaryCalculated.has_pending" class="p-075 mb-1 border-rounded" style="background: #fffbeb; border: 1px solid #fef3c7; font-size: 0.82rem; color: #92400e; line-height: 1.4;">
        ⏳ <b>Catatan Request PP:</b> Terdapat Job Order berstatus <b>Request (req)</b>. LKH tetap dapat disimpan secara normal, namun Target Shift dan OEE akan berstatus <b>Pending</b> sampai Admin menetapkan Standar JPH dan Kuota Cetak (ACC) di Master Data.
      </div>

      <!-- BREAKDOWN PER PP -->

      <div class="grid grid-2 gap-1 mb-15">
        <div 
          v-for="(item, idx) in form.pp_items" 
          :key="idx" 
          class="card p-075"
          style="margin-bottom: 0; background: white; border: 1px solid #cbd5e1;"
        >
          <div class="d-flex justify-between align-center mb-05 border-bottom pb-02">
            <span class="font-bold" style="font-size: 0.88rem; color: #1e40af;">
              PP #{{ idx + 1 }}: {{ getPpLabel(item.pp_id) }}
            </span>
            <span v-if="perPpCalculated[idx]?.is_pending" class="badge badge-warning" style="background:#fef3c7; color:#b45309; font-size: 0.72rem;">
              OEE Pending (Req)
            </span>
            <span v-else class="badge" :class="oeeColorClass(perPpCalculated[idx]?.oee || 0)">
              OEE {{ ((perPpCalculated[idx]?.oee || 0) * 100).toFixed(1) }}%
            </span>
          </div>

          <div class="grid grid-2 gap-05" style="font-size: 0.78rem;">
            <div>Total Proses: <b>{{ Number(perPpCalculated[idx]?.total_proses || 0).toLocaleString('id-ID') }} pcs</b></div>
            <div>Jam Efektif: <b>{{ (perPpCalculated[idx]?.jam_jalan || 0).toFixed(2) }} jam</b></div>
            <div>Downtime: <b>{{ perPpCalculated[idx]?.total_dt_menit || 0 }} menit</b></div>
            <div>Actual Speed: <b>{{ (perPpCalculated[idx]?.actual_jph || 0).toFixed(0) }} JPH</b></div>
          </div>
        </div>
      </div>

      <!-- TOTAL AGREGAT SHIFT -->

      <div class="grid grid-4 gap-1 mb-1">
        <div class="metric-card">
          <span class="text-muted" style="font-size: 0.72rem;">Total Proses Shift:</span><br/>
          <b style="font-size: 1.15rem; color: #0f172a;">{{ Number(summaryCalculated.total_proses).toLocaleString('id-ID') }} pcs</b>
        </div>
        <div class="metric-card">
          <span class="text-muted" style="font-size: 0.72rem;">Jam Efektif Shift:</span><br/>
          <b style="font-size: 1.15rem; color: #1e3a8a;">{{ summaryCalculated.total_jam_jalan.toFixed(2) }} jam</b>
        </div>
        <div class="metric-card">
          <span class="text-muted" style="font-size: 0.72rem;">Total Downtime Shift:</span><br/>
          <b style="font-size: 1.15rem; color: #b45309;">{{ summaryCalculated.total_dt_menit }} menit</b>
        </div>
        <div class="metric-card">
          <span class="text-muted" style="font-size: 0.72rem;">Actual JPH Shift:</span><br/>
          <b style="font-size: 1.15rem; color: #059669;">{{ summaryCalculated.actual_jph.toFixed(0) }} JPH</b>
        </div>
      </div>

      <!-- Submit Button -->
      <div class="mt-2 text-center">
        <button 
          type="submit" 
          class="btn btn-primary btn-mobile-block" 
          style="padding: 0.95rem 3.5rem; font-size: 1.15rem; font-weight: 800; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);" 
          :disabled="saving"
        >
          <span>💾</span> {{ saving ? 'Menyimpan LKH...' : (isEdit ? 'Perbarui LKH Shift' : 'Simpan LKH Shift Sekarang') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  form: { type: Object, required: true },
  isOpen: { type: Boolean, default: true },
  isComplete: { type: Boolean, default: false },
  perPpCalculated: { type: Array, required: true },
  summaryCalculated: { type: Object, required: true },
  saving: { type: Boolean, default: false },
  isEdit: { type: Boolean, default: false },
  getPpLabel: { type: Function, required: true },
  oeeColorClass: { type: Function, required: true }
})

defineEmits(['toggle'])
</script>
