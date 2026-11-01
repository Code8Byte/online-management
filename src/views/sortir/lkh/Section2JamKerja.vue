<template>
  <div class="accordion-item mb-1" :class="{ 'is-open': isOpen }">
    <div class="accordion-header" @click="$emit('toggle')">
      <div class="accordion-header-left">
        <span class="step-circle" :class="{ 'step-done': isComplete }">
          <span v-if="isComplete">✓</span>
          <span v-else>2</span>
        </span>
        <div class="accordion-header-text">
          <h4 class="accordion-title">
            <span>🕒</span> 2. Jam Kerja Shift
          </h4>
          <div v-if="!isOpen" class="accordion-snippet">
            {{ formatTimeSnippet(form.start) }} s/d {{ formatTimeSnippet(form.finish) }} &bull; Durasi Shift: <b>{{ shiftRawDurationJam.toFixed(2) }} jam</b>
          </div>
        </div>
      </div>
      <div class="accordion-header-right">
        <span class="accordion-chevron">{{ isOpen ? '▲' : '▼' }}</span>
      </div>
    </div>

    <div v-show="isOpen" class="accordion-body">
      <div class="grid grid-2 gap-1">
        <div class="form-group" style="margin-bottom: 0;">
          <label>Start Kerja Shift <span class="text-danger">*</span></label>
          <input type="datetime-local" v-model="form.start" class="form-control" required @change="$emit('shift-time-change')" />
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Finish Kerja Shift <span class="text-danger">*</span></label>
          <input type="datetime-local" v-model="form.finish" class="form-control" required @change="$emit('shift-time-change')" />
        </div>
      </div>

      <!-- Kolom Lembur Kerja -->
      <div class="mt-1 p-075 border-rounded" style="background: #fdf2f8; border: 1px solid #fbcfe8;">
        <div class="form-group" style="margin-bottom: 0;">
          <label style="font-size: 0.85rem; color: #831843; font-weight: 700;">
            ⏰ Lembur Kerja
          </label>
          <div class="d-flex align-center gap-05" style="max-width: 200px;">
            <input 
              type="number" 
              v-model.number="form.lembur_menit" 
              class="form-control font-bold" 
              min="0" 
              step="5" 
              placeholder="0"
              style="border-color: #f472b6;"
              @input="$emit('lembur-change')"
            />
            <span class="text-muted" style="font-size: 0.85rem;">menit</span>
          </div>
        </div>
      </div>
      <div v-if="timeWarning" class="badge badge-danger mt-1" style="display: block; text-align: left; padding: 0.5rem 0.75rem;">
        ⚠️ {{ timeWarning }}
      </div>

    </div>
  </div>
</template>

<script setup>
defineProps({
  form: { type: Object, required: true },
  isOpen: { type: Boolean, default: true },
  isComplete: { type: Boolean, default: false },
  shiftRawDurationJam: { type: Number, default: 0 },
  timeWarning: { type: String, default: '' },
  formatTimeSnippet: { type: Function, required: true }
})

defineEmits(['toggle', 'next', 'shift-time-change', 'lembur-change'])
</script>
