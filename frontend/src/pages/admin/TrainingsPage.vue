<script setup>
import { ref, onMounted, computed } from 'vue'
import AdminLayout from '@/components/admin/AdminLayout.vue'
import api from '@/services/api'
import QRCode from 'qrcode'

const trainings = ref([])
const loading = ref(true)
const showModal = ref(false)
const showQRModal = ref(false)
const editingTraining = ref(null)
const selectedTraining = ref(null)
const qrCanvas = ref(null)

const form = ref({
  name: '',
  training_date: '',
  purge_days: 3,
  lunch_image_mon: '',
  lunch_image_tue: '',
  lunch_image_wed: '',
  lunch_image_thu: '',
  lunch_image_fri: '',
})

const saving = ref(false)
const error = ref(null)

const trainingAccessUrl = computed(() => {
  if (!selectedTraining.value) return ''
  const baseUrl = window.location.origin
  return `${baseUrl}/t/${selectedTraining.value.access_code}`
})

onMounted(async () => {
  await fetchTrainings()
})

async function fetchTrainings() {
  loading.value = true
  try {
    const response = await api.get('/admin/trainings')
    if (response.data.success) {
      trainings.value = response.data.data
    }
  } catch (e) {
    console.error('Failed to fetch trainings:', e)
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  editingTraining.value = null
  form.value = {
    name: '',
    training_date: '',
    purge_days: 3,
    lunch_image_mon: '',
    lunch_image_tue: '',
    lunch_image_wed: '',
    lunch_image_thu: '',
    lunch_image_fri: '',
  }
  showModal.value = true
}

function openEditModal(training) {
  editingTraining.value = training
  form.value = {
    name: training.name,
    training_date: training.training_date,
    purge_days: training.purge_days,
    lunch_image_mon: training.lunch_image_mon || '',
    lunch_image_tue: training.lunch_image_tue || '',
    lunch_image_wed: training.lunch_image_wed || '',
    lunch_image_thu: training.lunch_image_thu || '',
    lunch_image_fri: training.lunch_image_fri || '',
  }
  showModal.value = true
}

async function openQRModal(training) {
  selectedTraining.value = training
  showQRModal.value = true

  // Wait for modal to render then generate QR
  setTimeout(async () => {
    await generateQR()
  }, 100)
}

function closeModal() {
  showModal.value = false
  editingTraining.value = null
  error.value = null
}

function closeQRModal() {
  showQRModal.value = false
  selectedTraining.value = null
}

async function generateQR() {
  if (!qrCanvas.value || !selectedTraining.value) return

  try {
    const url = trainingAccessUrl.value
    await QRCode.toCanvas(qrCanvas.value, url, {
      width: 280,
      margin: 2,
      color: {
        dark: '#1e293b',
        light: '#ffffff',
      },
    })
  } catch (err) {
    console.error('QR 생성 실패:', err)
  }
}

function downloadQR() {
  if (!qrCanvas.value) return

  const link = document.createElement('a')
  link.download = `훈련QR_${selectedTraining.value.name}_${selectedTraining.value.access_code}.png`
  link.href = qrCanvas.value.toDataURL('image/png')
  link.click()
}

function copyAccessUrl() {
  navigator.clipboard.writeText(trainingAccessUrl.value)
  alert('URL이 클립보드에 복사되었습니다.')
}

async function saveTraining() {
  if (!form.value.name || !form.value.training_date) {
    error.value = '훈련명과 훈련일을 입력해주세요.'
    return
  }

  saving.value = true
  error.value = null

  try {
    if (editingTraining.value) {
      await api.put(`/admin/trainings/${editingTraining.value.id}`, form.value)
    } else {
      await api.post('/admin/trainings', form.value)
    }
    await fetchTrainings()
    closeModal()
  } catch (e) {
    error.value = e.response?.data?.error?.message || '저장에 실패했습니다.'
  } finally {
    saving.value = false
  }
}

async function deleteTraining(training) {
  if (!confirm(`"${training.name}" 훈련을 삭제하시겠습니까?`)) return

  try {
    await api.delete(`/admin/trainings/${training.id}`)
    await fetchTrainings()
  } catch (e) {
    alert(e.response?.data?.error?.message || '삭제에 실패했습니다.')
  }
}

async function activateTraining(training) {
  try {
    await api.post(`/admin/trainings/${training.id}/activate`)
    await fetchTrainings()
  } catch (e) {
    alert(e.response?.data?.error?.message || '활성화에 실패했습니다.')
  }
}

async function pauseTraining(training) {
  try {
    await api.post(`/admin/trainings/${training.id}/pause`)
    await fetchTrainings()
  } catch (e) {
    alert(e.response?.data?.error?.message || '일시정지에 실패했습니다.')
  }
}

async function completeTraining(training) {
  if (!confirm(`"${training.name}" 훈련을 종료하시겠습니까?\n종료 후에는 문진표 작성이 불가능합니다.`)) return

  try {
    await api.post(`/admin/trainings/${training.id}/complete`)
    await fetchTrainings()
  } catch (e) {
    alert(e.response?.data?.error?.message || '종료에 실패했습니다.')
  }
}

function getStatusBadge(status) {
  switch (status) {
    case 'active':
      return { class: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20', label: '진행중', icon: 'play' }
    case 'completed':
      return { class: 'bg-slate-50 text-slate-700 ring-1 ring-slate-600/20', label: '완료', icon: 'check' }
    case 'purged':
      return { class: 'bg-red-50 text-red-700 ring-1 ring-red-600/20', label: '파기됨', icon: 'trash' }
    default:
      return { class: 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20', label: '예정', icon: 'clock' }
  }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-slate-900">훈련 관리</h2>
          <p class="text-slate-500 mt-1">예비군 훈련 일정을 관리하고 QR 코드를 생성합니다.</p>
        </div>
        <button
          @click="openCreateModal"
          class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-xl transition-all duration-200 shadow-lg shadow-slate-900/20"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          새 훈련 추가
        </button>
      </div>

      <!-- Trainings Grid -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="inline-flex items-center gap-3">
          <svg class="animate-spin h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
          </svg>
          <span class="text-slate-500">훈련 목록을 불러오는 중...</span>
        </div>
      </div>

      <div v-else-if="trainings.length === 0" class="bg-white rounded-2xl border border-slate-100 p-12 text-center">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
        <p class="text-slate-500 mb-4">등록된 훈련이 없습니다.</p>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-4 py-2 text-emerald-600 hover:text-emerald-700 font-medium"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          첫 훈련 추가하기
        </button>
      </div>

      <div v-else class="grid gap-4">
        <div
          v-for="training in trainings"
          :key="training.id"
          class="bg-white rounded-2xl border border-slate-100 p-6 hover:shadow-md transition-all duration-300"
        >
          <div class="flex flex-col lg:flex-row lg:items-center gap-4">
            <!-- Left: Training Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-3 mb-2">
                <h3 class="text-lg font-semibold text-slate-900 truncate">{{ training.name }}</h3>
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg flex-shrink-0',
                    getStatusBadge(training.status).class
                  ]"
                >
                  {{ getStatusBadge(training.status).label }}
                </span>
                <!-- Access Code Badge -->
                <span v-if="training.access_code" class="inline-flex items-center px-2.5 py-1 text-xs font-mono font-medium rounded-lg bg-blue-50 text-blue-700 ring-1 ring-blue-600/20 flex-shrink-0">
                  {{ training.access_code }}
                </span>
              </div>
              <div class="flex flex-wrap items-center gap-4 text-sm text-slate-500">
                <div class="flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  {{ new Date(training.training_date).toLocaleDateString('ko-KR', { year: 'numeric', month: 'long', day: 'numeric', weekday: 'short' }) }}
                </div>
                <div class="flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  보존: {{ training.purge_days }}일
                </div>
                <!-- 파기 예정일 (completed 상태) -->
                <div v-if="training.status === 'completed' && training.auto_purge_at" class="flex items-center gap-1.5 text-red-500">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                  파기예정: {{ new Date(training.auto_purge_at).toLocaleDateString('ko-KR') }}
                </div>
              </div>
            </div>

            <!-- Right: Actions -->
            <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">
              <!-- QR Code Button (not for completed/purged) -->
              <button
                v-if="!['completed', 'purged'].includes(training.status)"
                @click="openQRModal(training)"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 font-medium rounded-xl transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                QR 코드
              </button>
              <button
                v-if="training.status === 'scheduled'"
                @click="activateTraining(training)"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-medium rounded-xl transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                시작
              </button>
              <button
                v-if="training.status === 'active'"
                @click="pauseTraining(training)"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 font-medium rounded-xl transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                일시정지
              </button>
              <button
                v-if="training.status === 'active'"
                @click="completeTraining(training)"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-xl transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                </svg>
                종료
              </button>
              <button
                v-if="!['completed', 'purged'].includes(training.status)"
                @click="openEditModal(training)"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-xl transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                수정
              </button>
              <button
                v-if="training.status !== 'purged'"
                @click="deleteTraining(training)"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 font-medium rounded-xl transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                삭제
              </button>
              <!-- Purged 상태 안내 -->
              <span
                v-if="training.status === 'purged'"
                class="text-sm text-slate-400 italic"
              >
                데이터 파기됨
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="showModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
          <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <div>
              <h2 class="text-xl font-semibold text-slate-900">
                {{ editingTraining ? '훈련 수정' : '새 훈련 추가' }}
              </h2>
              <p class="text-sm text-slate-500 mt-0.5">훈련 정보를 입력해주세요.</p>
            </div>
            <button @click="closeModal" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveTraining" class="p-6 space-y-6">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">훈련명 *</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                  placeholder="2024년 1월 예비군 훈련"
                />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">훈련일 *</label>
                <input
                  v-model="form.training_date"
                  type="date"
                  class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                />
              </div>
            </div>

            <!-- Settings -->
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">데이터 보존 기간</label>
              <div class="relative">
                <input
                  v-model.number="form.purge_days"
                  type="number"
                  min="1"
                  max="30"
                  class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all pr-12"
                />
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm">일</span>
              </div>
              <p class="text-xs text-slate-500 mt-1.5">훈련 종료 후 개인정보가 자동 파기됩니다.</p>
            </div>

            <!-- Lunch Images -->
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-3">요일별 중식 이미지 URL</label>
              <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <div>
                  <label class="block text-xs text-slate-500 mb-1.5">월요일</label>
                  <input
                    v-model="form.lunch_image_mon"
                    type="url"
                    class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                    placeholder="URL"
                  />
                </div>
                <div>
                  <label class="block text-xs text-slate-500 mb-1.5">화요일</label>
                  <input
                    v-model="form.lunch_image_tue"
                    type="url"
                    class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                    placeholder="URL"
                  />
                </div>
                <div>
                  <label class="block text-xs text-slate-500 mb-1.5">수요일</label>
                  <input
                    v-model="form.lunch_image_wed"
                    type="url"
                    class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                    placeholder="URL"
                  />
                </div>
                <div>
                  <label class="block text-xs text-slate-500 mb-1.5">목요일</label>
                  <input
                    v-model="form.lunch_image_thu"
                    type="url"
                    class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                    placeholder="URL"
                  />
                </div>
                <div>
                  <label class="block text-xs text-slate-500 mb-1.5">금요일</label>
                  <input
                    v-model="form.lunch_image_fri"
                    type="url"
                    class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                    placeholder="URL"
                  />
                </div>
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
                class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-xl transition-colors"
              >
                취소
              </button>
              <button
                type="submit"
                :disabled="saving"
                class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
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
      </div>
    </Transition>

    <!-- QR Code Modal -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="showQRModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
          <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <div>
              <h2 class="text-xl font-semibold text-slate-900">훈련 QR 코드</h2>
              <p class="text-sm text-slate-500 mt-0.5">이 QR을 예비군이 스캔하면 문진표 작성을 시작합니다.</p>
            </div>
            <button @click="closeQRModal" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div class="p-6">
            <!-- Training Info -->
            <div v-if="selectedTraining" class="text-center mb-6">
              <h3 class="text-lg font-semibold text-slate-900">{{ selectedTraining.name }}</h3>
              <p class="text-sm text-slate-500 mt-1">
                {{ new Date(selectedTraining.training_date).toLocaleDateString('ko-KR', { year: 'numeric', month: 'long', day: 'numeric', weekday: 'short' }) }}
              </p>
              <span class="inline-block mt-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-lg text-sm font-mono font-medium">
                {{ selectedTraining.access_code }}
              </span>
            </div>

            <!-- QR Code -->
            <div class="flex justify-center mb-6">
              <div class="bg-white p-4 rounded-2xl shadow-inner border-2 border-slate-100">
                <canvas ref="qrCanvas"></canvas>
              </div>
            </div>

            <!-- Access URL -->
            <div class="bg-slate-50 rounded-xl p-4 mb-6">
              <label class="block text-xs font-medium text-slate-500 mb-2">접속 URL</label>
              <div class="flex items-center gap-2">
                <input
                  type="text"
                  readonly
                  :value="trainingAccessUrl"
                  class="flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 font-mono"
                />
                <button
                  @click="copyAccessUrl"
                  class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg transition-colors"
                  title="URL 복사"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
              <button
                @click="downloadQR"
                class="flex-1 px-4 py-3 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-xl transition-colors flex items-center justify-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                QR 코드 다운로드
              </button>
              <button
                @click="closeQRModal"
                class="px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-xl transition-colors"
              >
                닫기
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </AdminLayout>
</template>
