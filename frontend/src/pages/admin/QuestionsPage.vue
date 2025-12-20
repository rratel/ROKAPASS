<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/components/admin/AdminLayout.vue'
import api from '@/services/api'

const questions = ref([])
const loading = ref(true)
const showModal = ref(false)
const editingQuestion = ref(null)

const form = ref({
  question_text: '',
  description: '',
  question_type: 'yes_no',
  options: [
    { label: '예', value: 'yes', is_danger: false },
    { label: '아니오', value: 'no', is_danger: false },
  ],
  order_num: 0,
  is_active: true,
})

const saving = ref(false)
const error = ref(null)

onMounted(async () => {
  await fetchQuestions()
})

async function fetchQuestions() {
  loading.value = true
  try {
    const response = await api.get('/admin/questions')
    if (response.data.success) {
      questions.value = response.data.data
    }
  } catch (e) {
    console.error('Failed to fetch questions:', e)
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  editingQuestion.value = null
  form.value = {
    question_text: '',
    description: '',
    question_type: 'yes_no',
    options: [
      { label: '예', value: 'yes', is_danger: false },
      { label: '아니오', value: 'no', is_danger: false },
    ],
    order_num: questions.value.length + 1,
    is_active: true,
  }
  showModal.value = true
}

function openEditModal(question) {
  editingQuestion.value = question
  form.value = {
    question_text: question.question_text,
    description: question.description || '',
    question_type: question.question_type,
    options: JSON.parse(JSON.stringify(question.options || [])),
    order_num: question.order_num,
    is_active: question.is_active,
  }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingQuestion.value = null
  error.value = null
}

function addOption() {
  form.value.options.push({ label: '', value: '', is_danger: false })
}

function removeOption(index) {
  form.value.options.splice(index, 1)
}

async function saveQuestion() {
  if (!form.value.question_text) {
    error.value = '질문 내용을 입력해주세요.'
    return
  }

  saving.value = true
  error.value = null

  try {
    if (editingQuestion.value) {
      await api.put(`/admin/questions/${editingQuestion.value.id}`, form.value)
    } else {
      await api.post('/admin/questions', form.value)
    }
    await fetchQuestions()
    closeModal()
  } catch (e) {
    error.value = e.response?.data?.error?.message || '저장에 실패했습니다.'
  } finally {
    saving.value = false
  }
}

async function deleteQuestion(question) {
  if (!confirm('이 문항을 삭제하시겠습니까?')) return

  try {
    await api.delete(`/admin/questions/${question.id}`)
    await fetchQuestions()
  } catch (e) {
    alert(e.response?.data?.error?.message || '삭제에 실패했습니다.')
  }
}

async function toggleActive(question) {
  try {
    await api.patch(`/admin/questions/${question.id}/toggle`)
    await fetchQuestions()
  } catch (e) {
    alert('상태 변경에 실패했습니다.')
  }
}

async function moveQuestion(question, direction) {
  const index = questions.value.findIndex(q => q.id === question.id)
  if (direction === 'up' && index > 0) {
    await swapOrder(question, questions.value[index - 1])
  } else if (direction === 'down' && index < questions.value.length - 1) {
    await swapOrder(question, questions.value[index + 1])
  }
}

async function swapOrder(q1, q2) {
  try {
    await api.post('/admin/questions/reorder', {
      items: [
        { id: q1.id, order_num: q2.order_num },
        { id: q2.id, order_num: q1.order_num },
      ]
    })
    await fetchQuestions()
  } catch (e) {
    alert('순서 변경에 실패했습니다.')
  }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-8">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-slate-900">문진표 관리</h2>
          <p class="text-slate-500 mt-1">문진표 질문을 관리합니다.</p>
        </div>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 text-white rounded-xl font-medium hover:bg-slate-800 transition-all duration-200 shadow-lg shadow-slate-900/20"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          새 문항 추가
        </button>
      </div>

      <!-- Questions List -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <!-- Loading State -->
        <div v-if="loading" class="p-12 text-center">
          <div class="inline-flex items-center gap-3">
            <svg class="animate-spin h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-slate-500">문항을 불러오는 중...</span>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="questions.length === 0" class="p-12 text-center">
          <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
          </div>
          <p class="text-slate-500 mb-4">등록된 문항이 없습니다.</p>
          <button
            @click="openCreateModal"
            class="inline-flex items-center gap-2 px-4 py-2 text-emerald-600 hover:text-emerald-700 font-medium"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            첫 문항 추가하기
          </button>
        </div>

        <!-- Questions -->
        <div v-else class="divide-y divide-slate-100">
          <div
            v-for="(question, index) in questions"
            :key="question.id"
            :class="[
              'p-5 flex items-start gap-4 hover:bg-slate-50/50 transition-colors',
              !question.is_active && 'bg-slate-50'
            ]"
          >
            <!-- Order Controls -->
            <div class="flex flex-col items-center gap-1 pt-1">
              <button
                @click="moveQuestion(question, 'up')"
                :disabled="index === 0"
                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:hover:bg-transparent transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                </svg>
              </button>
              <span class="w-8 h-8 flex items-center justify-center bg-slate-100 rounded-lg text-sm font-medium text-slate-600">
                {{ question.order_num }}
              </span>
              <button
                @click="moveQuestion(question, 'down')"
                :disabled="index === questions.length - 1"
                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:hover:bg-transparent transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
            </div>

            <!-- Question Content -->
            <div class="flex-1 min-w-0">
              <p :class="['font-medium text-slate-900', !question.is_active && 'text-slate-400']">
                {{ question.question_text }}
              </p>
              <p v-if="question.description" class="text-sm text-slate-500 mt-1">
                {{ question.description }}
              </p>
              <div class="flex flex-wrap gap-2 mt-3">
                <span
                  v-for="option in question.options"
                  :key="option.value"
                  :class="[
                    'inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg',
                    option.is_danger
                      ? 'bg-red-50 text-red-700 ring-1 ring-red-600/20'
                      : 'bg-slate-100 text-slate-600'
                  ]"
                >
                  {{ option.label }}
                  <svg v-if="option.is_danger" class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                  </svg>
                </span>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
              <!-- Toggle Group -->
              <div class="inline-flex rounded-lg border border-slate-200 overflow-hidden">
                <button
                  @click="question.is_active || toggleActive(question)"
                  :class="[
                    'px-3 py-1.5 text-sm font-medium transition-colors',
                    question.is_active
                      ? 'bg-emerald-500 text-white'
                      : 'bg-white text-slate-500 hover:bg-slate-50'
                  ]"
                >
                  활성화
                </button>
                <button
                  @click="!question.is_active || toggleActive(question)"
                  :class="[
                    'px-3 py-1.5 text-sm font-medium transition-colors border-l border-slate-200',
                    !question.is_active
                      ? 'bg-slate-500 text-white'
                      : 'bg-white text-slate-500 hover:bg-slate-50'
                  ]"
                >
                  비활성화
                </button>
              </div>
              <button
                @click="openEditModal(question)"
                class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors"
              >
                수정
              </button>
              <button
                @click="deleteQuestion(question)"
                class="px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
              >
                삭제
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="showModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="showModal" class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white">
              <div>
                <h2 class="text-lg font-semibold text-slate-900">
                  {{ editingQuestion ? '문항 수정' : '새 문항 추가' }}
                </h2>
                <p class="text-sm text-slate-500 mt-0.5">문진표에 표시될 질문을 입력하세요.</p>
              </div>
              <button @click="closeModal" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- Modal Body -->
            <form @submit.prevent="saveQuestion" class="p-6 space-y-6">
              <!-- Question Text -->
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">질문 내용 *</label>
                <textarea
                  v-model="form.question_text"
                  rows="3"
                  class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200 resize-none"
                  placeholder="최근 2주 이내에 발열, 기침, 인후통 등의 증상이 있었습니까?"
                ></textarea>
              </div>

              <!-- Description -->
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">설명 (선택)</label>
                <input
                  v-model="form.description"
                  type="text"
                  class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
                  placeholder="추가 설명이 필요한 경우 입력"
                />
              </div>

              <!-- Options -->
              <div>
                <div class="flex items-center justify-between mb-3">
                  <label class="block text-sm font-semibold text-slate-700">답변 옵션</label>
                  <button
                    type="button"
                    @click="addOption"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600 hover:text-emerald-700"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    옵션 추가
                  </button>
                </div>
                <div class="space-y-3">
                  <div
                    v-for="(option, index) in form.options"
                    :key="index"
                    class="flex gap-3 items-center p-3 bg-slate-50 rounded-xl"
                  >
                    <input
                      v-model="option.label"
                      type="text"
                      class="flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
                      placeholder="표시 텍스트"
                    />
                    <input
                      v-model="option.value"
                      type="text"
                      class="w-24 px-3 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm"
                      placeholder="값"
                    />
                    <label class="flex items-center gap-2 text-sm whitespace-nowrap cursor-pointer">
                      <input
                        v-model="option.is_danger"
                        type="checkbox"
                        class="w-4 h-4 text-red-600 border-slate-300 rounded focus:ring-red-500"
                      />
                      <span class="text-red-600 font-medium">위험</span>
                    </label>
                    <button
                      type="button"
                      @click="removeOption(index)"
                      :disabled="form.options.length <= 2"
                      class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-slate-400 transition-colors"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Settings -->
              <div class="grid grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-2">순서</label>
                  <input
                    v-model.number="form.order_num"
                    type="number"
                    min="1"
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
                  />
                </div>
                <div class="flex items-end pb-1">
                  <label class="flex items-center gap-3 cursor-pointer">
                    <input
                      v-model="form.is_active"
                      type="checkbox"
                      class="w-5 h-5 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500"
                    />
                    <span class="text-sm font-medium text-slate-700">활성화</span>
                  </label>
                </div>
              </div>

              <!-- Error -->
              <div v-if="error" class="flex items-center gap-3 p-4 bg-red-50 border border-red-100 rounded-xl">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm text-red-600">{{ error }}</span>
              </div>

              <!-- Actions -->
              <div class="flex justify-end gap-3 pt-2">
                <button
                  type="button"
                  @click="closeModal"
                  class="px-5 py-2.5 border border-slate-200 text-slate-700 rounded-xl font-medium hover:bg-slate-50 transition-colors"
                >
                  취소
                </button>
                <button
                  type="submit"
                  :disabled="saving"
                  class="px-5 py-2.5 bg-slate-900 text-white rounded-xl font-medium hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
                >
                  <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                  </svg>
                  <span>{{ saving ? '저장 중...' : '저장' }}</span>
                </button>
              </div>
            </form>
          </div>
        </Transition>
      </div>
    </Transition>
  </AdminLayout>
</template>
