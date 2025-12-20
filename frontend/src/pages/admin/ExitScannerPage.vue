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

const formattedDate = computed(() => {
  return currentTime.value.toLocaleDateString('ko-KR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    weekday: 'long',
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
    <div class="space-y-8">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-slate-900">퇴소 스캐너</h2>
          <p class="text-slate-500 mt-1">QR 코드를 스캔하여 퇴소 처리합니다.</p>
        </div>
        <div class="text-right">
          <div class="text-4xl font-bold font-mono text-slate-900 tracking-tight">
            {{ formattedTime }}
          </div>
          <div class="text-sm text-slate-500 mt-1">{{ formattedDate }}</div>
        </div>
      </div>

      <!-- Training Selection Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
          <!-- Training Select -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">훈련 선택</label>
            <div class="relative">
              <select
                v-model="selectedTrainingId"
                @change="onTrainingChange"
                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200 appearance-none cursor-pointer"
              >
                <option value="">훈련을 선택하세요</option>
                <option v-for="training in trainings" :key="training.id" :value="training.id">
                  {{ training.name }}
                </option>
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </div>
            </div>
          </div>

          <!-- Exit Mode -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">퇴소 모드</label>
            <div class="flex gap-4 p-1 bg-slate-100 rounded-xl">
              <label
                :class="[
                  'flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg cursor-pointer transition-all duration-200',
                  exitMode === 'auto'
                    ? 'bg-white text-slate-900 shadow-sm font-medium'
                    : 'text-slate-500 hover:text-slate-700'
                ]"
              >
                <input
                  v-model="exitMode"
                  type="radio"
                  value="auto"
                  class="sr-only"
                />
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span class="text-sm">자동</span>
              </label>
              <label
                :class="[
                  'flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg cursor-pointer transition-all duration-200',
                  exitMode === 'confirm'
                    ? 'bg-white text-slate-900 shadow-sm font-medium'
                    : 'text-slate-500 hover:text-slate-700'
                ]"
              >
                <input
                  v-model="exitMode"
                  type="radio"
                  value="confirm"
                  class="sr-only"
                />
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm">확인</span>
              </label>
            </div>
          </div>

          <!-- Scan Button -->
          <div>
            <button
              v-if="!scanning"
              @click="startScanning"
              :disabled="!selectedTrainingId"
              class="w-full py-3.5 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/30"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
              </svg>
              스캔 시작
            </button>
            <button
              v-else
              @click="stopScanning"
              class="w-full py-3.5 bg-slate-900 text-white rounded-xl font-semibold hover:bg-slate-800 transition-all duration-200 flex items-center justify-center gap-2"
            >
              <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
              </span>
              스캔 중지
            </button>
          </div>
        </div>
      </div>

      <!-- Scanner Area -->
      <div v-if="scanning" class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-2xl p-8 min-h-[400px]">
        <div class="max-w-lg mx-auto">
          <!-- Scan Result -->
          <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-90"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-90"
          >
            <div v-if="scanResult" class="text-center py-8">
              <div
                :class="[
                  'w-32 h-32 rounded-full flex items-center justify-center mx-auto mb-6 shadow-2xl',
                  scanResult.success
                    ? 'bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-emerald-500/40'
                    : 'bg-gradient-to-br from-red-400 to-red-600 shadow-red-500/40'
                ]"
              >
                <svg v-if="scanResult.success" class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
                <svg v-else class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </div>
              <p class="text-3xl font-bold text-white mb-2">{{ scanResult.name || '' }}</p>
              <p class="text-slate-400 text-lg">{{ scanResult.message }}</p>
            </div>
          </Transition>

          <!-- Scanning State -->
          <div v-if="!scanResult" class="text-center py-8">
            <div class="w-56 h-56 border-4 border-dashed border-slate-600 rounded-2xl flex items-center justify-center mx-auto mb-8 relative">
              <div class="absolute inset-4 border-2 border-emerald-500/30 rounded-xl"></div>
              <svg class="w-20 h-20 text-slate-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
              </svg>
            </div>
            <p class="text-xl text-slate-400 mb-2">QR 코드를 스캔해주세요</p>
            <p class="text-sm text-slate-500">스캐너에 QR 코드를 대면 자동으로 인식됩니다</p>
          </div>

          <!-- Manual Input -->
          <div class="mt-8 flex gap-3">
            <input
              v-model="manualUUID"
              type="text"
              placeholder="UUID 직접 입력"
              class="flex-1 px-4 py-3.5 bg-slate-800 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
              @keyup.enter="manualScan"
            />
            <button
              @click="manualScan"
              class="px-6 py-3.5 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 transition-colors"
            >
              스캔
            </button>
          </div>
        </div>
      </div>

      <!-- No Training Selected -->
      <div v-else class="bg-slate-50 rounded-2xl p-16 text-center border border-slate-100">
        <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-sm border border-slate-100">
          <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
          </svg>
        </div>
        <p class="text-slate-600 text-lg font-medium mb-2">훈련을 선택해주세요</p>
        <p class="text-slate-400">훈련을 선택하고 스캔을 시작하면 QR 코드 퇴소 처리가 가능합니다.</p>
      </div>
    </div>

    <!-- Confirm Dialog -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="showConfirmDialog"
        class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4"
      >
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="showConfirmDialog" class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
            <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6">
              <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 text-center mb-3">퇴소 확인</h2>
            <p class="text-slate-600 text-center text-lg mb-8">
              <span class="font-bold text-slate-900">{{ pendingExit?.name }}</span>님의 퇴소를 처리하시겠습니까?
            </p>
            <div class="flex gap-4">
              <button
                @click="cancelExit"
                class="flex-1 py-4 border-2 border-slate-200 text-slate-700 rounded-xl font-semibold text-lg hover:bg-slate-50 transition-colors"
              >
                취소
              </button>
              <button
                @click="confirmExit"
                class="flex-1 py-4 bg-emerald-600 text-white rounded-xl font-semibold text-lg hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-600/30"
              >
                확인
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </AdminLayout>
</template>
