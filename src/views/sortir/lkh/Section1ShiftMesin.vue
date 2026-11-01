<template>
  <div class="accordion-item mb-1" :class="{ 'is-open': isOpen }">
    <div class="accordion-header" @click="$emit('toggle')">
      <div class="accordion-header-left">
        <span class="step-circle" :class="{ 'step-done': isComplete }">
          <span v-if="isComplete">✓</span>
          <span v-else>1</span>
        </span>
        <div class="accordion-header-text">
          <h4 class="accordion-title">
            <span>⚙️</span> 1. Data Shift, Mesin &amp; Operator
          </h4>
          <div v-if="!isOpen" class="accordion-snippet">
            Shift {{ form.shift || '-' }} &bull; {{ getMesinNama(form.mesin_id) || 'Pilih Mesin' }} &bull; Opr: {{ form.operator || '-' }}
          </div>
        </div>
      </div>
      <div class="accordion-header-right">
        <span class="accordion-chevron">{{ isOpen ? '▲' : '▼' }}</span>
      </div>
    </div>

    <div v-show="isOpen" class="accordion-body">
      <div class="grid grid-3 gap-1 mb-1">
        <div class="form-group" style="margin-bottom: 0;">
          <label>Tanggal <span class="text-danger">*</span></label>
          <input type="date" v-model="form.tanggal" class="form-control" required />
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Shift <span class="text-danger">*</span></label>
          <select v-model="form.shift" class="form-control" required @change="$emit('shift-change')">
            <option value="">-- Pilih Shift --</option>
            <option v-for="s in master.shift" :key="s.shift" :value="s.shift">
              Shift {{ s.shift }} ({{ s.jam_mulai.substring(0,5) }} - {{ s.jam_selesai.substring(0,5) }})
            </option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>Mesin <span class="text-danger">*</span></label>
          <select v-model="form.mesin_id" class="form-control" required>
            <option value="">-- Pilih Mesin --</option>
            <option v-for="m in master.mesin.filter(x => x.aktif != 0 || x.id === form.mesin_id)" :key="m.id" :value="m.id">
              {{ m.nama }}
            </option>
          </select>
        </div>
      </div>

      <div class="grid grid-2 gap-1 mb-1">
        <div class="form-group" style="margin-bottom: 0;">
          <label>Nama Operator <span class="text-danger">*</span></label>
          <input type="text" v-model="form.operator" class="form-control" required placeholder="Ketik nama operator..." />
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label>PIC<span class="text-danger">*</span></label>
          <input 
            type="text" 
            v-model="form.pic" 
            class="form-control" 
            readonly 
            style="background-color: #f1f5f9; cursor: not-allowed; font-weight: 600; color: #1e293b;" 
            title="PIC terisi otomatis sesuai akun login saat ini dan terkunci"
          />
        </div>
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
  hasPrefilledHistory: { type: Boolean, default: false },
  getMesinNama: { type: Function, required: true }
})

defineEmits(['toggle', 'next', 'shift-change'])
</script>
