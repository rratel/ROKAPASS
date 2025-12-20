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
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">문진표 관리</h1>
          <p class="text-gray-500 mt-1">문진표 질문을 관리합니다.</p>
        </div>
        <button @click="openCreateModal" class="btn-primary">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          새 문항 추가
        </button>
      </div>

      <!-- Questions List -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div v-if="loading" class="p-8 text-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-military-600 mx-auto"></div>
        </div>

        <div v-else-if="questions.length === 0" class="p-8 text-center text-gray-500">
          등록된 문항이 없습니다.
        </div>

        <div v-else class="divide-y divide-gray-100">
          <div
            v-for="(question, index) in questions"
            :key="question.id"
            :class="['p-4 flex items-start gap-4', !question.is_active && 'bg-gray-50']"
          >
            <!-- Order Controls -->
            <div class="flex flex-col gap-1">
              <button
                @click="moveQuestion(question, 'up')"
                :disabled="index === 0"
                class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                </svg>
              </button>
              <span class="text-sm text-center text-gray-500">{{ question.order_num }}</span>
              <button
                @click="moveQuestion(question, 'down')"
                :disabled="index === questions.length - 1"
                class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
            </div>

            <!-- Question Content -->
            <div class="flex-1">
              <p :class="['font-medium', !question.is_active && 'text-gray-400']">
                {{ question.question_text }}
              </p>
              <p v-if="question.description" class="text-sm text-gray-500 mt-1">
                {{ question.description }}
              </p>
              <div class="flex flex-wrap gap-2 mt-2">
                <span
                  v-for="option in question.options"
                  :key="option.value"
                  :class="[
                    'px-2 py-1 text-xs rounded-full',
                    option.is_danger ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-600'
                  ]"
                >
                  {{ option.label }}
                </span>
              </div>
            </div>

            <!-- Status Badge -->
            <span
              :class="[
                'px-2 py-1 text-xs font-medium rounded-full',
                question.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500'
              ]"
            >
              {{ question.is_active ? '활성' : '비활성' }}
            </span>

            <!-- Actions -->
            <div class="flex gap-2">
              <button
                @click="toggleActive(question)"
                class="text-sm text-gray-500 hover:text-gray-700"
              >
                {{ question.is_active ? '비활성화' : '활성화' }}
              </button>
              <button
                @click="openEditModal(question)"
                class="text-sm text-blue-600 hover:text-blue-800"
              >
                수정
              </button>
              <button
                @click="deleteQuestion(question)"
                class="text-sm text-red-600 hover:text-red-800"
              >
                삭제
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-800">
            {{ editingQuestion ? '문항 수정' : '새 문항 추가' }}
          </h2>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="saveQuestion" class="p-6 space-y-6">
          <!-- Question Text -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">질문 내용 *</label>
            <textarea
              v-model="form.question_text"
              rows="3"
              class="input-field"
              placeholder="최근 2주 이내에 발열, 기침, 인후통 등의 증상이 있었습니까?"
            ></textarea>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">설명 (선택)</label>
            <input
              v-model="form.description"
              type="text"
              class="input-field"
              placeholder="추가 설명이 필요한 경우 입력"
            />
          </div>

          <!-- Options -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-sm font-medium text-gray-700">답변 옵션</label>
              <button type="button" @click="addOption" class="text-sm text-military-600 hover:text-military-700">
                + 옵션 추가
              </button>
            </div>
            <div class="space-y-2">
              <div v-for="(option, index) in form.options" :key="index" class="flex gap-2 items-center">
                <input
                  v-model="option.label"
                  type="text"
                  class="input-field flex-1"
                  placeholder="표시 텍스트"
                />
                <input
                  v-model="option.value"
                  type="text"
                  class="input-field w-24"
                  placeholder="값"
                />
                <label class="flex items-center gap-1 text-sm whitespace-nowrap">
                  <input v-model="option.is_danger" type="checkbox" class="rounded" />
                  <span class="text-red-600">위험</span>
                </label>
                <button
                  type="button"
                  @click="removeOption(index)"
                  :disabled="form.options.length <= 2"
                  class="p-1 text-gray-400 hover:text-red-600 disabled:opacity-30"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Settings -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">순서</label>
              <input v-model.number="form.order_num" type="number" min="1" class="input-field" />
            </div>
            <div class="flex items-center pt-6">
              <label class="flex items-center gap-2">
                <input v-model="form.is_active" type="checkbox" class="rounded" />
                <span class="text-sm text-gray-700">활성화</span>
              </label>
            </div>
          </div>

          <!-- Error -->
          <div v-if="error" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">
            {{ error }}
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3">
            <button type="button" @click="closeModal" class="btn-secondary">
              취소
            </button>
            <button type="submit" :disabled="saving" class="btn-primary">
              <span v-if="saving">저장 중...</span>
              <span v-else>저장</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>
