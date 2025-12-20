<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()

const trainingId = ref(localStorage.getItem('kiosk_training_id'))
const trainingInfo = ref(null)
const exitMode = ref('auto') // 'auto' | 'confirm'
const scanning = ref(false)
const lastScan = ref(null)
const scanResult = ref(null)
const showConfirmDialog = ref(false)
const pendingExit = ref(null)
const currentTime = ref(new Date())
const error = ref(null)

let timeInterval = null
let scanTimeout = null

const formattedTime = computed(() => {
  return currentTime.value.toLocaleTimeString('ko-KR', {
    hour: '2-digit',
    minute: '2-digit',
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
  if (!trainingId.value) {
    router.push({ name: 'KioskSetup' })
    return
  }

  // 훈련 정보 가져오기
  await fetchTrainingInfo()

  // 시간 업데이트
  timeInterval = setInterval(() => {
    currentTime.value = new Date()
  }, 1000)

  // QR 스캐너 시작 (실제 구현에서는 카메라 API 사용)
  startScanner()
})

onUnmounted(() => {
  if (timeInterval) clearInterval(timeInterval)
  if (scanTimeout) clearTimeout(scanTimeout)
})

async function fetchTrainingInfo() {
  try {
    const response = await api.get(`/trainings/${trainingId.value}`)
    if (response.data.success) {
      trainingInfo.value = response.data.data
      exitMode.value = response.data.data.exit_mode || 'auto'
    }
  } catch (e) {
    error.value = '훈련 정보를 불러오는데 실패했습니다.'
  }
}

function startScanner() {
  // 실제 구현에서는 카메라 API를 사용
  // 여기서는 수동 입력을 시뮬레이션
  scanning.value = true
}

async function handleQRScan(uuid) {
  if (lastScan.value === uuid && Date.now() - lastScan.value.time < 3000) {
    return // 중복 스캔 방지
  }

  lastScan.value = { uuid, time: Date.now() }
  scanning.value = false

  try {
    const response = await api.post('/kiosk/scan', {
      training_id: trainingId.value,
      uuid: uuid,
    })

    if (response.data.success) {
      const data = response.data.data

      scanResult.value = {
        success: true,
        name: data.name,
        action: data.action, // 'entry' | 'exit'
        survey_result: data.survey_result,
        message: data.message,
      }

      // TTS 음성 안내
      speakMessage(data.tts_message || `${data.name}님, ${data.action === 'entry' ? '입소' : '퇴소'} 처리되었습니다.`)

      // 퇴소 모드가 confirm이고 퇴소 스캔인 경우
      if (exitMode.value === 'confirm' && data.action === 'exit') {
        pendingExit.value = data
        showConfirmDialog.value = true
      }
    } else {
      scanResult.value = {
        success: false,
        message: response.data.error?.message || '스캔 처리에 실패했습니다.',
      }
      speakMessage('스캔 처리에 실패했습니다.')
    }
  } catch (e) {
    scanResult.value = {
      success: false,
      message: e.response?.data?.error?.message || '스캔 처리에 실패했습니다.',
    }
    speakMessage('스캔 처리에 실패했습니다.')
  }

  // 3초 후 결과 초기화
  scanTimeout = setTimeout(() => {
    scanResult.value = null
    scanning.value = true
  }, 3000)
}

async function confirmExit() {
  if (!pendingExit.value) return

  try {
    await api.post('/kiosk/confirm-exit', {
      uuid: pendingExit.value.uuid,
    })
    speakMessage(`${pendingExit.value.name}님, 퇴소 처리되었습니다.`)
  } catch (e) {
    speakMessage('퇴소 처리에 실패했습니다.')
  }

  showConfirmDialog.value = false
  pendingExit.value = null
}

function cancelExit() {
  showConfirmDialog.value = false
  pendingExit.value = null
  speakMessage('퇴소가 취소되었습니다.')
}

function speakMessage(message) {
  if ('speechSynthesis' in window) {
    const utterance = new SpeechSynthesisUtterance(message)
    utterance.lang = 'ko-KR'
    utterance.rate = 0.9
    speechSynthesis.speak(utterance)
  }
}

function goToSetup() {
  router.push({ name: 'KioskSetup' })
}

// 수동 QR 입력 (테스트용)
const manualUUID = ref('')
function manualScan() {
  if (manualUUID.value) {
    handleQRScan(manualUUID.value)
    manualUUID.value = ''
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-900 text-white flex flex-col">
    <!-- Header -->
    <header class="bg-black/50 py-4 px-6 flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold">{{ trainingInfo?.name || '훈련' }}</h1>
        <p class="text-gray-400 text-sm">{{ formattedDate }}</p>
      </div>
      <div class="text-right">
        <p class="text-3xl font-bold font-mono">{{ formattedTime }}</p>
        <button @click="goToSetup" class="text-gray-400 text-sm hover:text-white">
          설정
        </button>
      </div>
    </header>

    <!-- Main Scanner Area -->
    <main class="flex-1 flex flex-col items-center justify-center px-8">
      <!-- Scanning State -->
      <div v-if="scanning && !scanResult" class="text-center">
        <div class="w-64 h-64 border-4 border-dashed border-gray-600 rounded-lg flex items-center justify-center mb-6">
          <div class="text-center">
            <svg class="w-16 h-16 text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
            <p class="text-gray-400 text-lg">QR 코드를 스캔해주세요</p>
          </div>
        </div>

        <!-- Manual Input (for testing) -->
        <div class="flex gap-2">
          <input
            v-model="manualUUID"
            type="text"
            placeholder="UUID 직접 입력 (테스트)"
            class="px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white text-sm"
          />
          <button
            @click="manualScan"
            class="px-4 py-2 bg-military-600 rounded-lg text-sm hover:bg-military-700"
          >
            스캔
          </button>
        </div>
      </div>

      <!-- Scan Result -->
      <div v-if="scanResult" class="text-center">
        <div
          :class="[
            'w-64 h-64 rounded-lg flex items-center justify-center mb-6',
            scanResult.success ? 'bg-green-500' : 'bg-red-500'
          ]"
        >
          <div class="text-center">
            <svg v-if="scanResult.success" class="w-20 h-20 text-white mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
            <svg v-else class="w-20 h-20 text-white mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <p class="text-white text-2xl font-bold">
              {{ scanResult.success ? scanResult.name : '오류' }}
            </p>
          </div>
        </div>

        <p class="text-xl">{{ scanResult.message }}</p>

        <!-- Result Badge -->
        <div v-if="scanResult.success && scanResult.survey_result" class="mt-4">
          <span
            :class="[
              'inline-block px-6 py-2 rounded-full text-lg font-bold',
              scanResult.survey_result === 'NORMAL' ? 'bg-green-500' :
              scanResult.survey_result === 'CAUTION' ? 'bg-yellow-500' : 'bg-red-500'
            ]"
          >
            {{ scanResult.survey_result === 'NORMAL' ? '정상' :
               scanResult.survey_result === 'CAUTION' ? '주의' : '위험' }}
          </span>
        </div>
      </div>
    </main>

    <!-- Confirm Dialog -->
    <div
      v-if="showConfirmDialog"
      class="fixed inset-0 bg-black/80 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 text-gray-800">
        <h2 class="text-2xl font-bold mb-4">퇴소 확인</h2>
        <p class="text-lg mb-6">
          <span class="font-bold">{{ pendingExit?.name }}</span>님의 퇴소를 처리하시겠습니까?
        </p>
        <div class="flex gap-4">
          <button
            @click="cancelExit"
            class="flex-1 py-4 border-2 border-gray-300 rounded-xl font-bold text-lg hover:bg-gray-100"
          >
            취소
          </button>
          <button
            @click="confirmExit"
            class="flex-1 py-4 bg-military-600 text-white rounded-xl font-bold text-lg hover:bg-military-700"
          >
            확인
          </button>
        </div>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error" class="fixed bottom-4 left-4 right-4 bg-red-500 text-white p-4 rounded-lg text-center">
      {{ error }}
    </div>
  </div>
</template>
