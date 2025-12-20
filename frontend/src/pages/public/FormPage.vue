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
  const code = surveyStore.accessCode
  surveyStore.reset()
  if (code) {
    router.push({ name: 'TrainingEntry', params: { code } })
  } else {
    router.push({ name: 'Landing' })
  }
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
          <h1 class="text-lg font-bold">개인정보 입력</h1>
          <p class="text-sm text-slate-400">1단계</p>
        </div>
      </div>
    </header>

    <!-- Progress -->
    <div class="bg-white border-b border-slate-100">
      <div class="max-w-lg mx-auto px-4 py-3">
        <div class="flex items-center gap-2">
          <div class="flex-1 h-1.5 bg-emerald-500 rounded-full"></div>
          <div class="flex-1 h-1.5 bg-slate-200 rounded-full"></div>
          <div class="flex-1 h-1.5 bg-slate-200 rounded-full"></div>
        </div>
      </div>
    </div>

    <!-- Form -->
    <main class="max-w-lg mx-auto px-4 py-6">
      <form @submit.prevent="submitForm" class="space-y-5">
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
            placeholder="01012345678"
            :class="{ 'ring-2 ring-red-500 border-transparent': errors.phone }"
          />
          <p v-if="errors.phone" class="text-red-500 text-sm mt-2 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ errors.phone }}
          </p>
        </div>

        <!-- 은행 선택 -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-2">
            은행 <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <select
              v-model="form.bank_name"
              class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200 appearance-none cursor-pointer"
              :class="{ 'ring-2 ring-red-500 border-transparent': errors.bank_name }"
            >
              <option value="">은행을 선택하세요</option>
              <option v-for="bank in banks" :key="bank" :value="bank">{{ bank }}</option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
              <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </div>
          </div>
          <p v-if="errors.bank_name" class="text-red-500 text-sm mt-2 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ errors.bank_name }}
          </p>
        </div>

        <!-- 계좌번호 -->
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-2">
            계좌번호 <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.account_num"
            type="text"
            inputmode="numeric"
            class="w-full px-4 py-3.5 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
            placeholder="- 없이 입력"
            :class="{ 'ring-2 ring-red-500 border-transparent': errors.account_num }"
          />
          <p v-if="errors.account_num" class="text-red-500 text-sm mt-2 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ errors.account_num }}
          </p>
        </div>

        <!-- 중식 신청 -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
          <label class="flex items-start cursor-pointer">
            <div class="relative flex items-center justify-center">
              <input
                v-model="form.lunch_yn"
                type="checkbox"
                id="lunch"
                class="sr-only"
              />
              <div
                :class="[
                  'w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all',
                  form.lunch_yn
                    ? 'bg-emerald-500 border-emerald-500'
                    : 'border-slate-300 bg-white'
                ]"
              >
                <svg v-if="form.lunch_yn" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
            </div>
            <div class="ml-4">
              <span class="font-semibold text-slate-900">중식 신청</span>
              <p class="text-sm text-slate-500 mt-1">훈련 당일 중식을 신청합니다.</p>
            </div>
          </label>

          <!-- 중식 이미지 -->
          <div v-if="lunchImage" class="mt-4 pt-4 border-t border-slate-100">
            <p class="text-sm font-medium text-slate-700 mb-3">오늘의 중식 메뉴</p>
            <img :src="lunchImage" alt="중식 메뉴" class="w-full rounded-xl shadow-sm" />
          </div>
        </div>

        <!-- 제출 에러 -->
        <div v-if="errors.submit" class="flex items-center gap-3 p-4 bg-red-50 border border-red-100 rounded-xl">
          <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="text-sm text-red-600">{{ errors.submit }}</span>
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
            처리 중...
          </span>
          <span v-else class="flex items-center gap-2">
            다음: 문진표 작성
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
          </span>
        </button>
      </form>
    </main>
  </div>
</template>
