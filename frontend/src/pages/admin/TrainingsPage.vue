<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/components/admin/AdminLayout.vue'
import api from '@/services/api'

const trainings = ref([])
const loading = ref(true)
const showModal = ref(false)
const editingTraining = ref(null)

const form = ref({
  name: '',
  training_date: '',
  exit_mode: 'auto',
  purge_days: 3,
  lunch_image_mon: '',
  lunch_image_tue: '',
  lunch_image_wed: '',
  lunch_image_thu: '',
  lunch_image_fri: '',
})

const saving = ref(false)
const error = ref(null)

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
    exit_mode: 'auto',
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
    exit_mode: training.exit_mode,
    purge_days: training.purge_days,
    lunch_image_mon: training.lunch_image_mon || '',
    lunch_image_tue: training.lunch_image_tue || '',
    lunch_image_wed: training.lunch_image_wed || '',
    lunch_image_thu: training.lunch_image_thu || '',
    lunch_image_fri: training.lunch_image_fri || '',
  }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  editingTraining.value = null
  error.value = null
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

function getStatusBadgeClass(status) {
  switch (status) {
    case 'active':
      return 'bg-green-100 text-green-800'
    case 'completed':
      return 'bg-gray-100 text-gray-800'
    case 'purged':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-yellow-100 text-yellow-800'
  }
}

function getStatusText(status) {
  switch (status) {
    case 'active':
      return '진행중'
    case 'completed':
      return '완료'
    case 'purged':
      return '파기됨'
    default:
      return '예정'
  }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">훈련 관리</h1>
          <p class="text-gray-500 mt-1">훈련 일정을 관리합니다.</p>
        </div>
        <button @click="openCreateModal" class="btn-primary">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          새 훈련 추가
        </button>
      </div>

      <!-- Trainings Table -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div v-if="loading" class="p-8 text-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-military-600 mx-auto"></div>
        </div>

        <div v-else-if="trainings.length === 0" class="p-8 text-center text-gray-500">
          등록된 훈련이 없습니다.
        </div>

        <table v-else class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">훈련명</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">훈련일</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">상태</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">퇴소모드</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">보존기간</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">작업</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <tr v-for="training in trainings" :key="training.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="font-medium text-gray-800">{{ training.name }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ new Date(training.training_date).toLocaleDateString('ko-KR') }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded-full',
                    getStatusBadgeClass(training.status)
                  ]"
                >
                  {{ getStatusText(training.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ training.exit_mode === 'auto' ? '자동' : '확인' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ training.purge_days }}일
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                <button
                  v-if="training.status === 'scheduled'"
                  @click="activateTraining(training)"
                  class="text-green-600 hover:text-green-800 mr-3"
                >
                  시작
                </button>
                <button
                  @click="openEditModal(training)"
                  class="text-blue-600 hover:text-blue-800 mr-3"
                >
                  수정
                </button>
                <button
                  @click="deleteTraining(training)"
                  class="text-red-600 hover:text-red-800"
                >
                  삭제
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-800">
            {{ editingTraining ? '훈련 수정' : '새 훈련 추가' }}
          </h2>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="saveTraining" class="p-6 space-y-6">
          <!-- Basic Info -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">훈련명 *</label>
              <input v-model="form.name" type="text" class="input-field" placeholder="2024년 1월 예비군 훈련" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">훈련일 *</label>
              <input v-model="form.training_date" type="date" class="input-field" />
            </div>
          </div>

          <!-- Settings -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">퇴소 모드</label>
              <select v-model="form.exit_mode" class="input-field">
                <option value="auto">자동 (스캔 즉시 퇴소)</option>
                <option value="confirm">확인 (버튼 클릭 후 퇴소)</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">데이터 보존 기간 (일)</label>
              <input v-model.number="form.purge_days" type="number" min="1" max="30" class="input-field" />
            </div>
          </div>

          <!-- Lunch Images -->
          <div>
            <h3 class="text-sm font-medium text-gray-700 mb-3">요일별 중식 이미지 URL</h3>
            <div class="grid grid-cols-5 gap-2">
              <div>
                <label class="block text-xs text-gray-500 mb-1">월요일</label>
                <input v-model="form.lunch_image_mon" type="url" class="input-field text-sm" placeholder="URL" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">화요일</label>
                <input v-model="form.lunch_image_tue" type="url" class="input-field text-sm" placeholder="URL" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">수요일</label>
                <input v-model="form.lunch_image_wed" type="url" class="input-field text-sm" placeholder="URL" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">목요일</label>
                <input v-model="form.lunch_image_thu" type="url" class="input-field text-sm" placeholder="URL" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">금요일</label>
                <input v-model="form.lunch_image_fri" type="url" class="input-field text-sm" placeholder="URL" />
              </div>
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
