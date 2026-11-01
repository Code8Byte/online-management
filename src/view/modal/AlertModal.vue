<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="modalState.isOpen"
        class="custom-modal-overlay"
        @click.self="handleBackdropClick"
        role="dialog"
        aria-modal="true"
      >
        <div class="custom-modal-box" :class="`box-${modalState.type}`">
          <!-- Close button (top right) -->
          <button
            type="button"
            class="modal-close-btn"
            @click="handleCancel"
            title="Tutup dialog (Esc)"
          >
            ✕
          </button>

          <!-- Icon Header -->
          <div class="modal-icon-wrap" :class="`icon-${modalState.type}`">
            <!-- Logout specific icon -->
            <span v-if="isLogoutConfirm" class="icon-sym">🚪</span>
            <span v-else-if="modalState.type === 'error'" class="icon-sym">✕</span>
            <span v-else-if="modalState.type === 'warning'" class="icon-sym">⚠️</span>
            <span v-else-if="modalState.type === 'success'" class="icon-sym">✓</span>
            <span v-else-if="modalState.type === 'confirm'" class="icon-sym">❓</span>
            <span v-else class="icon-sym">ℹ️</span>
          </div>

          <!-- Content: Title & Message -->
          <div class="modal-content">
            <h3 class="modal-title">{{ modalState.title }}</h3>
            <p class="modal-message">{{ modalState.message }}</p>
          </div>

          <!-- Action Buttons -->
          <div class="modal-actions" :class="{ 'actions-dual': modalState.showCancel }">
            <button
              v-if="modalState.showCancel"
              type="button"
              class="btn-modal btn-modal-cancel"
              @click="handleCancel"
            >
              {{ modalState.cancelText || 'Batal' }}
            </button>
            <button
              type="button"
              class="btn-modal btn-modal-confirm"
              :class="confirmBtnClass"
              @click="handleConfirm"
              ref="confirmBtnRef"
            >
              {{ modalState.confirmText || 'OK' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { modalState, closeModal } from './modalService'

const confirmBtnRef = ref(null)

const isLogoutConfirm = computed(() => {
  const t = (modalState.value.title || '').toLowerCase()
  const m = (modalState.value.message || '').toLowerCase()
  return t.includes('logout') || m.includes('logout') || m.includes('keluar')
})

const confirmBtnClass = computed(() => {
  if (isLogoutConfirm.value || modalState.value.type === 'error') {
    return 'btn-danger'
  }
  if (modalState.value.type === 'warning') {
    return 'btn-warning'
  }
  if (modalState.value.type === 'success') {
    return 'btn-success'
  }
  return 'btn-primary'
})

const handleConfirm = () => {
  closeModal(true)
}

const handleCancel = () => {
  closeModal(false)
}

const handleBackdropClick = () => {
  // Hanya tutup otomatis jika bukan konfirmasi kritis
  if (!modalState.value.showCancel) {
    closeModal(false)
  }
}

// Keyboard shortcuts: ESC untuk batal, Enter untuk konfirmasi
const handleKeydown = (e) => {
  if (!modalState.value.isOpen) return
  if (e.key === 'Escape') {
    e.preventDefault()
    handleCancel()
  } else if (e.key === 'Enter') {
    e.preventDefault()
    handleConfirm()
  }
}

watch(() => modalState.value.isOpen, (open) => {
  if (open) {
    nextTick(() => {
      confirmBtnRef.value?.focus()
    })
  }
})

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
/* Overlay Backdrop */
.custom-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999999;
  padding: 1rem;
}

/* Modal Card Box */
.custom-modal-box {
  position: relative;
  width: 100%;
  max-width: 440px;
  background: #ffffff;
  border-radius: 18px;
  box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35), 0 0 0 1px rgba(226, 232, 240, 0.8);
  padding: 1.75rem 1.6rem 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  animation: modalPopIn 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalPopIn {
  from {
    opacity: 0;
    transform: scale(0.92) translateY(8px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* Close button (top right) */
.modal-close-btn {
  position: absolute;
  top: 14px;
  right: 14px;
  background: transparent;
  border: none;
  font-size: 1.1rem;
  color: #94a3b8;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
}
.modal-close-btn:hover {
  background: #f1f5f9;
  color: #334155;
}

/* Icon Wrap */
.modal-icon-wrap {
  width: 58px;
  height: 58px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.1rem;
  font-size: 1.6rem;
}
.icon-error {
  background: #fee2e2;
  color: #ef4444;
  border: 4px solid #fef2f2;
}
.icon-warning {
  background: #fef3c7;
  color: #d97706;
  border: 4px solid #fffbeb;
}
.icon-confirm {
  background: #e0e7ff;
  color: #4f46e5;
  border: 4px solid #eef2ff;
}
.icon-success {
  background: #d1fae5;
  color: #10b981;
  border: 4px solid #ecfdf5;
}
.icon-info {
  background: #e0f2fe;
  color: #0284c7;
  border: 4px solid #f0f9ff;
}
.icon-sym {
  display: inline-flex;
  font-weight: 800;
  line-height: 1;
}

/* Content */
.modal-content {
  width: 100%;
  margin-bottom: 1.4rem;
}
.modal-title {
  margin: 0 0 0.55rem;
  font-size: 1.18rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.01em;
}
.modal-message {
  margin: 0;
  font-size: 0.9rem;
  color: #475569;
  line-height: 1.5;
  white-space: pre-line;
  word-break: break-word;
}

/* Action Buttons */
.modal-actions {
  width: 100%;
  display: flex;
  gap: 0.75rem;
}
.actions-dual {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
}

.btn-modal {
  height: 42px;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 700;
  border: 1px solid transparent;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
  padding: 0 1rem;
}

.btn-modal-cancel {
  background: #f8fafc;
  color: #64748b;
  border-color: #cbd5e1;
}
.btn-modal-cancel:hover {
  background: #f1f5f9;
  color: #334155;
  border-color: #94a3b8;
}

.btn-primary {
  background: #4f46e5;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}
.btn-primary:hover {
  background: #4338ca;
  box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);
}

.btn-danger {
  background: #ef4444;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}
.btn-danger:hover {
  background: #dc2626;
  box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
}

.btn-warning {
  background: #d97706;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3);
}
.btn-warning:hover {
  background: #b45309;
}

.btn-success {
  background: #10b981;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.btn-success:hover {
  background: #059669;
}

/* Transition Animations */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
