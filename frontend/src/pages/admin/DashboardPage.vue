<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/components/admin/AdminLayout.vue'
import api from '@/services/api'

const stats = ref({
  todayResponses: 0,
  todayEntries: 0,
  todayExits: 0,
  activeTrainings: 0,
})

const recentResponses = ref([])
const loading = ref(true)

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
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div>
        <h1 class="text-2xl font-bold text-gray-800">대시보드</h1>
        <p class="text-gray-500 mt-1">오늘의 훈련 현황을 확인하세요.</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Today Responses -->
        <div class="bg-white rounded-xl p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">오늘 등록</p>
              <p class="text-3xl font-bold text-gray-800 mt-1">{{ stats.todayResponses }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Today Entries -->
        <div class="bg-white rounded-xl p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">오늘 입소</p>
              <p class="text-3xl font-bold text-gray-800 mt-1">{{ stats.todayEntries }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Today Exits -->
        <div class="bg-white rounded-xl p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">오늘 퇴소</p>
              <p class="text-3xl font-bold text-gray-800 mt-1">{{ stats.todayExits }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Active Trainings -->
        <div class="bg-white rounded-xl p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-500">진행 중 훈련</p>
              <p class="text-3xl font-bold text-gray-800 mt-1">{{ stats.activeTrainings }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Responses -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-gray-800">최근 등록</h2>
        </div>

        <div v-if="loading" class="p-8 text-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-military-600 mx-auto"></div>
        </div>

        <div v-else-if="recentResponses.length === 0" class="p-8 text-center text-gray-500">
          등록된 응답이 없습니다.
        </div>

        <table v-else class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">이름</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">결과</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">상태</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">중식</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">등록일시</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <tr v-for="response in recentResponses" :key="response.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="font-medium text-gray-800">{{ response.name }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded-full',
                    getResultBadgeClass(response.survey_result)
                  ]"
                >
                  {{ response.survey_result }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded-full',
                    getStatusBadgeClass(response.attendance_status)
                  ]"
                >
                  {{ response.attendance_status === 'entered' ? '입소' :
                     response.attendance_status === 'exited' ? '퇴소' : '대기' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ response.lunch_yn ? 'O' : 'X' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ new Date(response.created_at).toLocaleString('ko-KR') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>
