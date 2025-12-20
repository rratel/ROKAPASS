<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useSurveyStore } from '@/stores/survey'
import api from '@/services/api'

const router = useRouter()
const route = useRoute()
const surveyStore = useSurveyStore()

const loading = ref(true)
const error = ref(null)
const errorType = ref(null)
const training = ref(null)

onMounted(async () => {
  const code = route.params.code

  if (!code) {
    error.value = '잘못된 접근입니다.'
    loading.value = false
    return
  }

  // 기존 세션이 있는지 확인
  if (surveyStore.loadFromStorage()) {
    router.push({ name: 'QR' })
    return
  }

  try {
    const response = await api.get(`/trainings/code/${code}`)
    if (response.data.success) {
      training.value = response.data.data
      // 훈련 정보를 store에 저장
      surveyStore.setTrainingInfo(training.value)
    } else {
      error.value = response.data.error?.message || '훈련 정보를 찾을 수 없습니다.'
    }
  } catch (e) {
    const errorCode = e.response?.data?.error?.code
    const errorMessage = e.response?.data?.error?.message

    if (e.response?.status === 404) {
      error.value = errorMessage || '유효하지 않은 훈련 코드입니다.'
      errorType.value = 'not_found'
    } else if (e.response?.status === 403) {
      error.value = errorMessage || '훈련에 접근할 수 없습니다.'
      errorType.value = errorCode === 'NOT_STARTED' ? 'not_started' : 'completed'
    } else {
      error.value = '훈련 정보를 불러오는데 실패했습니다.'
      errorType.value = 'error'
    }
  } finally {
    loading.value = false
  }
})

function startSurvey() {
  router.push({ name: 'Form' })
}

function goHome() {
  router.push({ name: 'Landing' })
}

function retry() {
  window.location.reload()
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex flex-col">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
      <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;0.4&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <!-- Header -->
    <header class="relative py-8 px-4 text-center">
      <div class="w-16 h-16 bg-emerald-500/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4 border border-emerald-500/30">
        <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
      </div>
      <h1 class="text-3xl font-bold text-white tracking-tight">ROKAPASS</h1>
      <p class="text-slate-400 mt-2">예비군 One-Step 입소 시스템</p>
    </header>

    <!-- Main Content -->
    <main class="relative flex-1 flex flex-col items-center justify-center px-4 pb-8">
      <div class="w-full max-w-md">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <div class="inline-flex items-center gap-3">
            <svg class="animate-spin h-8 w-8 text-emerald-400" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-slate-300 text-lg">훈련 정보 확인 중...</span>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="space-y-6">
          <!-- Not Started (일시정지) -->
          <div v-if="errorType === 'not_started'" class="bg-amber-500/20 backdrop-blur-sm border border-amber-500/30 text-amber-200 p-6 rounded-2xl text-center">
            <div class="w-16 h-16 bg-amber-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <h3 class="text-lg font-semibold mb-2">훈련 대기중</h3>
            <p class="text-amber-300">{{ error }}</p>
            <p class="text-amber-400/70 text-sm mt-2">관리자가 훈련을 시작하면 이용 가능합니다.</p>

            <button
              @click="retry"
              class="mt-6 w-full py-4 bg-amber-500 text-white rounded-2xl font-medium hover:bg-amber-600 transition-all duration-200 flex items-center justify-center gap-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
              다시 시도
            </button>
          </div>

          <!-- Completed -->
          <div v-else-if="errorType === 'completed'" class="bg-slate-500/20 backdrop-blur-sm border border-slate-500/30 text-slate-200 p-6 rounded-2xl text-center">
            <div class="w-16 h-16 bg-slate-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <h3 class="text-lg font-semibold mb-2">훈련 종료</h3>
            <p class="text-slate-300">{{ error }}</p>
            <p class="text-slate-400/70 text-sm mt-3">이미 문진표를 작성하셨다면 QR코드 재발급을 이용하세요.</p>

            <router-link
              :to="{ name: 'Reissue' }"
              class="mt-6 w-full py-4 bg-slate-500 text-white rounded-2xl font-medium hover:bg-slate-600 transition-all duration-200 flex items-center justify-center gap-2 inline-flex"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
              </svg>
              QR코드 재발급
            </router-link>
          </div>

          <!-- Other Errors -->
          <div v-else class="bg-red-500/20 backdrop-blur-sm border border-red-500/30 text-red-200 p-6 rounded-2xl text-center">
            <div class="w-16 h-16 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <h3 class="text-lg font-semibold mb-2">접근 오류</h3>
            <p class="text-red-300">{{ error }}</p>
          </div>

          <button
            v-if="!['not_started', 'completed'].includes(errorType)"
            @click="goHome"
            class="w-full py-4 bg-white/10 backdrop-blur-sm text-white rounded-2xl font-medium hover:bg-white/20 transition-all duration-200 flex items-center justify-center gap-2 border border-white/20"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            처음으로
          </button>
        </div>

        <!-- Training Found -->
        <div v-else-if="training" class="space-y-6">
          <div class="bg-white rounded-2xl shadow-2xl p-6">
            <!-- Training Icon -->
            <div class="flex items-center justify-center mb-6">
              <div class="w-20 h-20 bg-emerald-100 rounded-2xl flex items-center justify-center">
                <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
              </div>
            </div>

            <!-- Training Info -->
            <div class="text-center mb-6">
              <h2 class="text-xl font-bold text-slate-900 mb-2">{{ training.name }}</h2>
              <div class="flex items-center justify-center gap-2 text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>{{ new Date(training.training_date).toLocaleDateString('ko-KR', { year: 'numeric', month: 'long', day: 'numeric', weekday: 'short' }) }}</span>
              </div>
            </div>

            <!-- Status Badge -->
            <div class="flex justify-center mb-6">
              <span class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 rounded-xl text-sm font-medium">
                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                {{ training.status === 'active' ? '훈련 진행중' : '훈련 예정' }}
              </span>
            </div>

            <!-- Steps Info -->
            <div class="bg-slate-50 rounded-xl p-4 mb-6">
              <h4 class="text-sm font-semibold text-slate-700 mb-3">입소 절차 안내</h4>
              <div class="space-y-2 text-sm text-slate-600">
                <div class="flex items-center gap-3">
                  <span class="w-6 h-6 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center text-xs font-bold">1</span>
                  <span>개인정보 입력</span>
                </div>
                <div class="flex items-center gap-3">
                  <span class="w-6 h-6 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center text-xs font-bold">2</span>
                  <span>건강 문진표 작성</span>
                </div>
                <div class="flex items-center gap-3">
                  <span class="w-6 h-6 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center text-xs font-bold">3</span>
                  <span>QR 코드 발급</span>
                </div>
              </div>
            </div>

            <!-- Start Button -->
            <button
              @click="startSurvey"
              class="w-full py-4 bg-emerald-500 text-white rounded-2xl font-bold text-lg hover:bg-emerald-600 transition-all duration-200 flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/30"
            >
              <span>입소 절차 시작</span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
              </svg>
            </button>
          </div>

          <!-- QR Reissue Link -->
          <div class="text-center">
            <router-link
              :to="{ name: 'Reissue' }"
              class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors text-sm"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
              </svg>
              QR코드 분실 시 재발급
            </router-link>
          </div>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="relative py-6 text-center text-slate-500 text-sm">
      <p>ROKAPASS - 예비군 One-Step 입소 시스템</p>
    </footer>
  </div>
</template>
