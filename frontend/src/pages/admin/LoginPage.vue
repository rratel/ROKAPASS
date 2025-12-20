<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  email: '',
  password: '',
})

const loading = ref(false)
const error = ref(null)

async function handleLogin() {
  if (!form.value.email || !form.value.password) {
    error.value = '이메일과 비밀번호를 입력해주세요.'
    return
  }

  loading.value = true
  error.value = null

  const result = await authStore.login(form.value.email, form.value.password)

  if (result.success) {
    router.push({ name: 'Dashboard' })
  } else {
    error.value = result.message
  }

  loading.value = false
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-military-700">ROKAPASS</h1>
        <p class="text-gray-500 mt-2">관리자 로그인</p>
      </div>

      <!-- Login Form -->
      <div class="bg-white rounded-xl shadow-lg p-8">
        <form @submit.prevent="handleLogin" class="space-y-6">
          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              이메일
            </label>
            <input
              v-model="form.email"
              type="email"
              class="input-field"
              placeholder="admin@example.com"
              autocomplete="email"
            />
          </div>

          <!-- Password -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              비밀번호
            </label>
            <input
              v-model="form.password"
              type="password"
              class="input-field"
              placeholder="••••••••"
              autocomplete="current-password"
            />
          </div>

          <!-- Error -->
          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">
            {{ error }}
          </div>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="loading"
            class="btn-primary w-full py-3"
          >
            <span v-if="loading" class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
              로그인 중...
            </span>
            <span v-else>로그인</span>
          </button>
        </form>
      </div>

      <!-- Footer -->
      <p class="text-center text-gray-400 text-sm mt-8">
        예비군 One-Step 입소 시스템
      </p>
    </div>
  </div>
</template>
