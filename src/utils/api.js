import { ref } from 'vue'

const API_BASE = '/api'
let csrfToken = ''

// Ambil status otentikasi sinkron dari localStorage agar tidak ter-reset saat back/forward browser
function getStoredAuth() {
  try {
    const raw = localStorage.getItem('pura_auth_user')
    if (raw) {
      const data = JSON.parse(raw)
      if (data && data.isAuthenticated) return data
    }
  } catch (e) {}
  return {
    isAuthenticated: false,
    username: '',
    role: '',
    divisi: ''
  }
}

export const authState = ref(getStoredAuth())

export function setAuthUser(user) {
  authState.value = {
    isAuthenticated: true,
    username: user.username || '',
    role: user.role || '',
    divisi: user.divisi || user.role || ''
  }
  try {
    localStorage.setItem('pura_auth_user', JSON.stringify(authState.value))
  } catch (e) {}
}

export function clearAuthUser() {
  authState.value = {
    isAuthenticated: false,
    username: '',
    role: '',
    divisi: ''
  }
  try {
    localStorage.removeItem('pura_auth_user')
  } catch (e) {}
}

export async function initAuth() {
  try {
    const res = await fetch(`${API_BASE}/auth.php?action=csrf`, { credentials: 'same-origin' })
    if (res.ok) {
      const data = await res.json()
      csrfToken = data.csrf_token || ''
    }
    
    const meRes = await fetch(`${API_BASE}/auth.php?action=me`, { credentials: 'same-origin' })
    if (meRes.ok) {
      const meData = await meRes.json()
      setAuthUser(meData)
      return authState.value
    } else {
      if (meRes.status === 401) {
        clearAuthUser()
      }
    }
  } catch (e) {
    console.error('Init Auth error', e)
  }
}

export async function apiFetch(endpoint, options = {}) {
  const headers = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    ...options.headers
  }

  if (options.method && ['POST', 'PUT', 'DELETE'].includes(options.method.toUpperCase())) {
    headers['X-CSRF-Token'] = csrfToken
  }

  const res = await fetch(`${API_BASE}/${endpoint}`, { credentials: 'same-origin', ...options, headers })
  
  if (res.status === 401 && !endpoint.includes('action=login') && !endpoint.includes('roadmap')) {
    clearAuthUser()
    window.location.hash = '#/login'
    throw new Error('Unauthorized')
  }

  const data = await res.json()
  if (!res.ok) {
    throw new Error(data.error || 'Terjadi kesalahan')
  }
  return data
}
