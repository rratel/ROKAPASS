<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyStore } from '@/stores/survey'
import api from '@/services/api'

const router = useRouter()
const surveyStore = useSurveyStore()

const form = ref({
  name: '',
  dob: '',
  phone: '',
})

const submitting = ref(false)
const error = ref(null)
const errors = ref({})

function validateForm() {
  errors.value = {}

  if (!form.value.name.trim()) {
    errors.value.name = '이름을 입력해주세요.'
  }

  if (!form.value.dob.match(/^\d{6}$/)) {
    errors.value.dob = '생년월일 6자리를 입력해주세요.'
  }

  if (!form.value.phone.match(/^\d{10,11}$/)) {
    errors.value.phone = '휴대폰 번호를 입력해주세요.'
  }

  return Object.keys(errors.value).length === 0
}

async function submitReissue() {
  if (!validateForm()) return

  submitting.value = true
  error.value = null

  try {
    const response = await api.post('/survey/reissue', {
      name: form.value.name,
      dob: form.value.dob,
      phone: form.value.phone,
    })

    if (response.data.success) {
      const data = response.data.data
      surveyStore.setResult({
        survey_result: data.survey_result,
        uuid: data.uuid,
      })
      surveyStore.setPersonalInfo({
        name: data.name,
        lunch_yn: data.lunch_yn,
      })
      surveyStore.saveToStorage()
      router.push({ name: 'QR' })
    } else {
      error.value = response.data.error?.message || '조회에 실패했습니다.'
    }
  } catch (e) {
    if (e.response?.status === 404) {
      error.value = '등록된 정보를 찾을 수 없습니다. 입력 정보를 확인해주세요.'
    } else {
      error.value = e.response?.data?.error?.message || '조회에 실패했습니다.'
    }
  } finally {
    submitting.value = false
  }
}

function goBack() {
  router.push({ name: 'Landing' })
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <!-- Header -->
    <header class="bg-gradient-to-r from-slate-900 to-slate-800 text-white py-5 px-4 sticky top-0 z-10">
      <div class="max-w-lg mx-auto flex items-center">
        <button @click="goBack" class="p-2 -ml-2 hover:bg-white/10 rounded-xl transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <div class="ml-3">
          <h1 class="text-lg font-bold">QR 코드 재발급</h1>
          <p class="text-sm text-slate-400">본인 확인 후 재발급</p>
        </div>
      </div>
    </header>

    <!-- Form -->
    <main class="max-w-lg mx-auto px-4 py-6">
      <!-- Info Card -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 mb-6">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          </div>
          <div>
            <h3 class="font-semibold text-slate-900 mb-1">QR 코드 분실 안내</h3>
            <p class="text-slate-600 text-sm leading-relaxed">
              QR 코드를 분실한 경우, 아래 정보를 입력하여 재발급 받을 수 있습니다.
              입소 절차 시 입력한 정보와 동일해야 합니다.
            </p>
          </div>
        </div>
      </div>

      <form @submit.prevent="submitReissue" class="space-y-5">
        <!-- 이름 -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-2">
            이름 <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
            placeholder="홍길동"
            :class="{ 'ring-2 ring-red-500 border-transparent': errors.name }"
          />
          <p v-if="errors.name" class="text-red-500 text-sm mt-2 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ errors.name }}
          </p>
        </div>

        <!-- 생년월일 -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-2">
            생년월일 (6자리) <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.dob"
            type="text"
            inputmode="numeric"
            maxlength="6"
            class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
            placeholder="901225"
            :class="{ 'ring-2 ring-red-500 border-transparent': errors.dob }"
          />
          <p v-if="errors.dob" class="text-red-500 text-sm mt-2 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ errors.dob }}
          </p>
        </div>

        <!-- 휴대폰 번호 -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-2">
            휴대폰 번호 <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.phone"
            type="tel"
            inputmode="numeric"
            maxlength="11"
            class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
            placeholder="01012345678 (- 없이)"
            :class="{ 'ring-2 ring-red-500 border-transparent': errors.phone }"
          />
          <p v-if="errors.phone" class="text-red-500 text-sm mt-2 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ errors.phone }}
          </p>
        </div>

        <!-- Error -->
        <div v-if="error" class="flex items-center gap-3 p-4 bg-red-50 border border-red-100 rounded-xl">
          <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="text-sm text-red-600">{{ error }}</span>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="submitting"
          class="w-full py-4 bg-slate-900 text-white rounded-2xl font-bold text-lg hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 flex items-center justify-center gap-2 shadow-lg shadow-slate-900/20"
        >
          <span v-if="submitting" class="flex items-center gap-2">
            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            조회 중...
          </span>
          <span v-else class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
            QR 코드 재발급
          </span>
        </button>
      </form>

      <!-- Back Link -->
      <div class="mt-8 text-center">
        <button
          @click="goBack"
          class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-700 transition-colors text-sm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          처음으로 돌아가기
        </button>
      </div>
    </main>
  </div>
</template>
