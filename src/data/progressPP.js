import { ref } from 'vue'
import { apiFetch } from '../utils/api'

export const initialJobOrders = []

export const jobOrdersState = ref([...initialJobOrders])

export async function fetchProgressFromApi() {
  try {
    const res = await apiFetch('ppic.php?action=progress')
    if (Array.isArray(res)) {
      jobOrdersState.value = res
    }
  } catch (e) {
    console.error('Failed to fetch progress from API:', e)
  }
}

// Auto-fetch jika berjalan di browser
if (typeof window !== 'undefined') {
  setTimeout(() => { fetchProgressFromApi() }, 150)
}


export function getJobOrders() {
  return jobOrdersState.value
}

export function updateJobOrder(id, updatedFields) {
  const index = jobOrdersState.value.findIndex(j => j.id === id)
  if (index !== -1) {
    jobOrdersState.value[index] = { ...jobOrdersState.value[index], ...updatedFields }
  }
}

export function formatNumber(val) {
  if (val === undefined || val === null || isNaN(val)) return '0'
  return Number(val).toLocaleString('id-ID')
}

export function getPct(current, target) {
  const c = Number(current) || 0
  const t = Number(target) || 0
  if (t <= 0) return 0
  return (c / t) * 100
}

export function getStatusTone(status) {
  if (status === 'selesai') return 'tone-ok'
  if (status === 'proses' || status === 'aktif') return 'tone-brand'
  if (status === 'antri' || status === 'req') return 'tone-warn'
  if (status === 'lewati') return 'tone-skip'
  return 'tone-muted'
}

export function getStatusLabel(status) {
  if (status === 'selesai') return 'Selesai'
  if (status === 'proses') return 'Sedang Berjalan'
  if (status === 'aktif') return 'Aktif'
  if (status === 'antri') return 'Antri / Belum Mulai'
  if (status === 'req') return 'Menunggu ACC'
  if (status === 'lewati') return 'Dilewati (Tidak Lewat Alur)'
  return status || '-'
}
