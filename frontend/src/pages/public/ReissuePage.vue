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
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-military-700 text-white py-4 px-4">
      <div class="max-w-md mx-auto flex items-center">
        <button @click="goBack" class="mr-3">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <h1 class="text-lg font-bold">QR 코드 재발급</h1>
      </div>
    </header>

    <!-- Form -->
    <main class="max-w-md mx-auto px-4 py-6">
      <div class="bg-white rounded-lg p-5 shadow-sm mb-6">
        <p class="text-gray-600 text-sm">
          QR 코드를 분실한 경우, 아래 정보를 입력하여 재발급 받을 수 있습니다.
          입소 절차 시 입력한 정보와 동일해야 합니다.
        </p>
      </div>

      <form @submit.prevent="submitReissue" class="space-y-5">
        <!-- 이름 -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            이름 <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            class="input-field"
            placeholder="홍길동"
            :class="{ 'border-red-500': errors.name }"
          />
          <p v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name }}</p>
        </div>

        <!-- 생년월일 -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            생년월일 (6자리) <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.dob"
            type="text"
            inputmode="numeric"
            maxlength="6"
            class="input-field"
            placeholder="901225"
            :class="{ 'border-red-500': errors.dob }"
          />
          <p v-if="errors.dob" class="text-red-500 text-sm mt-1">{{ errors.dob }}</p>
        </div>

        <!-- 휴대폰 번호 -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            휴대폰 번호 <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.phone"
            type="tel"
            inputmode="numeric"
            maxlength="11"
            class="input-field"
            placeholder="01012345678"
            :class="{ 'border-red-500': errors.phone }"
          />
          <p v-if="errors.phone" class="text-red-500 text-sm mt-1">{{ errors.phone }}</p>
        </div>

        <!-- Error -->
        <p v-if="error" class="text-red-500 text-sm text-center">{{ error }}</p>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="submitting"
          class="btn-primary w-full py-4 text-lg"
        >
          <span v-if="submitting" class="flex items-center justify-center">
            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            조회 중...
          </span>
          <span v-else>QR 코드 재발급</span>
        </button>
      </form>
    </main>
  </div>
</template>
