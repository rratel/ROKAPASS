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

function getResultBadgeClass(result) {
  switch (result) {
    case 'NORMAL':
      return 'bg-green-100 text-green-800'
    case 'CAUTION':
      return 'bg-yellow-100 text-yellow-800'
    case 'DANGER':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

function getStatusBadgeClass(status) {
  switch (status) {
    case 'entered':
      return 'bg-blue-100 text-blue-800'
    case 'exited':
      return 'bg-gray-100 text-gray-800'
    default:
      return 'bg-yellow-100 text-yellow-800'
  }
}

function getStatusText(status) {
  switch (status) {
    case 'entered':
      return '입소'
    case 'exited':
      return '퇴소'
    default:
      return '대기'
  }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">응답 관리</h1>
          <p class="text-gray-500 mt-1">문진표 응답을 확인하고 관리합니다.</p>
        </div>
        <button @click="exportExcel" :disabled="exporting" class="btn-secondary">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <span v-if="exporting">내보내는 중...</span>
          <span v-else>엑셀 내보내기</span>
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm text-gray-600 mb-1">훈련</label>
            <select v-model="filters.training_id" class="input-field">
              <option value="">전체</option>
              <option v-for="training in trainings" :key="training.id" :value="training.id">
                {{ training.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">결과</label>
            <select v-model="filters.survey_result" class="input-field">
              <option value="">전체</option>
              <option value="NORMAL">정상</option>
              <option value="CAUTION">주의</option>
              <option value="DANGER">위험</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">상태</label>
            <select v-model="filters.attendance_status" class="input-field">
              <option value="">전체</option>
              <option value="registered">대기</option>
              <option value="entered">입소</option>
              <option value="exited">퇴소</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">검색</label>
            <input v-model="filters.search" type="text" class="input-field" placeholder="이름 검색" />
          </div>
        </div>
      </div>

      <!-- Responses Table -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div v-if="loading" class="p-8 text-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-military-600 mx-auto"></div>
        </div>

        <div v-else-if="responses.length === 0" class="p-8 text-center text-gray-500">
          조회된 응답이 없습니다.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">이름</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">훈련</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">결과</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">상태</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">중식</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">입소시간</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">퇴소시간</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">등록일시</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
              <tr v-for="response in responses" :key="response.id">
                <td class="px-4 py-4 whitespace-nowrap font-medium text-gray-800">
                  {{ response.name }}
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ response.training?.name || '-' }}
                </td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span :class="['px-2 py-1 text-xs font-medium rounded-full', getResultBadgeClass(response.survey_result)]">
                    {{ response.survey_result }}
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusBadgeClass(response.attendance_status)]">
                    {{ getStatusText(response.attendance_status) }}
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ response.lunch_yn ? 'O' : 'X' }}
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ response.entered_at ? new Date(response.entered_at).toLocaleTimeString('ko-KR') : '-' }}
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ response.exited_at ? new Date(response.exited_at).toLocaleTimeString('ko-KR') : '-' }}
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ new Date(response.created_at).toLocaleString('ko-KR') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
          <p class="text-sm text-gray-500">
            총 {{ pagination.total }}건 중 {{ (pagination.current_page - 1) * pagination.per_page + 1 }} - {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}건
          </p>
          <div class="flex gap-1">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-1 border rounded text-sm disabled:opacity-50"
            >
              이전
            </button>
            <button
              v-for="page in pagination.last_page"
              :key="page"
              @click="changePage(page)"
              :class="[
                'px-3 py-1 border rounded text-sm',
                page === pagination.current_page ? 'bg-military-600 text-white border-military-600' : ''
              ]"
            >
              {{ page }}
            </button>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1 border rounded text-sm disabled:opacity-50"
            >
              다음
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
