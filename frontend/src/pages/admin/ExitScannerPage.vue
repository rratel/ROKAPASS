<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'
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

// 카메라 스캔 관련
const scanMode = ref('manual') // 'camera' | 'manual' - 기본은 수동
const cameraScanning = ref(false)
const cameraLoading = ref(false) // 카메라 로딩 중
const cameras = ref([])
const selectedCameraId = ref(null)
const cameraError = ref(null)
const cameraAvailable = ref(false)
let html5QrCode = null

// 토스트 알림
const toasts = ref([])

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
  await getCameras()
  timeInterval = setInterval(() => {
    currentTime.value = new Date()
  }, 1000)
})

onUnmounted(async () => {
  if (timeInterval) clearInterval(timeInterval)
  if (scanTimeout) clearTimeout(scanTimeout)
  await stopCameraScanning()
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

async function startScanning() {
  if (!selectedTrainingId.value) return
  scanning.value = true
  // 카메라 모드면 자동 시작
  if (scanMode.value === 'camera') {
    await startCameraScanning()
  }
}

async function stopScanning() {
  await stopCameraScanning()
  scanning.value = false
}

async function handleQRScan(uuid) {
  if (lastScan.value?.uuid === uuid && Date.now() - lastScan.value.time < 3000) {
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
        // 확인 모드: 확인 다이얼로그 표시
        pendingExit.value = data
        showConfirmDialog.value = true
      } else {
        // 자동 모드: 바로 퇴소 처리 + 토스트 알림
        try {
          await api.post('/admin/confirm-exit', { uuid: uuid })
          showToast(`${data.name}님 퇴소 완료`, 'success')
          speakMessage(`${data.name}님, 퇴소 처리되었습니다.`)
          // 진동 피드백
          if (navigator.vibrate) {
            navigator.vibrate([100, 50, 100])
          }
        } catch (exitErr) {
          const errorMsg = exitErr.response?.data?.error?.message || '퇴소 처리 실패'
          showToast(errorMsg, 'error')
          speakErrorMessage(errorMsg)
        }
      }
    } else {
      const errorMsg = response.data.error?.message || '스캔 처리 실패'
      showToast(errorMsg, 'error')
      speakErrorMessage(errorMsg)
    }
  } catch (e) {
    const errorMsg = e.response?.data?.error?.message || '서버 연결 오류'
    showToast(errorMsg, 'error')
    speakErrorMessage(errorMsg)
  }
}

// 에러 메시지 음성 안내
function speakErrorMessage(message) {
  if (message.includes('입소하지 않은')) {
    speakMessage('아직 입소하지 않은 인원입니다.')
  } else if (message.includes('이미 퇴소')) {
    speakMessage('이미 퇴소 처리된 인원입니다.')
  } else if (message.includes('등록된 QR')) {
    speakMessage('등록되지 않은 QR 코드입니다.')
  } else {
    speakMessage('처리에 실패했습니다.')
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

// 토스트 알림 추가
function showToast(message, type = 'success') {
  const id = Date.now()
  toasts.value.push({ id, message, type })

  // 3초 후 자동 제거
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }, 3000)
}

function speakMessage(message) {
  if (!('speechSynthesis' in window)) {
    console.warn('TTS not supported')
    return
  }

  // 이전 음성 취소 (중복 방지)
  speechSynthesis.cancel()

  const utterance = new SpeechSynthesisUtterance(message)
  utterance.lang = 'ko-KR'
  utterance.rate = 0.9

  // 한국어 음성 선택 시도
  const voices = speechSynthesis.getVoices()
  const koreanVoice = voices.find(v => v.lang.includes('ko'))
  if (koreanVoice) {
    utterance.voice = koreanVoice
  }

  // 디버그 로깅
  console.log('TTS:', message)

  utterance.onerror = (e) => {
    console.error('TTS error:', e)
  }

  speechSynthesis.speak(utterance)
}

// 수동 QR 입력
const manualUUID = ref('')
function manualScan() {
  if (manualUUID.value && selectedTrainingId.value) {
    handleQRScan(manualUUID.value)
    manualUUID.value = ''
  }
}

// 카메라 목록 가져오기
async function getCameras() {
  try {
    const devices = await Html5Qrcode.getCameras()
    cameras.value = devices
    if (devices.length > 0) {
      cameraAvailable.value = true
      // 후면 카메라 우선 선택
      const backCamera = devices.find(d =>
        d.label.toLowerCase().includes('back') ||
        d.label.toLowerCase().includes('rear') ||
        d.label.toLowerCase().includes('environment')
      )
      selectedCameraId.value = backCamera?.id || devices[0].id
      cameraError.value = null
    } else {
      cameraAvailable.value = false
      cameraError.value = null // 카메라가 없어도 에러는 아님
    }
  } catch (err) {
    cameraAvailable.value = false
    cameras.value = []
    // NotFoundError는 카메라가 없는 것이므로 에러 메시지 표시하지 않음
    if (err.name === 'NotFoundError' || err.message?.includes('Requested device not found')) {
      cameraError.value = null
      console.log('No camera found on this device')
    } else {
      cameraError.value = '카메라 접근 권한이 필요합니다.'
      console.error('Failed to get cameras:', err)
    }
  }
}

// 카메라 스캔 시작
async function startCameraScanning() {
  if (!selectedTrainingId.value) return
  if (cameraScanning.value) return
  if (cameraLoading.value) return

  // 카메라 모드로 전환
  scanMode.value = 'camera'
  cameraLoading.value = true
  cameraError.value = null

  // DOM이 렌더링될 때까지 대기
  await nextTick()

  // 약간의 지연 추가 (DOM 완전히 렌더링 대기)
  await new Promise(resolve => setTimeout(resolve, 100))

  // DOM 요소 확인
  const qrReaderElement = document.getElementById('qr-reader')
  if (!qrReaderElement) {
    console.warn('QR reader element not found, retrying...')
    cameraLoading.value = false
    setTimeout(() => startCameraScanning(), 200)
    return
  }

  try {
    // 카메라 목록이 없으면 다시 시도
    if (cameras.value.length === 0) {
      try {
        const devices = await Html5Qrcode.getCameras()
        cameras.value = devices
        if (devices.length > 0) {
          cameraAvailable.value = true
          const backCamera = devices.find(d =>
            d.label.toLowerCase().includes('back') ||
            d.label.toLowerCase().includes('rear') ||
            d.label.toLowerCase().includes('environment')
          )
          selectedCameraId.value = backCamera?.id || devices[0].id
        }
      } catch (e) {
        console.log('Could not enumerate cameras, will try direct access')
      }
    }

    html5QrCode = new Html5Qrcode('qr-reader')

    const config = {
      fps: 10,
      qrbox: { width: 200, height: 200 },
      aspectRatio: 1.0
    }

    // 카메라 ID가 있으면 사용, 없으면 후면 카메라 시도
    const cameraIdOrConfig = selectedCameraId.value || { facingMode: 'environment' }

    console.log('Starting camera with:', cameraIdOrConfig)

    await html5QrCode.start(
      cameraIdOrConfig,
      config,
      onQrCodeSuccess,
      () => {} // onScanError - 무시 (계속 스캔)
    )

    console.log('Camera started successfully')
    cameraScanning.value = true
    cameraAvailable.value = true
    cameraError.value = null
  } catch (err) {
    console.error('Failed to start camera:', err)
    // 권한 거부 또는 카메라 없음
    if (err.name === 'NotAllowedError') {
      cameraError.value = '카메라 권한을 허용해주세요.'
    } else if (err.name === 'NotFoundError') {
      cameraError.value = '사용 가능한 카메라가 없습니다.'
      cameraAvailable.value = false
    } else {
      cameraError.value = '카메라 오류: ' + (err.message || '알 수 없는 오류')
    }
    // 카메라 실패 시 수동 모드로 전환
    scanMode.value = 'manual'
  } finally {
    cameraLoading.value = false
  }
}

// 카메라 스캔 중지
async function stopCameraScanning() {
  if (!cameraScanning.value || !html5QrCode) return

  try {
    await html5QrCode.stop()
    html5QrCode.clear()
    cameraScanning.value = false
  } catch (err) {
    console.error('Failed to stop camera:', err)
  }
}

// QR 코드 인식 성공
function onQrCodeSuccess(decodedText) {
  // 중복 스캔 방지 (3초)
  if (lastScan.value?.uuid === decodedText &&
      Date.now() - lastScan.value.time < 3000) {
    return
  }

  // 진동 피드백 (모바일)
  if (navigator.vibrate) {
    navigator.vibrate(200)
  }

  // 스캔 처리
  handleQRScan(decodedText)
}

// 카메라 전환
async function switchCamera() {
  if (cameras.value.length <= 1) return

  const currentIndex = cameras.value.findIndex(c => c.id === selectedCameraId.value)
  const nextIndex = (currentIndex + 1) % cameras.value.length
  selectedCameraId.value = cameras.value[nextIndex].id

  if (cameraScanning.value) {
    await stopCameraScanning()
    await startCameraScanning()
  }
}

// 스캔 모드 전환
async function setScanMode(mode) {
  if (mode === scanMode.value) return

  if (mode === 'manual' && cameraScanning.value) {
    await stopCameraScanning()
  }
  scanMode.value = mode
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
          <!-- Scan Mode Toggle -->
          <div class="flex justify-center gap-2 mb-6">
            <button
              @click="startCameraScanning"
              :class="[
                'flex items-center gap-2 px-5 py-2.5 rounded-xl font-medium transition-all duration-200',
                scanMode === 'camera'
                  ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30'
                  : 'bg-slate-700 text-slate-300 hover:bg-slate-600'
              ]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              카메라 스캔
            </button>
            <button
              @click="setScanMode('manual')"
              :class="[
                'flex items-center gap-2 px-5 py-2.5 rounded-xl font-medium transition-all duration-200',
                scanMode === 'manual'
                  ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30'
                  : 'bg-slate-700 text-slate-300 hover:bg-slate-600'
              ]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              수동 입력
            </button>
          </div>

          <!-- Camera Error -->
          <div v-if="cameraError" class="bg-red-500/20 border border-red-500/50 rounded-xl p-4 mb-6 text-center">
            <p class="text-red-300">{{ cameraError }}</p>
            <button @click="cameraError = null; startCameraScanning()" class="mt-2 text-sm text-red-400 hover:text-red-300 underline">
              다시 시도
            </button>
          </div>

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

          <!-- Camera Scanning View -->
          <div v-if="!scanResult && scanMode === 'camera'" class="text-center">
            <!-- QR Reader Container (항상 표시, 로딩 오버레이로 덮음) -->
            <div class="relative inline-block">
              <div id="qr-reader" class="qr-reader-container mx-auto rounded-2xl overflow-hidden"></div>

              <!-- Loading Overlay -->
              <div v-if="cameraLoading && !cameraScanning" class="absolute inset-0 bg-slate-800 rounded-2xl flex items-center justify-center z-20">
                <div class="text-center">
                  <svg class="w-12 h-12 text-emerald-500 mx-auto animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <p class="text-emerald-400 text-sm mt-3 font-medium">카메라 시작 중...</p>
                </div>
              </div>

              <!-- Camera Switch Button -->
              <button
                v-if="cameras.length > 1 && cameraScanning"
                @click="switchCamera"
                class="absolute top-3 right-3 p-2.5 bg-black/50 backdrop-blur rounded-xl hover:bg-black/70 transition-colors z-10"
                title="카메라 전환"
              >
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
              </button>

              <!-- Scanning Indicator -->
              <div v-if="cameraScanning" class="absolute bottom-3 left-1/2 -translate-x-1/2 z-10">
                <div class="flex items-center gap-2 bg-black/60 backdrop-blur px-4 py-2 rounded-full">
                  <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                  </span>
                  <span class="text-white text-sm font-medium">QR코드를 스캔하세요</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Manual Input Mode -->
          <div v-if="!scanResult && scanMode === 'manual'" class="text-center py-8">
            <div class="w-56 h-56 border-4 border-dashed border-slate-600 rounded-2xl flex items-center justify-center mx-auto mb-8 relative">
              <div class="absolute inset-4 border-2 border-emerald-500/30 rounded-xl"></div>
              <svg class="w-20 h-20 text-slate-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
              </svg>
            </div>
            <p class="text-xl text-slate-400 mb-2">UUID를 직접 입력해주세요</p>
            <p class="text-sm text-slate-500">아래 입력창에 UUID를 입력하고 스캔 버튼을 누르세요</p>
          </div>

          <!-- Manual Input (항상 표시) -->
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

    <!-- Toast Notifications -->
    <div class="fixed top-4 right-4 z-50 space-y-2">
      <TransitionGroup
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-x-8"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 translate-x-8"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg min-w-[280px]',
            toast.type === 'success'
              ? 'bg-emerald-600 text-white'
              : 'bg-red-600 text-white'
          ]"
        >
          <div class="flex-shrink-0">
            <svg v-if="toast.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </div>
          <span class="font-medium">{{ toast.message }}</span>
        </div>
      </TransitionGroup>
    </div>
  </AdminLayout>
</template>

<style>
/* QR Reader 커스텀 스타일 */
.qr-reader-container {
  width: 300px;
  height: 300px;
  margin: 0 auto;
}

#qr-reader {
  width: 100% !important;
  height: 100% !important;
  border: none !important;
  background: #1e293b !important;
  border-radius: 16px !important;
  overflow: hidden !important;
}

#qr-reader video {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
  border-radius: 16px !important;
}

#qr-reader__scan_region {
  border-radius: 16px;
}

#qr-reader__scan_region video {
  border-radius: 16px !important;
}

/* html5-qrcode 기본 UI 숨기기 */
#qr-reader__dashboard,
#qr-reader__status_span,
#qr-reader__header_message,
#qr-reader__dashboard_section,
#qr-reader__dashboard_section_csr,
#qr-reader__dashboard_section_swaplink {
  display: none !important;
}
</style>
