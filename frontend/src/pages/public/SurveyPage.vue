<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyStore } from '@/stores/survey'
import api from '@/services/api'

const router = useRouter()
const surveyStore = useSurveyStore()

const submitting = ref(false)
const error = ref(null)

onMounted(() => {
  if (!surveyStore.trainingId || surveyStore.questions.length === 0) {
    router.push({ name: 'Landing' })
  }
})

const currentQuestion = computed(() => surveyStore.currentQuestion)
const progress = computed(() => surveyStore.progress)
const isLastQuestion = computed(() => surveyStore.isLastQuestion)
const currentAnswer = computed(() => {
  return surveyStore.answers[currentQuestion.value?.id] || null
})

function selectAnswer(answer) {
  surveyStore.answerQuestion(currentQuestion.value.id, answer)
}

function goNext() {
  if (!currentAnswer.value) return

  if (isLastQuestion.value) {
    submitSurvey()
  } else {
    surveyStore.nextQuestion()
  }
}

function goPrev() {
  if (surveyStore.currentQuestionIndex > 0) {
    surveyStore.prevQuestion()
  } else {
    router.push({ name: 'Form' })
  }
}

async function submitSurvey() {
  submitting.value = true
  error.value = null

  try {
    const payload = {
      training_id: surveyStore.trainingId,
      name: surveyStore.personalInfo.name,
      dob: surveyStore.personalInfo.dob,
      phone: surveyStore.personalInfo.phone,
      bank_name: surveyStore.personalInfo.bank_name,
      account_num: surveyStore.personalInfo.account_num,
      lunch_yn: surveyStore.personalInfo.lunch_yn,
      answers: surveyStore.answers,
    }

    const response = await api.post('/survey/submit', payload)

    if (response.data.success) {
      surveyStore.setResult(response.data.data)
      router.push({ name: 'Result' })
    } else {
      error.value = response.data.error?.message || '제출에 실패했습니다.'
    }
  } catch (e) {
    error.value = e.response?.data?.error?.message || '제출에 실패했습니다.'
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
        <div class="flex items-center justify-between mb-3">
          <button @click="goPrev" class="flex items-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>
          <h1 class="text-lg font-bold">문진표 작성</h1>
          <span class="text-sm">
            {{ surveyStore.currentQuestionIndex + 1 }}/{{ surveyStore.totalQuestions }}
          </span>
        </div>

        <!-- Progress Bar -->
        <div class="w-full bg-military-600 rounded-full h-2">
          <div
            class="bg-white rounded-full h-2 transition-all duration-300"
            :style="{ width: `${progress}%` }"
          ></div>
        </div>
      </div>
    </header>

    <!-- Question -->
    <main class="flex-1 flex flex-col max-w-md mx-auto w-full px-4 py-6">
      <div v-if="currentQuestion" class="flex-1 flex flex-col">
        <!-- Question Text -->
        <div class="bg-white rounded-lg p-5 shadow-sm mb-6">
          <p class="text-lg font-medium text-gray-800 leading-relaxed">
            {{ currentQuestion.question_text }}
          </p>
          <p v-if="currentQuestion.description" class="text-sm text-gray-500 mt-2">
            {{ currentQuestion.description }}
          </p>
        </div>

        <!-- Answer Options -->
        <div class="space-y-3 flex-1">
          <button
            v-for="option in currentQuestion.options"
            :key="option.value"
            @click="selectAnswer(option.value)"
            :class="[
              'w-full p-4 rounded-lg border-2 text-left transition-all',
              currentAnswer === option.value
                ? 'border-military-500 bg-military-50'
                : 'border-gray-200 bg-white hover:border-military-300'
            ]"
          >
            <span class="font-medium text-gray-800">{{ option.label }}</span>
          </button>
        </div>

        <!-- Error -->
        <p v-if="error" class="text-red-500 text-sm text-center mt-4">{{ error }}</p>

        <!-- Next Button -->
        <button
          @click="goNext"
          :disabled="!currentAnswer || submitting"
          :class="[
            'w-full py-4 rounded-xl font-bold text-lg mt-6 transition-all',
            currentAnswer && !submitting
              ? 'bg-military-600 text-white hover:bg-military-700'
              : 'bg-gray-300 text-gray-500 cursor-not-allowed'
          ]"
        >
          <span v-if="submitting" class="flex items-center justify-center">
            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            제출 중...
          </span>
          <span v-else>{{ isLastQuestion ? '제출하기' : '다음' }}</span>
        </button>
      </div>
    </main>
  </div>
</template>
