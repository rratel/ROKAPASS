<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyStore } from '@/stores/survey'
import api from '@/services/api'

const router = useRouter()
const surveyStore = useSurveyStore()

const signaturePad = ref(null)
const canvas = ref(null)
const ctx = ref(null)
const isDrawing = ref(false)
const hasSignature = ref(false)
const submitting = ref(false)
const error = ref(null)

const resultInfo = computed(() => {
  const result = surveyStore.surveyResult
  if (result === 'NORMAL') {
    return {
      status: '정상',
      color: 'green',
      bgColor: 'bg-green-100',
      textColor: 'text-green-800',
      borderColor: 'border-green-500',
      message: '정상적으로 훈련에 참가할 수 있습니다.',
    }
  } else if (result === 'CAUTION') {
    return {
      status: '주의',
      color: 'yellow',
      bgColor: 'bg-yellow-100',
      textColor: 'text-yellow-800',
      borderColor: 'border-yellow-500',
      message: '훈련 중 건강 상태에 주의가 필요합니다.',
    }
  } else {
    return {
      status: '위험',
      color: 'red',
      bgColor: 'bg-red-100',
      textColor: 'text-red-800',
      borderColor: 'border-red-500',
      message: '건강 상태 확인이 필요합니다. 관리자에게 문의하세요.',
    }
  }
})

onMounted(() => {
  if (!surveyStore.uuid) {
    router.push({ name: 'Landing' })
    return
  }

  initCanvas()
})

function initCanvas() {
  const canvasEl = canvas.value
  if (!canvasEl) return

  ctx.value = canvasEl.getContext('2d')
  ctx.value.strokeStyle = '#000'
  ctx.value.lineWidth = 2
  ctx.value.lineCap = 'round'
  ctx.value.lineJoin = 'round'

  // Set canvas size
  const rect = canvasEl.getBoundingClientRect()
  canvasEl.width = rect.width * 2
  canvasEl.height = rect.height * 2
  ctx.value.scale(2, 2)
}

function getPos(e) {
  const rect = canvas.value.getBoundingClientRect()
  const touch = e.touches ? e.touches[0] : e
  return {
    x: touch.clientX - rect.left,
    y: touch.clientY - rect.top,
  }
}

function startDrawing(e) {
  isDrawing.value = true
  const pos = getPos(e)
  ctx.value.beginPath()
  ctx.value.moveTo(pos.x, pos.y)
}

function draw(e) {
  if (!isDrawing.value) return
  e.preventDefault()
  const pos = getPos(e)
  ctx.value.lineTo(pos.x, pos.y)
  ctx.value.stroke()
  hasSignature.value = true
}

function stopDrawing() {
  isDrawing.value = false
}

function clearSignature() {
  const canvasEl = canvas.value
  ctx.value.clearRect(0, 0, canvasEl.width, canvasEl.height)
  hasSignature.value = false
}

async function submitSignature() {
  if (!hasSignature.value) return

  submitting.value = true
  error.value = null

  try {
    const signatureData = canvas.value.toDataURL('image/png')

    const response = await api.post('/survey/signature', {
      uuid: surveyStore.uuid,
      signature: signatureData,
    })

    if (response.data.success) {
      surveyStore.setSignature(signatureData)
      surveyStore.saveToStorage()
      router.push({ name: 'QR' })
    } else {
      error.value = response.data.error?.message || '서명 저장에 실패했습니다.'
    }
  } catch (e) {
    error.value = e.response?.data?.error?.message || '서명 저장에 실패했습니다.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <header class="bg-military-700 text-white py-4 px-4">
      <div class="max-w-md mx-auto">
        <h1 class="text-lg font-bold text-center">문진 결과</h1>
      </div>
    </header>

    <!-- Result -->
    <main class="flex-1 max-w-md mx-auto w-full px-4 py-6">
      <!-- Result Card -->
      <div
        :class="[
          'rounded-xl p-6 border-2 mb-6',
          resultInfo.bgColor,
          resultInfo.borderColor
        ]"
      >
        <div class="text-center">
          <div
            :class="[
              'inline-flex items-center justify-center w-16 h-16 rounded-full mb-4',
              resultInfo.color === 'green' ? 'bg-green-500' :
              resultInfo.color === 'yellow' ? 'bg-yellow-500' : 'bg-red-500'
            ]"
          >
            <svg v-if="resultInfo.color === 'green'" class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
            <svg v-else-if="resultInfo.color === 'yellow'" class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <svg v-else class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </div>

          <h2 :class="['text-2xl font-bold mb-2', resultInfo.textColor]">
            {{ resultInfo.status }}
          </h2>
          <p :class="['text-sm', resultInfo.textColor]">
            {{ resultInfo.message }}
          </p>
        </div>
      </div>

      <!-- Personal Info Summary -->
      <div class="bg-white rounded-lg p-5 shadow-sm mb-6">
        <h3 class="font-semibold text-gray-800 mb-3">입력 정보 확인</h3>
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between">
            <dt class="text-gray-500">이름</dt>
            <dd class="font-medium text-gray-800">{{ surveyStore.personalInfo.name }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">중식 신청</dt>
            <dd class="font-medium text-gray-800">{{ surveyStore.personalInfo.lunch_yn ? 'O' : 'X' }}</dd>
          </div>
        </dl>
      </div>

      <!-- Signature Pad -->
      <div class="bg-white rounded-lg p-5 shadow-sm mb-6">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-gray-800">서명</h3>
          <button
            @click="clearSignature"
            class="text-sm text-military-600 hover:text-military-700"
          >
            지우기
          </button>
        </div>

        <div
          ref="signaturePad"
          class="border-2 border-gray-300 rounded-lg bg-gray-50 relative overflow-hidden"
          style="height: 150px;"
        >
          <canvas
            ref="canvas"
            class="absolute inset-0 w-full h-full cursor-crosshair touch-none"
            @mousedown="startDrawing"
            @mousemove="draw"
            @mouseup="stopDrawing"
            @mouseleave="stopDrawing"
            @touchstart="startDrawing"
            @touchmove="draw"
            @touchend="stopDrawing"
          ></canvas>

          <p
            v-if="!hasSignature"
            class="absolute inset-0 flex items-center justify-center text-gray-400 pointer-events-none"
          >
            여기에 서명해주세요
          </p>
        </div>
      </div>

      <!-- Error -->
      <p v-if="error" class="text-red-500 text-sm text-center mb-4">{{ error }}</p>

      <!-- Submit Button -->
      <button
        @click="submitSignature"
        :disabled="!hasSignature || submitting"
        :class="[
          'w-full py-4 rounded-xl font-bold text-lg transition-all',
          hasSignature && !submitting
            ? 'bg-military-600 text-white hover:bg-military-700'
            : 'bg-gray-300 text-gray-500 cursor-not-allowed'
        ]"
      >
        <span v-if="submitting" class="flex items-center justify-center">
          <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
          </svg>
          처리 중...
        </span>
        <span v-else>QR 코드 발급</span>
      </button>
    </main>
  </div>
</template>
