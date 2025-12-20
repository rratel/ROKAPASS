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
      bgColor: 'bg-green-500',
      headerBg: 'bg-green-600',
    }
  } else if (result === 'CAUTION') {
    return {
      status: '주의',
      bgColor: 'bg-yellow-500',
      headerBg: 'bg-yellow-600',
    }
  } else {
    return {
      status: '위험',
      bgColor: 'bg-red-500',
      headerBg: 'bg-red-600',
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
      width: 200,
      margin: 2,
      color: {
        dark: '#000000',
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
  <div :class="['min-h-screen flex flex-col', resultInfo.bgColor]">
    <!-- Header -->
    <header :class="['py-4 px-4', resultInfo.headerBg]">
      <div class="max-w-md mx-auto text-center text-white">
        <p class="text-sm opacity-80">{{ formattedDate }}</p>
        <p class="text-3xl font-bold font-mono">{{ formattedTime }}</p>
      </div>
    </header>

    <!-- Main -->
    <main class="flex-1 flex flex-col items-center justify-center px-4 py-8">
      <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm">
        <!-- Status Badge -->
        <div class="text-center mb-4">
          <span
            :class="[
              'inline-block px-6 py-2 rounded-full text-white font-bold text-lg',
              resultInfo.bgColor
            ]"
          >
            {{ resultInfo.status }}
          </span>
        </div>

        <!-- Name -->
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-6">
          {{ surveyStore.personalInfo.name }}
        </h2>

        <!-- QR Code -->
        <div class="flex justify-center mb-6">
          <div class="bg-white p-3 rounded-lg shadow-inner">
            <canvas ref="qrCanvas"></canvas>
          </div>
        </div>

        <!-- Info -->
        <div class="text-center text-sm text-gray-500 space-y-1">
          <p>입소 시 QR 코드를 스캔해주세요</p>
          <p>퇴소 시에도 동일한 QR 코드를 사용합니다</p>
        </div>

        <!-- Lunch Badge -->
        <div v-if="surveyStore.personalInfo.lunch_yn" class="mt-4 text-center">
          <span class="inline-block px-4 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
            중식 신청 완료
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="mt-8 space-y-3 w-full max-w-sm">
        <button
          @click="newSurvey"
          class="w-full py-3 bg-white/20 text-white rounded-xl font-medium hover:bg-white/30 transition-all"
        >
          새로운 입소 절차 시작
        </button>
      </div>
    </main>

    <!-- Footer -->
    <footer class="py-4 text-center text-white/60 text-sm">
      <p>이 화면을 캡처하여 보관하세요</p>
    </footer>
  </div>
</template>
