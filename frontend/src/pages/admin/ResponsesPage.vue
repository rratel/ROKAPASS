<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import AdminLayout from '@/components/admin/AdminLayout.vue'
import api from '@/services/api'

const route = useRoute()

const responses = ref([])
const trainings = ref([])
const loading = ref(true)
const exporting = ref(false)

// 상세 보기 모달
const showDetailModal = ref(false)
const selectedResponse = ref(null)
const loadingDetail = ref(false)

// 탭 상태
const activeTab = ref('view') // 'view' | 'edit' | 'resurvey'

// 수정 모드
const editForm = ref({
  name: '',
  dob: '',
  phone: '',
  bank_name: '',
  account_num: '',
  lunch_yn: false,
  survey_result: '',
  override_reason: '',
})
const saving = ref(false)

// 재문진 모드
const questions = ref([])
const loadingQuestions = ref(false)
const resurveyForm = ref({
  answers: {},
  override_reason: '',
})
const savingResurvey = ref(false)

// 삭제 확인
const showDeleteConfirm = ref(false)
const deleting = ref(false)

// 재문진 결과 미리보기 (실시간 계산)
const calculatedResult = computed(() => {
  if (questions.value.length === 0) return null

  let dangerCount = 0
  for (const q of questions.value) {
    const answer = resurveyForm.value.answers[q.id]
    if (!answer) continue

    const option = q.options?.find(o => o.value === answer)
    if (option?.is_danger) {
      dangerCount++
    }
  }

  if (dangerCount >= 2) return 'DANGER'
  if (dangerCount === 1) return 'CAUTION'
  return 'NORMAL'
})

const filters = ref({
  training_id: '',
  survey_result: '',
  attendance_status: '',
  lunch_yn: '',
  search: '',
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
})

onMounted(async () => {
  // URL 쿼리 파라미터로 필터 초기화
  if (route.query.status) {
    filters.value.attendance_status = route.query.status
  }
  if (route.query.result) {
    filters.value.survey_result = route.query.result
  }

  await Promise.all([
    fetchTrainings(),
    fetchResponses(),
  ])
})

watch(filters, () => {
  pagination.value.current_page = 1
  fetchResponses()
}, { deep: true })

async function fetchTrainings() {
  try {
    const response = await api.get('/admin/trainings')
    if (response.data.success) {
      trainings.value = response.data.data
    }
  } catch (e) {
    console.error('Failed to fetch trainings:', e)
  }
}

async function fetchResponses() {
  loading.value = true
  try {
    const response = await api.get('/admin/responses', {
      params: {
        ...filters.value,
        page: pagination.value.current_page,
        per_page: pagination.value.per_page,
      }
    })
    if (response.data.success) {
      const data = response.data.data
      responses.value = data.data || data
      if (data.meta) {
        pagination.value = {
          current_page: data.meta.current_page,
          last_page: data.meta.last_page,
          per_page: data.meta.per_page,
          total: data.meta.total,
        }
      }
    }
  } catch (e) {
    console.error('Failed to fetch responses:', e)
  } finally {
    loading.value = false
  }
}

async function exportExcel() {
  exporting.value = true
  try {
    const response = await api.get('/admin/responses/export', {
      params: filters.value,
      responseType: 'blob',
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `responses_${new Date().toISOString().split('T')[0]}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (e) {
    alert('엑셀 내보내기에 실패했습니다.')
  } finally {
    exporting.value = false
  }
}

function changePage(page) {
  if (page < 1 || page > pagination.value.last_page) return
  pagination.value.current_page = page
  fetchResponses()
}

function getResultBadge(result) {
  switch (result) {
    case 'NORMAL':
      return { class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20', label: '정상' }
    case 'CAUTION':
      return { class: 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20', label: '주의' }
    case 'DANGER':
      return { class: 'bg-red-50 text-red-700 ring-1 ring-red-600/20', label: '위험' }
    default:
      return { class: 'bg-slate-50 text-slate-700 ring-1 ring-slate-600/20', label: result }
  }
}

function getStatusBadge(status) {
  switch (status) {
    case 'entered':
      return { class: 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20', label: '입소' }
    case 'exited':
      return { class: 'bg-slate-50 text-slate-700 ring-1 ring-slate-600/20', label: '퇴소' }
    default:
      return { class: 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20', label: '대기' }
  }
}

function getVisiblePages() {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages = []

  if (last <= 7) {
    for (let i = 1; i <= last; i++) pages.push(i)
  } else {
    if (current <= 3) {
      pages.push(1, 2, 3, 4, '...', last)
    } else if (current >= last - 2) {
      pages.push(1, '...', last - 3, last - 2, last - 1, last)
    } else {
      pages.push(1, '...', current - 1, current, current + 1, '...', last)
    }
  }
  return pages
}

// 상세 보기
async function openDetail(responseId) {
  loadingDetail.value = true
  showDetailModal.value = true
  try {
    const response = await api.get(`/admin/responses/${responseId}`)
    if (response.data.success) {
      selectedResponse.value = response.data.data
    }
  } catch (e) {
    console.error('Failed to fetch response detail:', e)
    alert('상세 정보를 불러오는데 실패했습니다.')
    showDetailModal.value = false
  } finally {
    loadingDetail.value = false
  }
}

function closeDetail() {
  showDetailModal.value = false
  selectedResponse.value = null
  activeTab.value = 'view'
  showDeleteConfirm.value = false
}

// 수정 탭 시작
function startEdit() {
  if (!selectedResponse.value) return
  editForm.value = {
    name: selectedResponse.value.name,
    dob: selectedResponse.value.dob,
    phone: selectedResponse.value.phone,
    bank_name: selectedResponse.value.bank_name,
    account_num: selectedResponse.value.account_num,
    lunch_yn: selectedResponse.value.lunch_yn,
    survey_result: selectedResponse.value.survey_result,
    override_reason: '',
  }
  activeTab.value = 'edit'
}

// 수정 취소
function cancelEdit() {
  activeTab.value = 'view'
}

// 인적사항 수정 저장
async function saveEdit() {
  if (!selectedResponse.value) return
  saving.value = true
  try {
    // 인적사항만 수정
    const response = await api.put(`/admin/responses/${selectedResponse.value.id}`, {
      name: editForm.value.name,
      dob: editForm.value.dob,
      phone: editForm.value.phone,
      bank_name: editForm.value.bank_name,
      account_num: editForm.value.account_num,
      lunch_yn: editForm.value.lunch_yn,
    })
    if (response.data.success) {
      await fetchResponses()
      await openDetail(selectedResponse.value.id)
      activeTab.value = 'view'
      alert('수정되었습니다.')
    }
  } catch (e) {
    console.error('Failed to update response:', e)
    alert('수정에 실패했습니다.')
  } finally {
    saving.value = false
  }
}

// 삭제
async function deleteResponse() {
  if (!selectedResponse.value) return
  deleting.value = true
  try {
    const response = await api.delete(`/admin/responses/${selectedResponse.value.id}`)
    if (response.data.success) {
      closeDetail()
      await fetchResponses()
      alert('삭제되었습니다.')
    }
  } catch (e) {
    console.error('Failed to delete response:', e)
    alert('삭제에 실패했습니다.')
  } finally {
    deleting.value = false
    showDeleteConfirm.value = false
  }
}

// 결과 직접 변경 저장
async function saveResultChange() {
  if (!selectedResponse.value) return
  if (editForm.value.survey_result === selectedResponse.value.survey_result) {
    alert('결과가 변경되지 않았습니다.')
    return
  }

  saving.value = true
  try {
    const response = await api.put(`/admin/responses/${selectedResponse.value.id}/result`, {
      survey_result: editForm.value.survey_result,
      override_reason: editForm.value.override_reason,
    })
    if (response.data.success) {
      await fetchResponses()
      await openDetail(selectedResponse.value.id)
      activeTab.value = 'view'
      alert('문진 결과가 변경되었습니다.')
    }
  } catch (e) {
    console.error('Failed to update result:', e)
    alert('결과 변경에 실패했습니다.')
  } finally {
    saving.value = false
  }
}

// 재문진 탭 시작
async function startResurvey() {
  if (!selectedResponse.value) return
  activeTab.value = 'resurvey'

  // 질문 목록 가져오기
  if (questions.value.length === 0) {
    loadingQuestions.value = true
    try {
      const response = await api.get('/questions/active')
      if (response.data.success) {
        questions.value = response.data.data
      }
    } catch (e) {
      console.error('Failed to fetch questions:', e)
      alert('질문 목록을 불러오는데 실패했습니다.')
    } finally {
      loadingQuestions.value = false
    }
  }

  // 기존 답변으로 초기화
  const answers = {}
  if (selectedResponse.value.answers) {
    for (const a of selectedResponse.value.answers) {
      answers[a.question_id] = a.answer_value
    }
  }
  resurveyForm.value = {
    answers,
    override_reason: '',
  }
}

// 재문진 저장
async function saveResurvey() {
  if (!selectedResponse.value) return

  // 모든 질문에 답변했는지 확인
  const unanswered = questions.value.filter(q => !resurveyForm.value.answers[q.id])
  if (unanswered.length > 0) {
    alert('모든 질문에 답변해주세요.')
    return
  }

  savingResurvey.value = true
  try {
    const response = await api.put(`/admin/responses/${selectedResponse.value.id}/answers`, {
      answers: resurveyForm.value.answers,
      override_reason: resurveyForm.value.override_reason,
    })
    if (response.data.success) {
      await fetchResponses()
      await openDetail(selectedResponse.value.id)
      activeTab.value = 'view'
      alert('문진표가 재작성되었습니다.')
    }
  } catch (e) {
    console.error('Failed to save resurvey:', e)
    alert('재문진 저장에 실패했습니다.')
  } finally {
    savingResurvey.value = false
  }
}

function formatPhone(phone) {
  if (!phone) return '-'
  if (phone.length === 11) {
    return `${phone.slice(0, 3)}-${phone.slice(3, 7)}-${phone.slice(7)}`
  }
  return phone
}

function formatDob(dob) {
  if (!dob || dob.length !== 6) return dob || '-'
  return `${dob.slice(0, 2)}년 ${dob.slice(2, 4)}월 ${dob.slice(4, 6)}일`
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-slate-900">응답 관리</h2>
          <p class="text-slate-500 mt-1">문진표 응답을 확인하고 관리합니다.</p>
        </div>
        <button
          @click="exportExcel"
          :disabled="exporting"
          class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition-all duration-200 disabled:opacity-50 shadow-lg shadow-emerald-600/20"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <span v-if="exporting">내보내는 중...</span>
          <span v-else>엑셀 내보내기</span>
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">훈련</label>
            <select
              v-model="filters.training_id"
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
            >
              <option value="">전체</option>
              <option v-for="training in trainings" :key="training.id" :value="training.id">
                {{ training.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">결과</label>
            <select
              v-model="filters.survey_result"
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
            >
              <option value="">전체</option>
              <option value="NORMAL">정상</option>
              <option value="CAUTION">주의</option>
              <option value="DANGER">위험</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">상태</label>
            <select
              v-model="filters.attendance_status"
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
            >
              <option value="">전체</option>
              <option value="registered">대기</option>
              <option value="entered">입소</option>
              <option value="exited">퇴소</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">중식</label>
            <select
              v-model="filters.lunch_yn"
              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
            >
              <option value="">전체</option>
              <option value="1">신청</option>
              <option value="0">미신청</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">검색</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
              <input
                v-model="filters.search"
                type="text"
                class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                placeholder="이름 검색"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Responses Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <!-- Loading State -->
        <div v-if="loading" class="p-12 text-center">
          <div class="inline-flex items-center gap-3">
            <svg class="animate-spin h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-slate-500">데이터를 불러오는 중...</span>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="responses.length === 0" class="p-12 text-center">
          <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <p class="text-slate-500">조회된 응답이 없습니다.</p>
        </div>

        <!-- Table -->
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-slate-50/50">
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">이름</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">훈련</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">결과</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">상태</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">중식</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">입소</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">퇴소</th>
                <th class="px-5 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">등록</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="response in responses"
                :key="response.id"
                class="hover:bg-slate-50/50 transition-colors cursor-pointer"
                @click="openDetail(response.id)"
              >
                <td class="px-5 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-slate-100 to-slate-200 rounded-lg flex items-center justify-center">
                      <span class="text-sm font-medium text-slate-600">{{ response.name?.charAt(0) }}</span>
                    </div>
                    <span class="font-medium text-slate-900">{{ response.name }}</span>
                  </div>
                </td>
                <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-500">
                  {{ response.training?.name || '-' }}
                </td>
                <td class="px-5 py-4 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg',
                      getResultBadge(response.survey_result).class
                    ]"
                  >
                    {{ getResultBadge(response.survey_result).label }}
                  </span>
                </td>
                <td class="px-5 py-4 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg',
                      getStatusBadge(response.attendance_status).class
                    ]"
                  >
                    {{ getStatusBadge(response.attendance_status).label }}
                  </span>
                </td>
                <td class="px-5 py-4 whitespace-nowrap">
                  <span :class="response.lunch_yn ? 'text-emerald-600 font-medium' : 'text-slate-400'">
                    {{ response.lunch_yn ? '신청' : '미신청' }}
                  </span>
                </td>
                <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-500">
                  {{ response.entered_at ? new Date(response.entered_at).toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' }) : '-' }}
                </td>
                <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-500">
                  {{ response.exited_at ? new Date(response.exited_at).toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' }) : '-' }}
                </td>
                <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-500">
                  {{ new Date(response.created_at).toLocaleString('ko-KR', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p class="text-sm text-slate-500">
            총 <span class="font-medium text-slate-700">{{ pagination.total }}</span>건 중
            <span class="font-medium text-slate-700">{{ (pagination.current_page - 1) * pagination.per_page + 1 }}</span> -
            <span class="font-medium text-slate-700">{{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}</span>건
          </p>
          <div class="flex items-center gap-1">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
            </button>
            <template v-for="page in getVisiblePages()" :key="page">
              <span v-if="page === '...'" class="px-3 py-2 text-slate-400">...</span>
              <button
                v-else
                @click="changePage(page)"
                :class="[
                  'min-w-[40px] h-10 px-3 rounded-lg text-sm font-medium transition-colors',
                  page === pagination.current_page
                    ? 'bg-slate-900 text-white'
                    : 'text-slate-600 hover:bg-slate-100'
                ]"
              >
                {{ page }}
              </button>
            </template>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 상세 보기 모달 -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showDetailModal"
          class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50"
          @click="closeDetail"
        ></div>
      </Transition>

      <Transition
        enter-active-class="transition-transform duration-300"
        enter-from-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-300"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-full"
      >
        <div
          v-if="showDetailModal"
          class="fixed inset-y-0 right-0 w-full max-w-lg bg-white shadow-2xl z-50 flex flex-col"
        >
          <!-- Header -->
          <div class="border-b border-slate-200">
            <div class="flex items-center justify-between px-6 py-4">
              <h3 class="text-lg font-bold text-slate-900">응답 상세</h3>
              <div class="flex items-center gap-2">
                <button
                  v-if="activeTab === 'view' && !loadingDetail && selectedResponse"
                  @click="showDeleteConfirm = true"
                  class="px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                >
                  삭제
                </button>
                <button
                  @click="closeDetail"
                  class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>
            <!-- 탭 네비게이션 -->
            <div v-if="!loadingDetail && selectedResponse" class="flex px-6 gap-1">
              <button
                @click="activeTab = 'view'"
                :class="[
                  'px-4 py-2.5 text-sm font-medium rounded-t-lg border-b-2 transition-colors',
                  activeTab === 'view'
                    ? 'text-emerald-600 border-emerald-600 bg-emerald-50'
                    : 'text-slate-500 border-transparent hover:text-slate-700 hover:bg-slate-50'
                ]"
              >
                보기
              </button>
              <button
                @click="startEdit"
                :class="[
                  'px-4 py-2.5 text-sm font-medium rounded-t-lg border-b-2 transition-colors',
                  activeTab === 'edit'
                    ? 'text-blue-600 border-blue-600 bg-blue-50'
                    : 'text-slate-500 border-transparent hover:text-slate-700 hover:bg-slate-50'
                ]"
              >
                수정
              </button>
              <button
                @click="startResurvey"
                :class="[
                  'px-4 py-2.5 text-sm font-medium rounded-t-lg border-b-2 transition-colors',
                  activeTab === 'resurvey'
                    ? 'text-amber-600 border-amber-600 bg-amber-50'
                    : 'text-slate-500 border-transparent hover:text-slate-700 hover:bg-slate-50'
                ]"
              >
                재문진
              </button>
            </div>
          </div>

          <!-- Content -->
          <div class="flex-1 overflow-y-auto">
            <!-- Loading -->
            <div v-if="loadingDetail" class="p-12 text-center">
              <svg class="animate-spin h-8 w-8 text-emerald-600 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
              <p class="text-slate-500 mt-3">불러오는 중...</p>
            </div>

            <!-- Detail Content -->
            <div v-else-if="selectedResponse" class="p-6 space-y-6">
              <!-- ========== 수정 탭 ========== -->
              <template v-if="activeTab === 'edit'">
                <!-- 인적사항 수정 -->
                <div class="bg-slate-50 rounded-xl p-4 space-y-4">
                  <h4 class="text-sm font-semibold text-slate-500 uppercase tracking-wider">인적사항 수정</h4>
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-slate-700 mb-1">이름</label>
                      <input
                        v-model="editForm.name"
                        type="text"
                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700 mb-1">생년월일 (6자리)</label>
                      <input
                        v-model="editForm.dob"
                        type="text"
                        maxlength="6"
                        placeholder="990101"
                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700 mb-1">연락처 (숫자만)</label>
                      <input
                        v-model="editForm.phone"
                        type="text"
                        maxlength="11"
                        placeholder="01012345678"
                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700 mb-1">은행</label>
                      <input
                        v-model="editForm.bank_name"
                        type="text"
                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                    <div class="col-span-2">
                      <label class="block text-sm font-medium text-slate-700 mb-1">계좌번호</label>
                      <input
                        v-model="editForm.account_num"
                        type="text"
                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                    <div class="col-span-2">
                      <label class="flex items-center gap-2 cursor-pointer">
                        <input
                          v-model="editForm.lunch_yn"
                          type="checkbox"
                          class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span class="text-sm font-medium text-slate-700">중식 신청</span>
                      </label>
                    </div>
                  </div>
                  <div class="flex justify-end gap-2 pt-2">
                    <button
                      @click="cancelEdit"
                      class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-200 rounded-lg transition-colors"
                    >
                      취소
                    </button>
                    <button
                      @click="saveEdit"
                      :disabled="saving"
                      class="px-4 py-2 text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 rounded-lg transition-colors disabled:opacity-50"
                    >
                      {{ saving ? '저장 중...' : '인적사항 저장' }}
                    </button>
                  </div>
                </div>

                <!-- 문진 결과 직접 변경 -->
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 space-y-4">
                  <h4 class="text-sm font-semibold text-amber-700 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    문진 결과 직접 변경
                  </h4>
                  <p class="text-sm text-amber-700">군의관 면담 후 문진 결과를 직접 변경할 수 있습니다.</p>
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-slate-700 mb-1">현재 결과</label>
                      <div class="px-3 py-2 bg-white border border-slate-200 rounded-lg">
                        <span
                          :class="[
                            'inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg',
                            getResultBadge(selectedResponse.survey_result).class
                          ]"
                        >
                          {{ getResultBadge(selectedResponse.survey_result).label }}
                        </span>
                      </div>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700 mb-1">변경할 결과</label>
                      <select
                        v-model="editForm.survey_result"
                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500"
                      >
                        <option value="NORMAL">정상</option>
                        <option value="CAUTION">주의</option>
                        <option value="DANGER">위험</option>
                      </select>
                    </div>
                    <div class="col-span-2">
                      <label class="block text-sm font-medium text-slate-700 mb-1">변경 사유 (선택)</label>
                      <textarea
                        v-model="editForm.override_reason"
                        rows="2"
                        placeholder="예: 군의관 면담 결과 이상 없음 확인"
                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"
                      ></textarea>
                    </div>
                  </div>
                  <div class="flex justify-end pt-2">
                    <button
                      @click="saveResultChange"
                      :disabled="saving || editForm.survey_result === selectedResponse.survey_result"
                      class="px-4 py-2 text-sm font-medium bg-amber-600 text-white hover:bg-amber-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {{ saving ? '저장 중...' : '결과 변경' }}
                    </button>
                  </div>
                </div>
              </template>

              <!-- ========== 재문진 탭 ========== -->
              <template v-else-if="activeTab === 'resurvey'">
                <!-- 로딩 -->
                <div v-if="loadingQuestions" class="text-center py-8">
                  <svg class="animate-spin h-8 w-8 text-amber-600 mx-auto" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                  </svg>
                  <p class="text-slate-500 mt-3">질문 불러오는 중...</p>
                </div>

                <!-- 재문진 폼 -->
                <template v-else>
                  <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                    <p class="text-sm text-amber-700">
                      <strong>재문진 안내:</strong> 각 질문에 대한 답변을 다시 선택하면 결과가 자동으로 재계산됩니다.
                    </p>
                  </div>

                  <!-- 결과 미리보기 -->
                  <div class="bg-slate-100 rounded-xl p-4 mb-4">
                    <div class="flex items-center justify-between">
                      <span class="text-sm font-medium text-slate-600">결과 미리보기</span>
                      <div class="flex items-center gap-2">
                        <span
                          :class="[
                            'inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg',
                            getResultBadge(selectedResponse.survey_result).class
                          ]"
                        >
                          현재: {{ getResultBadge(selectedResponse.survey_result).label }}
                        </span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                        <span
                          v-if="calculatedResult"
                          :class="[
                            'inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg',
                            getResultBadge(calculatedResult).class
                          ]"
                        >
                          변경 후: {{ getResultBadge(calculatedResult).label }}
                        </span>
                        <span v-else class="text-sm text-slate-400">-</span>
                      </div>
                    </div>
                  </div>

                  <!-- 질문 목록 -->
                  <div class="space-y-4">
                    <div
                      v-for="(question, qIndex) in questions"
                      :key="question.id"
                      class="bg-white rounded-xl p-4 border border-slate-200"
                    >
                      <p class="text-sm font-medium text-slate-900 mb-3">
                        Q{{ qIndex + 1 }}. {{ question.question_text }}
                      </p>
                      <div class="flex flex-wrap gap-2">
                        <label
                          v-for="option in question.options"
                          :key="option.value"
                          :class="[
                            'inline-flex items-center px-4 py-2 rounded-lg border cursor-pointer transition-all',
                            resurveyForm.answers[question.id] === option.value
                              ? option.is_danger
                                ? 'bg-red-100 border-red-400 text-red-700'
                                : 'bg-emerald-100 border-emerald-400 text-emerald-700'
                              : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'
                          ]"
                        >
                          <input
                            type="radio"
                            :name="`resurvey_q_${question.id}`"
                            :value="option.value"
                            v-model="resurveyForm.answers[question.id]"
                            class="sr-only"
                          />
                          <span class="text-sm font-medium">{{ option.label }}</span>
                          <svg
                            v-if="option.is_danger && resurveyForm.answers[question.id] === option.value"
                            class="w-4 h-4 ml-1"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                          >
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                          </svg>
                        </label>
                      </div>
                    </div>
                  </div>

                  <!-- 사유 입력 -->
                  <div class="mt-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">변경 사유 (선택)</label>
                    <textarea
                      v-model="resurveyForm.override_reason"
                      rows="2"
                      placeholder="예: 군의관 면담 결과 재문진 실시"
                      class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"
                    ></textarea>
                  </div>

                  <!-- 버튼 -->
                  <div class="flex justify-end gap-2 mt-4">
                    <button
                      @click="activeTab = 'view'"
                      class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-200 rounded-lg transition-colors"
                    >
                      취소
                    </button>
                    <button
                      @click="saveResurvey"
                      :disabled="savingResurvey"
                      class="px-4 py-2 text-sm font-medium bg-amber-600 text-white hover:bg-amber-700 rounded-lg transition-colors disabled:opacity-50"
                    >
                      {{ savingResurvey ? '저장 중...' : '재문진 저장' }}
                    </button>
                  </div>
                </template>
              </template>

              <!-- ========== 보기 탭 ========== -->
              <template v-else>
                <!-- 인적사항 -->
                <div class="bg-slate-50 rounded-xl p-4">
                  <h4 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">인적사항</h4>
                  <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                      <span class="text-slate-500">이름</span>
                      <p class="font-medium text-slate-900">{{ selectedResponse.name }}</p>
                    </div>
                    <div>
                      <span class="text-slate-500">생년월일</span>
                      <p class="font-medium text-slate-900">{{ formatDob(selectedResponse.dob) }}</p>
                    </div>
                    <div>
                      <span class="text-slate-500">연락처</span>
                      <p class="font-medium text-slate-900">{{ formatPhone(selectedResponse.phone) }}</p>
                    </div>
                    <div>
                      <span class="text-slate-500">훈련</span>
                      <p class="font-medium text-slate-900">{{ selectedResponse.training?.name || '-' }}</p>
                    </div>
                    <div>
                      <span class="text-slate-500">은행</span>
                      <p class="font-medium text-slate-900">{{ selectedResponse.bank_name }}</p>
                    </div>
                    <div>
                      <span class="text-slate-500">계좌번호</span>
                      <p class="font-medium text-slate-900">{{ selectedResponse.account_num }}</p>
                    </div>
                  </div>
                </div>

                <!-- 문진 결과 -->
                <div class="flex items-center gap-4 flex-wrap">
                  <span
                    :class="[
                      'inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg',
                      getResultBadge(selectedResponse.survey_result).class
                    ]"
                  >
                    문진결과: {{ getResultBadge(selectedResponse.survey_result).label }}
                  </span>
                  <span
                    :class="[
                      'inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg',
                      getStatusBadge(selectedResponse.attendance_status).class
                    ]"
                  >
                    {{ getStatusBadge(selectedResponse.attendance_status).label }}
                  </span>
                  <span
                    :class="[
                      'inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg',
                      selectedResponse.lunch_yn ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-slate-50 text-slate-500 ring-1 ring-slate-200'
                    ]"
                  >
                    중식 {{ selectedResponse.lunch_yn ? '신청' : '미신청' }}
                  </span>
                </div>

                <!-- 삭제 확인 -->
                <div v-if="showDeleteConfirm" class="bg-red-50 border border-red-200 rounded-xl p-4">
                <p class="text-red-800 font-medium mb-3">정말 이 응답을 삭제하시겠습니까?</p>
                <p class="text-red-600 text-sm mb-4">삭제된 데이터는 복구할 수 없습니다.</p>
                <div class="flex justify-end gap-2">
                  <button
                    @click="showDeleteConfirm = false"
                    class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-200 rounded-lg transition-colors"
                  >
                    취소
                  </button>
                  <button
                    @click="deleteResponse"
                    :disabled="deleting"
                    class="px-4 py-2 text-sm font-medium bg-red-600 text-white hover:bg-red-700 rounded-lg transition-colors disabled:opacity-50"
                  >
                    {{ deleting ? '삭제 중...' : '삭제' }}
                  </button>
                </div>
              </div>

              <!-- 문진 Q&A -->
              <div>
                <h4 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4">문진표 응답</h4>
                <div v-if="selectedResponse.answers && selectedResponse.answers.length > 0" class="space-y-3">
                  <div
                    v-for="(answer, index) in selectedResponse.answers"
                    :key="index"
                    :class="[
                      'rounded-xl p-4 border-l-4',
                      answer.is_danger
                        ? 'bg-red-50 border-red-500'
                        : 'bg-white border-slate-200 shadow-sm'
                    ]"
                  >
                    <p class="text-sm text-slate-600 mb-1">Q{{ index + 1 }}. {{ answer.question_text }}</p>
                    <p :class="[
                      'font-medium flex items-center gap-2',
                      answer.is_danger ? 'text-red-700' : 'text-slate-900'
                    ]">
                      <svg v-if="answer.is_danger" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                      </svg>
                      → {{ answer.answer_label }}
                    </p>
                  </div>
                </div>
                <div v-else class="text-center py-8 text-slate-400">
                  응답 데이터가 없습니다.
                </div>
              </div>

              <!-- 시간 정보 -->
              <div class="bg-slate-50 rounded-xl p-4">
                <h4 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">시간 정보</h4>
                <div class="grid grid-cols-3 gap-3 text-sm">
                  <div>
                    <span class="text-slate-500">등록</span>
                    <p class="font-medium text-slate-900">
                      {{ selectedResponse.created_at ? new Date(selectedResponse.created_at).toLocaleString('ko-KR') : '-' }}
                    </p>
                  </div>
                  <div>
                    <span class="text-slate-500">입소</span>
                    <p class="font-medium text-slate-900">
                      {{ selectedResponse.entered_at ? new Date(selectedResponse.entered_at).toLocaleString('ko-KR') : '-' }}
                    </p>
                  </div>
                  <div>
                    <span class="text-slate-500">퇴소</span>
                    <p class="font-medium text-slate-900">
                      {{ selectedResponse.exited_at ? new Date(selectedResponse.exited_at).toLocaleString('ko-KR') : '-' }}
                    </p>
                  </div>
                </div>
              </div>
              </template>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AdminLayout>
</template>
