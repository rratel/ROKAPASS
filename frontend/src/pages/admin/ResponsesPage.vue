<script setup>
import { ref, onMounted, watch } from 'vue'
import AdminLayout from '@/components/admin/AdminLayout.vue'
import api from '@/services/api'

const responses = ref([])
const trainings = ref([])
const loading = ref(true)
const exporting = ref(false)

const filters = ref({
  training_id: '',
  survey_result: '',
  attendance_status: '',
  search: '',
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
})

onMounted(async () => {
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
                class="hover:bg-slate-50/50 transition-colors"
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
  </AdminLayout>
</template>
