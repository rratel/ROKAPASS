import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useSurveyStore = defineStore('survey', () => {
  // State
  const trainingId = ref(null)
  const trainingInfo = ref(null)
  const accessCode = ref(null)
  const personalInfo = ref({
    name: '',
    dob: '',
    phone: '',
    bank_name: '',
    account_num: '',
    lunch_yn: false,
  })
  const answers = ref({})
  const currentQuestionIndex = ref(0)
  const questions = ref([])
  const surveyResult = ref(null)
  const uuid = ref(null)
  const signature = ref(null)

  // Getters
  const totalQuestions = computed(() => questions.value.length)
  const progress = computed(() => {
    if (totalQuestions.value === 0) return 0
    return Math.round((currentQuestionIndex.value / totalQuestions.value) * 100)
  })
  const currentQuestion = computed(() => questions.value[currentQuestionIndex.value])
  const isLastQuestion = computed(() => currentQuestionIndex.value === totalQuestions.value - 1)

  // Actions
  function setTrainingInfo(info) {
    trainingId.value = info.id
    trainingInfo.value = info
    accessCode.value = info.access_code
  }

  function setPersonalInfo(info) {
    personalInfo.value = { ...personalInfo.value, ...info }
  }

  function setQuestions(q) {
    questions.value = q
  }

  function answerQuestion(questionId, answer) {
    answers.value[questionId] = answer
  }

  function nextQuestion() {
    if (currentQuestionIndex.value < totalQuestions.value - 1) {
      currentQuestionIndex.value++
    }
  }

  function prevQuestion() {
    if (currentQuestionIndex.value > 0) {
      currentQuestionIndex.value--
    }
  }

  function setResult(result) {
    surveyResult.value = result.survey_result
    uuid.value = result.uuid
  }

  function setSignature(sig) {
    signature.value = sig
  }

  function reset() {
    trainingId.value = null
    trainingInfo.value = null
    accessCode.value = null
    personalInfo.value = {
      name: '',
      dob: '',
      phone: '',
      bank_name: '',
      account_num: '',
      lunch_yn: false,
    }
    answers.value = {}
    currentQuestionIndex.value = 0
    questions.value = []
    surveyResult.value = null
    uuid.value = null
    signature.value = null
  }

  // Check localStorage for existing session
  function loadFromStorage() {
    const saved = localStorage.getItem('survey_session')
    if (saved) {
      const data = JSON.parse(saved)
      if (data.uuid) {
        uuid.value = data.uuid
        surveyResult.value = data.surveyResult
        personalInfo.value = data.personalInfo
        return true
      }
    }
    return false
  }

  function saveToStorage() {
    if (uuid.value) {
      localStorage.setItem('survey_session', JSON.stringify({
        uuid: uuid.value,
        surveyResult: surveyResult.value,
        personalInfo: personalInfo.value,
      }))
    }
  }

  return {
    // State
    trainingId,
    trainingInfo,
    accessCode,
    personalInfo,
    answers,
    currentQuestionIndex,
    questions,
    surveyResult,
    uuid,
    signature,
    // Getters
    totalQuestions,
    progress,
    currentQuestion,
    isLastQuestion,
    // Actions
    setTrainingInfo,
    setPersonalInfo,
    setQuestions,
    answerQuestion,
    nextQuestion,
    prevQuestion,
    setResult,
    setSignature,
    reset,
    loadFromStorage,
    saveToStorage,
  }
})
