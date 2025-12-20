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
      color: 'emerald',
      bgColor: 'bg-emerald-50',
      textColor: 'text-emerald-700',
      borderColor: 'border-emerald-200',
      iconBg: 'bg-emerald-500',
      ringColor: 'ring-emerald-500/20',
      message: '정상적으로 훈련에 참가할 수 있습니다.',
    }
  } else if (result === 'CAUTION') {
    return {
      status: '주의',
      color: 'amber',
      bgColor: 'bg-amber-50',
      textColor: 'text-amber-700',
      borderColor: 'border-amber-200',
      iconBg: 'bg-amber-500',
      ringColor: 'ring-amber-500/20',
      message: '훈련 중 건강 상태에 주의가 필요합니다.',
    }
  } else {
    return {
      status: '위험',
      color: 'red',
      bgColor: 'bg-red-50',
      textColor: 'text-red-700',
      borderColor: 'border-red-200',
      iconBg: 'bg-red-500',
      ringColor: 'ring-red-500/20',
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
  ctx.value.strokeStyle = '#1e293b'
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
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- Header -->
    <header class="bg-gradient-to-r from-slate-900 to-slate-800 text-white py-5 px-4 sticky top-0 z-10">
      <div class="max-w-lg mx-auto">
        <div class="text-center">
          <h1 class="text-lg font-bold">문진 결과</h1>
          <p class="text-sm text-slate-400">3단계</p>
        </div>
      </div>
    </header>

    <!-- Progress -->
    <div class="bg-white border-b border-slate-100">
      <div class="max-w-lg mx-auto px-4 py-3">
        <div class="flex items-center gap-2">
          <div class="flex-1 h-1.5 bg-emerald-500 rounded-full"></div>
          <div class="flex-1 h-1.5 bg-emerald-500 rounded-full"></div>
          <div class="flex-1 h-1.5 bg-emerald-500 rounded-full"></div>
        </div>
      </div>
    </div>

    <!-- Result -->
    <main class="flex-1 max-w-lg mx-auto w-full px-4 py-6">
      <!-- Result Card -->
      <div
        :class="[
          'rounded-2xl p-6 border-2 mb-6 ring-4 transition-all duration-300',
          resultInfo.bgColor,
          resultInfo.borderColor,
          resultInfo.ringColor
        ]"
      >
        <div class="text-center">
          <div
            :class="[
              'inline-flex items-center justify-center w-20 h-20 rounded-2xl mb-4 shadow-lg',
              resultInfo.iconBg
            ]"
          >
            <svg v-if="resultInfo.color === 'emerald'" class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
            <svg v-else-if="resultInfo.color === 'amber'" class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <svg v-else class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </div>

          <h2 :class="['text-3xl font-bold mb-2', resultInfo.textColor]">
            {{ resultInfo.status }}
          </h2>
          <p :class="['text-sm leading-relaxed', resultInfo.textColor]">
            {{ resultInfo.message }}
          </p>
        </div>
      </div>

      <!-- Personal Info Summary -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 mb-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <h3 class="font-semibold text-slate-900">입력 정보 확인</h3>
        </div>
        <dl class="space-y-3">
          <div class="flex justify-between items-center py-2 border-b border-slate-100">
            <dt class="text-slate-500">이름</dt>
            <dd class="font-semibold text-slate-900">{{ surveyStore.personalInfo.name }}</dd>
          </div>
          <div class="flex justify-between items-center py-2">
            <dt class="text-slate-500">중식 신청</dt>
            <dd>
              <span v-if="surveyStore.personalInfo.lunch_yn" class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-sm font-medium">
                신청완료
              </span>
              <span v-else class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-sm font-medium">
                미신청
              </span>
            </dd>
          </div>
        </dl>
      </div>

      <!-- Signature Pad -->
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 mb-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
              </svg>
            </div>
            <h3 class="font-semibold text-slate-900">서명</h3>
          </div>
          <button
            @click="clearSignature"
            class="text-sm text-slate-500 hover:text-slate-700 flex items-center gap-1 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            지우기
          </button>
        </div>

        <div
          ref="signaturePad"
          class="border-2 border-dashed border-slate-200 rounded-xl bg-slate-50 relative overflow-hidden transition-all duration-200 hover:border-emerald-300"
          style="height: 160px;"
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
            class="absolute inset-0 flex items-center justify-center text-slate-400 pointer-events-none"
          >
            <span class="flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
              </svg>
              여기에 서명해주세요
            </span>
          </p>
        </div>
      </div>

      <!-- Error -->
      <div v-if="error" class="flex items-center gap-3 p-4 bg-red-50 border border-red-100 rounded-xl mb-6">
        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="text-sm text-red-600">{{ error }}</span>
      </div>

      <!-- Submit Button -->
      <button
        @click="submitSignature"
        :disabled="!hasSignature || submitting"
        :class="[
          'w-full py-4 rounded-2xl font-bold text-lg transition-all duration-200 flex items-center justify-center gap-2',
          hasSignature && !submitting
            ? 'bg-slate-900 text-white hover:bg-slate-800 shadow-lg shadow-slate-900/20'
            : 'bg-slate-200 text-slate-400 cursor-not-allowed'
        ]"
      >
        <span v-if="submitting" class="flex items-center gap-2">
          <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
          </svg>
          처리 중...
        </span>
        <span v-else class="flex items-center gap-2">
          QR 코드 발급
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
          </svg>
        </span>
      </button>
    </main>
  </div>
</template>
