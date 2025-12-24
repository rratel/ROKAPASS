<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()

const trainings = ref([])
const selectedTraining = ref(null)
const loading = ref(true)
const error = ref(null)
const saving = ref(false)

onMounted(async () => {
  // 이미 설정된 훈련이 있으면 선택
  const savedTrainingId = localStorage.getItem('kiosk_training_id')
  if (savedTrainingId) {
    selectedTraining.value = parseInt(savedTrainingId)
  }

  await fetchTrainings()
})

async function fetchTrainings() {
  try {
    const response = await api.get('/trainings/active')
    if (response.data.success) {
      trainings.value = response.data.data
    }
  } catch (e) {
    error.value = '훈련 목록을 불러오는데 실패했습니다.'
  } finally {
    loading.value = false
  }
}

function selectTraining(training) {
  selectedTraining.value = training.id
}

function saveAndStart() {
  if (!selectedTraining.value) return

  saving.value = true
  localStorage.setItem('kiosk_training_id', selectedTraining.value.toString())

  setTimeout(() => {
    router.push({ name: 'Kiosk' })
  }, 500)
}

function clearSettings() {
  localStorage.removeItem('kiosk_training_id')
  selectedTraining.value = null
}
</script>

<template>
  <div class="min-h-screen bg-gray-900 text-white flex flex-col">
    <!-- Header -->
    <header class="bg-black/50 py-6 px-6">
      <h1 class="text-2xl font-bold text-center">키오스크 설정</h1>
    </header>

    <!-- Main -->
    <main class="flex-1 flex flex-col items-center justify-center px-6">
      <div class="w-full max-w-lg">
        <!-- Loading -->
        <div v-if="loading" class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto"></div>
          <p class="mt-4">로딩 중...</p>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="bg-red-500/20 text-red-300 p-4 rounded-lg text-center">
          {{ error }}
        </div>

        <!-- No Trainings -->
        <div v-else-if="trainings.length === 0" class="text-center">
          <p class="text-xl text-gray-400">진행 중인 훈련이 없습니다.</p>
        </div>

        <!-- Training Selection -->
        <div v-else class="space-y-6">
          <div class="bg-gray-800 rounded-xl p-6">
            <h2 class="text-lg font-semibold mb-4">훈련 선택</h2>

            <div class="space-y-3">
              <button
                v-for="training in trainings"
                :key="training.id"
                @click="selectTraining(training)"
                :class="[
                  'w-full p-4 rounded-lg border-2 text-left transition-all',
                  selectedTraining === training.id
                    ? 'border-military-500 bg-military-900'
                    : 'border-gray-700 hover:border-gray-600'
                ]"
              >
                <p class="font-medium">{{ training.name }}</p>
                <p class="text-sm text-gray-400 mt-1">
                  <template v-if="training.start_date === training.end_date">
                    {{ new Date(training.start_date).toLocaleDateString('ko-KR') }}
                  </template>
                  <template v-else>
                    {{ new Date(training.start_date).toLocaleDateString('ko-KR') }} ~ {{ new Date(training.end_date).toLocaleDateString('ko-KR') }}
                  </template>
                </p>
                <p class="text-sm text-gray-500 mt-1">
                  퇴소 모드: {{ training.exit_mode === 'auto' ? '자동' : '확인' }}
                </p>
              </button>
            </div>
          </div>

          <!-- Actions -->
          <div class="space-y-3">
            <button
              @click="saveAndStart"
              :disabled="!selectedTraining || saving"
              :class="[
                'w-full py-4 rounded-xl font-bold text-lg transition-all',
                selectedTraining && !saving
                  ? 'bg-military-600 hover:bg-military-700'
                  : 'bg-gray-700 text-gray-500 cursor-not-allowed'
              ]"
            >
              <span v-if="saving" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                저장 중...
              </span>
              <span v-else>저장 후 시작</span>
            </button>

            <button
              v-if="selectedTraining"
              @click="clearSettings"
              class="w-full py-3 border border-gray-600 rounded-xl text-gray-400 hover:text-white hover:border-gray-500 transition-all"
            >
              설정 초기화
            </button>
          </div>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="py-4 text-center text-gray-500 text-sm">
      <p>ROKAPASS 키오스크 모드</p>
    </footer>
  </div>
</template>
