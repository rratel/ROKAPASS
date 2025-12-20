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
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- Header -->
    <header class="bg-gradient-to-r from-slate-900 to-slate-800 text-white py-5 px-4 sticky top-0 z-10">
      <div class="max-w-lg mx-auto">
        <div class="flex items-center justify-between mb-4">
          <button @click="goPrev" class="p-2 -ml-2 hover:bg-white/10 rounded-xl transition-colors flex items-center gap-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <span class="text-sm">이전</span>
          </button>
          <div class="text-center">
            <h1 class="text-lg font-bold">문진표 작성</h1>
            <p class="text-sm text-slate-400">2단계</p>
          </div>
          <span class="text-sm font-medium bg-white/10 px-3 py-1 rounded-full">
            {{ surveyStore.currentQuestionIndex + 1 }}/{{ surveyStore.totalQuestions }}
          </span>
        </div>

        <!-- Progress Bar -->
        <div class="w-full bg-slate-700 rounded-full h-2 overflow-hidden">
          <div
            class="bg-gradient-to-r from-emerald-400 to-emerald-500 rounded-full h-2 transition-all duration-500 ease-out"
            :style="{ width: `${progress}%` }"
          ></div>
        </div>
      </div>
    </header>

    <!-- Question -->
    <main class="flex-1 flex flex-col max-w-lg mx-auto w-full px-4 py-6">
      <div v-if="currentQuestion" class="flex-1 flex flex-col">
        <!-- Question Text -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-6">
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center flex-shrink-0">
              <span class="text-emerald-600 font-bold">Q{{ surveyStore.currentQuestionIndex + 1 }}</span>
            </div>
            <div>
              <p class="text-lg font-medium text-slate-900 leading-relaxed">
                {{ currentQuestion.question_text }}
              </p>
              <p v-if="currentQuestion.description" class="text-sm text-slate-500 mt-2">
                {{ currentQuestion.description }}
              </p>
            </div>
          </div>
        </div>

        <!-- Answer Options -->
        <div class="space-y-3 flex-1">
          <button
            v-for="option in currentQuestion.options"
            :key="option.value"
            @click="selectAnswer(option.value)"
            :class="[
              'w-full p-5 rounded-2xl border-2 text-left transition-all duration-200',
              currentAnswer === option.value
                ? 'border-emerald-500 bg-emerald-50 ring-4 ring-emerald-500/20'
                : 'border-slate-200 bg-white hover:border-emerald-300 hover:bg-slate-50'
            ]"
          >
            <div class="flex items-center gap-4">
              <div
                :class="[
                  'w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all',
                  currentAnswer === option.value
                    ? 'border-emerald-500 bg-emerald-500'
                    : 'border-slate-300'
                ]"
              >
                <svg v-if="currentAnswer === option.value" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
              <span
                :class="[
                  'font-medium',
                  currentAnswer === option.value ? 'text-emerald-700' : 'text-slate-700'
                ]"
              >
                {{ option.label }}
              </span>
            </div>
          </button>
        </div>

        <!-- Error -->
        <div v-if="error" class="flex items-center gap-3 p-4 bg-red-50 border border-red-100 rounded-xl mt-4">
          <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="text-sm text-red-600">{{ error }}</span>
        </div>

        <!-- Next Button -->
        <button
          @click="goNext"
          :disabled="!currentAnswer || submitting"
          :class="[
            'w-full py-4 rounded-2xl font-bold text-lg mt-6 transition-all duration-200 flex items-center justify-center gap-2',
            currentAnswer && !submitting
              ? 'bg-slate-900 text-white hover:bg-slate-800 shadow-lg shadow-slate-900/20'
              : 'bg-slate-200 text-slate-400 cursor-not-allowed'
          ]"
        >
          <span v-if="submitting" class="flex items-center gap-2">
            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            제출 중...
          </span>
          <span v-else class="flex items-center gap-2">
            {{ isLastQuestion ? '제출하기' : '다음' }}
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
          </span>
        </button>
      </div>
    </main>
  </div>
</template>
