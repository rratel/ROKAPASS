<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyStore } from '@/stores/survey'
import QRCode from 'qrcode'

const router = useRouter()
const surveyStore = useSurveyStore()

const qrCanvas = ref(null)
const currentTime = ref(new Date())
let timeInterval = null

const resultInfo = computed(() => {
  const result = surveyStore.surveyResult
  if (result === 'NORMAL') {
    return {
      status: '정상',
      bgGradient: 'from-emerald-500 to-emerald-600',
      headerBg: 'from-emerald-600 to-emerald-700',
      badgeBg: 'bg-emerald-500',
      textColor: 'text-emerald-50',
    }
  } else if (result === 'CAUTION') {
    return {
      status: '주의',
      bgGradient: 'from-amber-500 to-amber-600',
      headerBg: 'from-amber-600 to-amber-700',
      badgeBg: 'bg-amber-500',
      textColor: 'text-amber-50',
    }
  } else {
    return {
      status: '위험',
      bgGradient: 'from-red-500 to-red-600',
      headerBg: 'from-red-600 to-red-700',
      badgeBg: 'bg-red-500',
      textColor: 'text-red-50',
    }
  }
})

const formattedTime = computed(() => {
  return currentTime.value.toLocaleTimeString('ko-KR', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
})

const formattedDate = computed(() => {
  return currentTime.value.toLocaleDateString('ko-KR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    weekday: 'long',
  })
})

onMounted(async () => {
  if (!surveyStore.uuid) {
    // 저장된 세션 확인
    if (!surveyStore.loadFromStorage()) {
      router.push({ name: 'Landing' })
      return
    }
  }

  // QR 코드 생성
  await generateQR()

  // 시간 업데이트
  timeInterval = setInterval(() => {
    currentTime.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  if (timeInterval) {
    clearInterval(timeInterval)
  }
})

async function generateQR() {
  if (!qrCanvas.value || !surveyStore.uuid) return

  try {
    await QRCode.toCanvas(qrCanvas.value, surveyStore.uuid, {
      width: 220,
      margin: 2,
      color: {
        dark: '#1e293b',
        light: '#ffffff',
      },
    })
  } catch (err) {
    console.error('QR 생성 실패:', err)
  }
}

function newSurvey() {
  localStorage.removeItem('survey_session')
  surveyStore.reset()
  router.push({ name: 'Landing' })
}
</script>

<template>
  <div :class="['min-h-screen flex flex-col bg-gradient-to-br', resultInfo.bgGradient]">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
      <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;0.4&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <!-- Header -->
    <header :class="['relative py-6 px-4 bg-gradient-to-r', resultInfo.headerBg]">
      <div class="max-w-md mx-auto text-center text-white">
        <p class="text-sm opacity-80 mb-1">{{ formattedDate }}</p>
        <p class="text-4xl font-bold font-mono tracking-wider">{{ formattedTime }}</p>
      </div>
    </header>

    <!-- Main -->
    <main class="relative flex-1 flex flex-col items-center justify-center px-4 py-8">
      <div class="bg-white rounded-3xl shadow-2xl p-8 w-full max-w-sm">
        <!-- Status Badge -->
        <div class="text-center mb-6">
          <span
            :class="[
              'inline-flex items-center gap-2 px-6 py-2 rounded-full text-white font-bold text-lg shadow-lg',
              resultInfo.badgeBg
            ]"
          >
            <svg v-if="surveyStore.surveyResult === 'NORMAL'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
            <svg v-else-if="surveyStore.surveyResult === 'CAUTION'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            {{ resultInfo.status }}
          </span>
        </div>

        <!-- Name -->
        <h2 class="text-2xl font-bold text-slate-900 text-center mb-6">
          {{ surveyStore.personalInfo.name }}
        </h2>

        <!-- QR Code -->
        <div class="flex justify-center mb-6">
          <div class="bg-white p-4 rounded-2xl shadow-inner border-2 border-slate-100">
            <canvas ref="qrCanvas"></canvas>
          </div>
        </div>

        <!-- Info -->
        <div class="bg-slate-50 rounded-xl p-4 mb-4">
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 bg-slate-200 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="text-sm text-slate-600 space-y-1">
              <p>입소 시 QR 코드를 스캔해주세요</p>
              <p>퇴소 시에도 동일한 QR 코드를 사용합니다</p>
            </div>
          </div>
        </div>

        <!-- Lunch Badge -->
        <div v-if="surveyStore.personalInfo.lunch_yn" class="text-center">
          <span class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 rounded-xl text-sm font-medium border border-blue-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            중식 신청 완료
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="relative mt-8 space-y-3 w-full max-w-sm">
        <button
          @click="newSurvey"
          class="w-full py-4 bg-white/20 backdrop-blur-sm text-white rounded-2xl font-semibold hover:bg-white/30 transition-all duration-200 flex items-center justify-center gap-2 border border-white/30"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
          </svg>
          새로운 입소 절차 시작
        </button>
      </div>
    </main>

    <!-- Footer -->
    <footer class="relative py-6 text-center">
      <div class="flex items-center justify-center gap-2 text-white/70 text-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        이 화면을 캡처하여 보관하세요
      </div>
    </footer>
  </div>
</template>
