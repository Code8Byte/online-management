<template>
  <div class="accordion-item mb-1" :class="{ 'is-open': isOpen }">
    <div class="accordion-header" @click="$emit('toggle')">
      <div class="accordion-header-left">
        <span class="step-circle" :class="{ 'step-done': isComplete }">
          <span v-if="isComplete">✓</span>
          <span v-else>5</span>
        </span>
        <div class="accordion-header-text">
          <h4 class="accordion-title">
            <span>📝</span> 5. Catatan Masalah &amp; PIC (Per Shift)
          </h4>
          <div v-if="!isOpen" class="accordion-snippet">
            {{ form.problem ? 'Catatan: ' + form.problem.substring(0, 35) + '...' : 'Tidak ada masalah khusus' }} &bull; PIC: <b>{{ form.pic }}</b>
          </div>
        </div>
      </div>
      <div class="accordion-header-right">
        <span class="badge badge-secondary">Opsional</span>
        <span class="accordion-chevron">{{ isOpen ? '▲' : '▼' }}</span>
      </div>
    </div>

    <div v-show="isOpen" class="accordion-body">
      <div class="grid grid-2 gap-1 mb-1">
        <div class="form-group" style="margin-bottom: 0;">
          <label>Problem / Masalah</label>
          <textarea v-model="form.problem" class="form-control" rows="2" placeholder="Uraian masalah jika ada..."></textarea>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Root Cause / Penyebab</label>
          <textarea v-model="form.root_cause" class="form-control" rows="2" placeholder="Penyebab utama kendala..."></textarea>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Tindakan Sementara</label>
          <textarea v-model="form.tindakan_sementara" class="form-control" rows="2" placeholder="Tindakan darurat di mesin..."></textarea>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Tindakan Perbaikan (Permanen)</label>
          <textarea v-model="form.tindakan_perbaikan" class="form-control" rows="2" placeholder="Tindakan jangka panjang..."></textarea>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
defineProps({
  form: { type: Object, required: true },
  isOpen: { type: Boolean, default: true },
  isComplete: { type: Boolean, default: false }
})

defineEmits(['toggle', 'next'])
</script>
