<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AdminLayout from '@/components/admin/AdminLayout.vue'
import api from '@/services/api'

const router = useRouter()

const stats = ref({
  todayResponses: 0,
  todayEntries: 0,
  todayExits: 0,
  activeTrainings: 0,
})

const recentResponses = ref([])
const loading = ref(true)

// 상세 보기 모달
const showDetailModal = ref(false)
const selectedResponse = ref(null)
const loadingDetail = ref(false)

onMounted(async () => {
  await Promise.all([
    fetchStats(),
    fetchRecentResponses(),
  ])
  loading.value = false
})

async function fetchStats() {
  try {
    const response = await api.get('/admin/dashboard/stats')
    if (response.data.success) {
      stats.value = response.data.data
    }
  } catch (e) {
    console.error('Stats fetch failed:', e)
  }
}

async function fetchRecentResponses() {
  try {
    const response = await api.get('/admin/responses', {
      params: { limit: 10 }
    })
    if (response.data.success) {
      recentResponses.value = response.data.data.data || response.data.data
    }
  } catch (e) {
    console.error('Responses fetch failed:', e)
  }
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
    <div class="space-y-8">
      <!-- Welcome Section -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-slate-900">오늘의 현황</h2>
          <p class="text-slate-500 mt-1">실시간 훈련 진행 상황을 확인하세요.</p>
        </div>
        <div class="hidden sm:flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 rounded-xl">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
          </span>
          <span class="text-sm font-medium">실시간 업데이트</span>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Today Responses -->
        <div
          class="group bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 border border-slate-100 cursor-pointer"
          @click="router.push({ name: 'Responses' })"
        >
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500">오늘 등록</p>
              <p class="text-4xl font-bold text-slate-900 mt-2 tracking-tight">{{ stats.todayResponses }}</p>
              <p class="text-xs text-slate-400 mt-2">총 문진표 제출</p>
            </div>
            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Today Entries -->
        <div
          class="group bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 border border-slate-100 cursor-pointer"
          @click="router.push({ name: 'Responses', query: { status: 'entered' } })"
        >
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500">오늘 입소</p>
              <p class="text-4xl font-bold text-slate-900 mt-2 tracking-tight">{{ stats.todayEntries }}</p>
              <p class="text-xs text-slate-400 mt-2">QR 발급 완료</p>
            </div>
            <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-500/30 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Today Exits -->
        <div
          class="group bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 border border-slate-100 cursor-pointer"
          @click="router.push({ name: 'Responses', query: { status: 'exited' } })"
        >
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500">오늘 퇴소</p>
              <p class="text-4xl font-bold text-slate-900 mt-2 tracking-tight">{{ stats.todayExits }}</p>
              <p class="text-xs text-slate-400 mt-2">퇴소 처리 완료</p>
            </div>
            <div class="w-14 h-14 bg-gradient-to-br from-violet-500 to-violet-600 rounded-2xl flex items-center justify-center shadow-lg shadow-violet-500/30 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Active Trainings -->
        <div
          class="group bg-white rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-300 border border-slate-100 cursor-pointer"
          @click="router.push({ name: 'Trainings' })"
        >
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500">진행 중 훈련</p>
              <p class="text-4xl font-bold text-slate-900 mt-2 tracking-tight">{{ stats.activeTrainings }}</p>
              <p class="text-xs text-slate-400 mt-2">활성화된 훈련</p>
            </div>
            <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl flex items-center justify-center shadow-lg shadow-amber-500/30 group-hover:scale-110 transition-transform">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Responses Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-slate-900">최근 등록</h3>
            <p class="text-sm text-slate-500 mt-0.5">최근 제출된 문진표 목록</p>
          </div>
          <router-link
            :to="{ name: 'Responses' }"
            class="text-sm font-medium text-emerald-600 hover:text-emerald-700 flex items-center gap-1"
          >
            전체 보기
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </router-link>
        </div>

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
        <div v-else-if="recentResponses.length === 0" class="p-12 text-center">
          <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <p class="text-slate-500">등록된 응답이 없습니다.</p>
        </div>

        <!-- Table -->
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-slate-50/50">
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">이름</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">결과</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">상태</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">중식</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">등록일시</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="response in recentResponses"
                :key="response.id"
                class="hover:bg-slate-50/50 transition-colors cursor-pointer"
                @click="openDetail(response.id)"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-slate-100 to-slate-200 rounded-lg flex items-center justify-center">
                      <span class="text-sm font-medium text-slate-600">{{ response.name?.charAt(0) }}</span>
                    </div>
                    <span class="font-medium text-slate-900">{{ response.name }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg',
                      getResultBadge(response.survey_result).class
                    ]"
                  >
                    {{ getResultBadge(response.survey_result).label }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg',
                      getStatusBadge(response.attendance_status).class
                    ]"
                  >
                    {{ getStatusBadge(response.attendance_status).label }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="response.lunch_yn ? 'text-emerald-600' : 'text-slate-400'">
                    {{ response.lunch_yn ? '신청' : '미신청' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                  {{ new Date(response.created_at).toLocaleString('ko-KR', {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                  }) }}
                </td>
              </tr>
            </tbody>
          </table>
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
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-900">응답 상세</h3>
            <button
              @click="closeDetail"
              class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
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
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AdminLayout>
</template>
