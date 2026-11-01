import { ref } from 'vue'

export const modalState = ref({
  isOpen: false,
  title: '',
  message: '',
  type: 'info', // 'confirm' | 'error' | 'warning' | 'success' | 'info'
  confirmText: 'OK',
  cancelText: 'Batal',
  showCancel: false,
  resolve: null
})

/**
 * Menampilkan modal alert custom (menggantikan window.alert)
 */
export function showAlert(message, options = {}) {
  return new Promise((resolve) => {
    modalState.value = {
      isOpen: true,
      title: options.title || 'Pemberitahuan Sistem',
      message: String(message || ''),
      type: options.type || 'info',
      confirmText: options.confirmText || 'OK, Mengerti',
      cancelText: 'Batal',
      showCancel: false,
      resolve
    }
  })
}

/**
 * Menampilkan modal error custom
 */
export function showError(message, options = {}) {
  return showAlert(message, {
    title: options.title || 'Terjadi Kesalahan',
    type: 'error',
    confirmText: options.confirmText || 'Tutup',
    ...options
  })
}

/**
 * Menampilkan modal warning custom
 */
export function showWarning(message, options = {}) {
  return showAlert(message, {
    title: options.title || 'Peringatan Sistem',
    type: 'warning',
    confirmText: options.confirmText || 'Mengerti',
    ...options
  })
}

/**
 * Menampilkan modal sukses custom
 */
export function showSuccess(message, options = {}) {
  return showAlert(message, {
    title: options.title || 'Berhasil',
    type: 'success',
    confirmText: options.confirmText || 'Selesai',
    ...options
  })
}

/**
 * Menampilkan modal konfirmasi custom (menggantikan window.confirm)
 */
export function showConfirm(message, options = {}) {
  return new Promise((resolve) => {
    modalState.value = {
      isOpen: true,
      title: options.title || 'Konfirmasi Tindakan',
      message: String(message || ''),
      type: options.type || 'confirm',
      confirmText: options.confirmText || 'Ya, Lanjutkan',
      cancelText: options.cancelText || 'Batal',
      showCancel: true,
      resolve
    }
  })
}

/**
 * Menutup modal dengan mengembalikan nilai (true jika konfirmasi, false jika batal)
 */
export function closeModal(result = false) {
  if (modalState.value.resolve) {
    modalState.value.resolve(result)
  }
  modalState.value.isOpen = false
  modalState.value.resolve = null
}

// Hook browser default window.alert agar otomatis memakai modal custom
if (typeof window !== 'undefined') {
  window.customAlert = showAlert
  window.customConfirm = showConfirm
  // Mengalihkan window.alert default ke custom alert modal
  window.alert = (msg) => {
    showAlert(msg, { title: 'Pemberitahuan Sistem' })
  }
}
