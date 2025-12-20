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
                class="hover:bg-slate-50/50 transition-colors"
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
  </AdminLayout>
</template>
