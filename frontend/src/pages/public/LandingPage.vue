<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyStore } from '@/stores/survey'
import api from '@/services/api'

const router = useRouter()
const surveyStore = useSurveyStore()

const trainings = ref([])
const selectedTraining = ref(null)
const loading = ref(true)
const error = ref(null)

onMounted(async () => {
  // 기존 세션이 있는지 확인
  if (surveyStore.loadFromStorage()) {
    router.push({ name: 'QR' })
    return
  }

  try {
    const response = await api.get('/trainings/active')
    if (response.data.success) {
      trainings.value = response.data.data
      if (trainings.value.length === 1) {
        selectedTraining.value = trainings.value[0]
      }
    }
  } catch (e) {
    error.value = '훈련 정보를 불러오는데 실패했습니다.'
  } finally {
    loading.value = false
  }
})

function selectTraining(training) {
  selectedTraining.value = training
}

function startSurvey() {
  if (!selectedTraining.value) return
  surveyStore.setTrainingInfo(selectedTraining.value)
  router.push({ name: 'Form' })
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-b from-military-700 to-military-900 flex flex-col">
    <!-- Header -->
    <header class="py-6 px-4 text-center">
      <h1 class="text-2xl font-bold text-white">예비군 훈련</h1>
      <p class="text-military-200 mt-1">One-Step 입소 시스템</p>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col items-center justify-center px-4 pb-8">
      <div class="w-full max-w-md">
        <!-- Loading State -->
        <div v-if="loading" class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto"></div>
          <p class="text-white mt-4">로딩 중...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-500/20 text-red-100 p-4 rounded-lg text-center">
          {{ error }}
        </div>

        <!-- No Active Trainings -->
        <div v-else-if="trainings.length === 0" class="bg-white/10 p-6 rounded-lg text-center">
          <p class="text-white text-lg">현재 진행 중인 훈련이 없습니다.</p>
          <p class="text-military-200 mt-2">훈련 시작 시간에 다시 방문해주세요.</p>
        </div>

        <!-- Training Selection -->
        <div v-else class="space-y-4">
          <div class="bg-white rounded-xl shadow-xl p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">훈련 선택</h2>

            <div class="space-y-3">
              <button
                v-for="training in trainings"
                :key="training.id"
                @click="selectTraining(training)"
                :class="[
                  'w-full p-4 rounded-lg border-2 text-left transition-all',
                  selectedTraining?.id === training.id
                    ? 'border-military-500 bg-military-50'
                    : 'border-gray-200 hover:border-military-300'
                ]"
              >
                <p class="font-medium text-gray-800">{{ training.name }}</p>
                <p class="text-sm text-gray-500 mt-1">
                  {{ new Date(training.training_date).toLocaleDateString('ko-KR') }}
                </p>
              </button>
            </div>
          </div>

          <button
            @click="startSurvey"
            :disabled="!selectedTraining"
            :class="[
              'w-full py-4 rounded-xl font-bold text-lg transition-all',
              selectedTraining
                ? 'bg-white text-military-700 hover:bg-gray-100'
                : 'bg-gray-400 text-gray-600 cursor-not-allowed'
            ]"
          >
            입소 절차 시작
          </button>
        </div>

        <!-- QR Reissue Link -->
        <div class="mt-8 text-center">
          <router-link
            :to="{ name: 'Reissue' }"
            class="text-white/80 hover:text-white underline text-sm"
          >
            QR코드 분실 시 재발급
          </router-link>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="py-4 text-center text-military-300 text-sm">
      <p>ROKAPASS - 예비군 One-Step 입소 시스템</p>
    </footer>
  </div>
</template>
