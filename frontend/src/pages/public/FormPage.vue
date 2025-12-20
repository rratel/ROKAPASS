<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyStore } from '@/stores/survey'
import api from '@/services/api'

const router = useRouter()
const surveyStore = useSurveyStore()

const form = ref({
  name: '',
  dob: '',
  phone: '',
  bank_name: '',
  account_num: '',
  lunch_yn: false,
})

const banks = [
  '국민은행', '신한은행', '우리은행', '하나은행', '농협은행',
  'SC제일은행', '케이뱅크', '카카오뱅크', '토스뱅크', '기업은행',
  '수협은행', '대구은행', '부산은행', '경남은행', '광주은행',
  '전북은행', '제주은행', '산업은행', '새마을금고', '신협',
  '우체국', '저축은행'
]

const lunchImage = ref(null)
const submitting = ref(false)
const errors = ref({})

onMounted(async () => {
  if (!surveyStore.trainingId) {
    router.push({ name: 'Landing' })
    return
  }

  // 요일별 중식 이미지 가져오기
  const dayOfWeek = new Date().getDay()
  const dayMap = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat']
  const day = dayMap[dayOfWeek]

  const training = surveyStore.trainingInfo
  if (training && training[`lunch_image_${day}`]) {
    lunchImage.value = training[`lunch_image_${day}`]
  }
})

const isFormValid = computed(() => {
  return form.value.name.trim() !== '' &&
         form.value.dob.match(/^\d{6}$/) &&
         form.value.phone.match(/^\d{10,11}$/) &&
         form.value.bank_name !== '' &&
         form.value.account_num.trim() !== ''
})

function validateForm() {
  errors.value = {}

  if (!form.value.name.trim()) {
    errors.value.name = '이름을 입력해주세요.'
  }

  if (!form.value.dob.match(/^\d{6}$/)) {
    errors.value.dob = '생년월일 6자리를 입력해주세요. (예: 901225)'
  }

  if (!form.value.phone.match(/^\d{10,11}$/)) {
    errors.value.phone = '휴대폰 번호를 입력해주세요. (- 없이)'
  }

  if (!form.value.bank_name) {
    errors.value.bank_name = '은행을 선택해주세요.'
  }

  if (!form.value.account_num.trim()) {
    errors.value.account_num = '계좌번호를 입력해주세요.'
  }

  return Object.keys(errors.value).length === 0
}

async function submitForm() {
  if (!validateForm()) return

  submitting.value = true

  try {
    // 문진표 문항 가져오기
    const response = await api.get('/questions/active')
    if (response.data.success) {
      surveyStore.setPersonalInfo(form.value)
      surveyStore.setQuestions(response.data.data)
      router.push({ name: 'Survey' })
    }
  } catch (e) {
    errors.value.submit = '문진표를 불러오는데 실패했습니다.'
  } finally {
    submitting.value = false
  }
}

function goBack() {
  surveyStore.reset()
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
        <h1 class="text-lg font-bold">개인정보 입력</h1>
      </div>
    </header>

    <!-- Form -->
    <main class="max-w-md mx-auto px-4 py-6">
      <form @submit.prevent="submitForm" class="space-y-5">
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

        <!-- 은행 선택 -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            은행 <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.bank_name"
            class="input-field"
            :class="{ 'border-red-500': errors.bank_name }"
          >
            <option value="">은행을 선택하세요</option>
            <option v-for="bank in banks" :key="bank" :value="bank">{{ bank }}</option>
          </select>
          <p v-if="errors.bank_name" class="text-red-500 text-sm mt-1">{{ errors.bank_name }}</p>
        </div>

        <!-- 계좌번호 -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            계좌번호 <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.account_num"
            type="text"
            inputmode="numeric"
            class="input-field"
            placeholder="- 없이 입력"
            :class="{ 'border-red-500': errors.account_num }"
          />
          <p v-if="errors.account_num" class="text-red-500 text-sm mt-1">{{ errors.account_num }}</p>
        </div>

        <!-- 중식 신청 -->
        <div class="bg-white rounded-lg p-4 border border-gray-200">
          <div class="flex items-start">
            <input
              v-model="form.lunch_yn"
              type="checkbox"
              id="lunch"
              class="mt-1 h-5 w-5 text-military-600 rounded"
            />
            <label for="lunch" class="ml-3">
              <span class="font-medium text-gray-800">중식 신청</span>
              <p class="text-sm text-gray-500 mt-1">훈련 당일 중식을 신청합니다.</p>
            </label>
          </div>

          <!-- 중식 이미지 -->
          <div v-if="lunchImage" class="mt-4">
            <p class="text-sm text-gray-600 mb-2">오늘의 중식 메뉴</p>
            <img :src="lunchImage" alt="중식 메뉴" class="w-full rounded-lg" />
          </div>
        </div>

        <!-- 제출 에러 -->
        <p v-if="errors.submit" class="text-red-500 text-sm text-center">{{ errors.submit }}</p>

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
            처리 중...
          </span>
          <span v-else>다음: 문진표 작성</span>
        </button>
      </form>
    </main>
  </div>
</template>
