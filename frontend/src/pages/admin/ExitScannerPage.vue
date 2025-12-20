<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import AdminLayout from '@/components/admin/AdminLayout.vue'
import api from '@/services/api'

const trainings = ref([])
const selectedTrainingId = ref('')
const exitMode = ref('auto')
const scanning = ref(false)
const lastScan = ref(null)
const scanResult = ref(null)
const showConfirmDialog = ref(false)
const pendingExit = ref(null)
const currentTime = ref(new Date())

let timeInterval = null
let scanTimeout = null

const formattedTime = computed(() => {
  return currentTime.value.toLocaleTimeString('ko-KR', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
})

onMounted(async () => {
  await fetchTrainings()
  timeInterval = setInterval(() => {
    currentTime.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  if (timeInterval) clearInterval(timeInterval)
  if (scanTimeout) clearTimeout(scanTimeout)
})

async function fetchTrainings() {
  try {
    const response = await api.get('/admin/trainings', {
      params: { status: 'active' }
    })
    if (response.data.success) {
      trainings.value = response.data.data
      if (trainings.value.length === 1) {
        selectedTrainingId.value = trainings.value[0].id
        exitMode.value = trainings.value[0].exit_mode
      }
    }
  } catch (e) {
    console.error('Failed to fetch trainings:', e)
  }
}

function onTrainingChange() {
  const training = trainings.value.find(t => t.id === parseInt(selectedTrainingId.value))
  if (training) {
    exitMode.value = training.exit_mode
  }
}

function startScanning() {
  if (!selectedTrainingId.value) return
  scanning.value = true
}

function stopScanning() {
  scanning.value = false
}

async function handleQRScan(uuid) {
  if (lastScan.value === uuid && Date.now() - lastScan.value.time < 3000) {
    return
  }

  lastScan.value = { uuid, time: Date.now() }

  try {
    const response = await api.post('/admin/exit-scan', {
      training_id: selectedTrainingId.value,
      uuid: uuid,
    })

    if (response.data.success) {
      const data = response.data.data

      if (exitMode.value === 'confirm') {
        pendingExit.value = data
        showConfirmDialog.value = true
      } else {
        scanResult.value = {
          success: true,
          name: data.name,
          message: '퇴소 처리 완료',
        }
        speakMessage(`${data.name}님, 퇴소 처리되었습니다.`)
        clearResultAfterDelay()
      }
    } else {
      scanResult.value = {
        success: false,
        message: response.data.error?.message || '스캔 처리 실패',
      }
      speakMessage('스캔 처리에 실패했습니다.')
      clearResultAfterDelay()
    }
  } catch (e) {
    scanResult.value = {
      success: false,
      message: e.response?.data?.error?.message || '스캔 처리 실패',
    }
    speakMessage('스캔 처리에 실패했습니다.')
    clearResultAfterDelay()
  }
}

async function confirmExit() {
  if (!pendingExit.value) return

  try {
    await api.post('/admin/confirm-exit', {
      uuid: pendingExit.value.uuid,
    })
    scanResult.value = {
      success: true,
      name: pendingExit.value.name,
      message: '퇴소 처리 완료',
    }
    speakMessage(`${pendingExit.value.name}님, 퇴소 처리되었습니다.`)
  } catch (e) {
    scanResult.value = {
      success: false,
      message: '퇴소 처리 실패',
    }
    speakMessage('퇴소 처리에 실패했습니다.')
  }

  showConfirmDialog.value = false
  pendingExit.value = null
  clearResultAfterDelay()
}

function cancelExit() {
  showConfirmDialog.value = false
  pendingExit.value = null
  speakMessage('퇴소가 취소되었습니다.')
}

function clearResultAfterDelay() {
  scanTimeout = setTimeout(() => {
    scanResult.value = null
  }, 3000)
}

function speakMessage(message) {
  if ('speechSynthesis' in window) {
    const utterance = new SpeechSynthesisUtterance(message)
    utterance.lang = 'ko-KR'
    utterance.rate = 0.9
    speechSynthesis.speak(utterance)
  }
}

// 수동 QR 입력
const manualUUID = ref('')
function manualScan() {
  if (manualUUID.value && selectedTrainingId.value) {
    handleQRScan(manualUUID.value)
    manualUUID.value = ''
  }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">퇴소 스캐너</h1>
          <p class="text-gray-500 mt-1">QR 코드를 스캔하여 퇴소 처리합니다.</p>
        </div>
        <div class="text-3xl font-bold font-mono text-gray-800">
          {{ formattedTime }}
        </div>
      </div>

      <!-- Training Selection -->
      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">훈련 선택</label>
            <select
              v-model="selectedTrainingId"
              @change="onTrainingChange"
              class="input-field"
            >
              <option value="">훈련을 선택하세요</option>
              <option v-for="training in trainings" :key="training.id" :value="training.id">
                {{ training.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">퇴소 모드</label>
            <div class="flex gap-4">
              <label class="flex items-center">
                <input
                  v-model="exitMode"
                  type="radio"
                  value="auto"
                  class="mr-2"
                />
                <span class="text-sm">자동</span>
              </label>
              <label class="flex items-center">
                <input
                  v-model="exitMode"
                  type="radio"
                  value="confirm"
                  class="mr-2"
                />
                <span class="text-sm">확인</span>
              </label>
            </div>
          </div>

          <div>
            <button
              v-if="!scanning"
              @click="startScanning"
              :disabled="!selectedTrainingId"
              class="btn-primary w-full"
            >
              스캔 시작
            </button>
            <button
              v-else
              @click="stopScanning"
              class="btn-secondary w-full"
            >
              스캔 중지
            </button>
          </div>
        </div>
      </div>

      <!-- Scanner Area -->
      <div v-if="scanning" class="bg-gray-900 rounded-xl p-8">
        <div class="max-w-md mx-auto">
          <!-- Scan Result -->
          <div v-if="scanResult" class="text-center mb-8">
            <div
              :class="[
                'w-32 h-32 rounded-full flex items-center justify-center mx-auto mb-4',
                scanResult.success ? 'bg-green-500' : 'bg-red-500'
              ]"
            >
              <svg v-if="scanResult.success" class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
              </svg>
              <svg v-else class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </div>
            <p class="text-2xl font-bold text-white">{{ scanResult.name || '' }}</p>
            <p class="text-gray-300 mt-2">{{ scanResult.message }}</p>
          </div>

          <!-- Scanning State -->
          <div v-else class="text-center">
            <div class="w-48 h-48 border-4 border-dashed border-gray-600 rounded-lg flex items-center justify-center mx-auto mb-6">
              <svg class="w-16 h-16 text-gray-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
              </svg>
            </div>
            <p class="text-xl text-gray-400">QR 코드를 스캔해주세요</p>
          </div>

          <!-- Manual Input -->
          <div class="mt-8 flex gap-2">
            <input
              v-model="manualUUID"
              type="text"
              placeholder="UUID 직접 입력"
              class="flex-1 px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white"
              @keyup.enter="manualScan"
            />
            <button @click="manualScan" class="btn-primary px-6">
              스캔
            </button>
          </div>
        </div>
      </div>

      <!-- No Training Selected -->
      <div v-else class="bg-gray-100 rounded-xl p-12 text-center">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
        </svg>
        <p class="text-gray-500">훈련을 선택하고 스캔을 시작하세요</p>
      </div>
    </div>

    <!-- Confirm Dialog -->
    <div
      v-if="showConfirmDialog"
      class="fixed inset-0 bg-black/80 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">퇴소 확인</h2>
        <p class="text-lg text-gray-600 mb-6">
          <span class="font-bold text-gray-800">{{ pendingExit?.name }}</span>님의 퇴소를 처리하시겠습니까?
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
  </AdminLayout>
</template>
