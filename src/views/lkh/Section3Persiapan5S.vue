<template>
  <div class="accordion-item mb-1" :class="{ 'is-open': isOpen }">
    <div class="accordion-header" @click="$emit('toggle')">
      <div class="accordion-header-left">
        <span class="step-circle" :class="{ 'step-done': isComplete }">
          <span v-if="isComplete">✓</span>
          <span v-else>3</span>
        </span>
        <div class="accordion-header-text">
          <h4 class="accordion-title">
            <span>🧹</span> 3. Persiapan &amp; 5S (Per Shift)
          </h4>
          <div v-if="!isOpen" class="accordion-snippet">
            Setting Awal: <b>{{ form.setting_menit || 0 }}m</b> &bull; 5S &amp; Laporan: <b>{{ form['5s_menit'] || 0 }}m</b>
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
          <label>Persiapan / Setting Mesin di Awal Shift (menit)</label>
          <input type="number" v-model.number="form.setting_menit" class="form-control font-bold" min="0" placeholder="0" />
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>5S &amp; Administrasi Laporan (menit)</label>
          <input type="number" v-model.number="form['5s_menit']" class="form-control font-bold" min="0" placeholder="0" />
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
