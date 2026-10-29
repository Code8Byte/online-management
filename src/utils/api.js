import { ref } from 'vue'

const API_BASE = '/api'
let csrfToken = ''

export const authState = ref({
  isAuthenticated: false,
  username: '',
  role: ''
})

export async function initAuth() {
  try {
    const res = await fetch(`${API_BASE}/auth.php?action=csrf`, { credentials: 'same-origin' })
    const data = await res.json()
    csrfToken = data.csrf_token || ''
    
    const meRes = await fetch(`${API_BASE}/auth.php?action=me`, { credentials: 'same-origin' })
    if (meRes.ok) {
      const meData = await meRes.json()
      authState.value = {
        isAuthenticated: true,
        username: meData.username,
        role: meData.role
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
  
  if (res.status === 401 && !endpoint.includes('action=login')) {
    authState.value.isAuthenticated = false
    window.location.hash = '#/login'
    throw new Error('Unauthorized')
  }

  const data = await res.json()
  if (!res.ok) {
    throw new Error(data.error || 'Terjadi kesalahan')
  }
  return data
}
