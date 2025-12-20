import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  // State
  const admin = ref(null)
  const token = ref(localStorage.getItem('admin_token'))

  // Getters
  const isAuthenticated = computed(() => !!token.value)
  const adminName = computed(() => admin.value?.name || '')
  const adminRole = computed(() => admin.value?.role || '')

  // Actions
  async function login(email, password) {
    try {
      const response = await api.post('/auth/login', { email, password })
      if (response.data.success) {
        token.value = response.data.data.token
        admin.value = response.data.data.admin
        localStorage.setItem('admin_token', token.value)
        return { success: true }
      }
      return { success: false, message: response.data.error?.message }
    } catch (error) {
      return { success: false, message: error.response?.data?.error?.message || '로그인에 실패했습니다.' }
    }
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
    } catch (e) {
      // Ignore errors
    }
    token.value = null
    admin.value = null
    localStorage.removeItem('admin_token')
  }

  async function fetchMe() {
    if (!token.value) return false
    try {
      const response = await api.get('/auth/me')
      if (response.data.success) {
        admin.value = response.data.data
        return true
      }
      return false
    } catch (error) {
      token.value = null
      localStorage.removeItem('admin_token')
      return false
    }
  }

  return {
    // State
    admin,
    token,
    // Getters
    isAuthenticated,
    adminName,
    adminRole,
    // Actions
    login,
    logout,
    fetchMe,
  }
})
