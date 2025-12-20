<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/components/admin/AdminLayout.vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const authStore = useAuthStore()

const admins = ref([])
const settings = ref({
  default_purge_days: 3,
  auto_entry_on_qr: true,
})

const loading = ref(true)
const saving = ref(false)
const showAdminModal = ref(false)
const editingAdmin = ref(null)

const adminForm = ref({
  name: '',
  email: '',
  password: '',
  role: 'admin',
})

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const changingPassword = ref(false)
const passwordError = ref(null)
const passwordSuccess = ref(false)

onMounted(async () => {
  await Promise.all([
    fetchAdmins(),
    fetchSettings(),
  ])
  loading.value = false
})

async function fetchAdmins() {
  try {
    const response = await api.get('/admin/admins')
    if (response.data.success) {
      admins.value = response.data.data
    }
  } catch (e) {
    console.error('Failed to fetch admins:', e)
  }
}

async function fetchSettings() {
  try {
    const response = await api.get('/admin/settings')
    if (response.data.success) {
      settings.value = response.data.data
    }
  } catch (e) {
    console.error('Failed to fetch settings:', e)
  }
}

async function saveSettings() {
  saving.value = true
  try {
    await api.put('/admin/settings', settings.value)
    alert('설정이 저장되었습니다.')
  } catch (e) {
    alert('설정 저장에 실패했습니다.')
  } finally {
    saving.value = false
  }
}

function openAdminModal(admin = null) {
  editingAdmin.value = admin
  if (admin) {
    adminForm.value = {
      name: admin.name,
      email: admin.email,
      password: '',
      role: admin.role,
    }
  } else {
    adminForm.value = {
      name: '',
      email: '',
      password: '',
      role: 'admin',
    }
  }
  showAdminModal.value = true
}

function closeAdminModal() {
  showAdminModal.value = false
  editingAdmin.value = null
}

async function saveAdmin() {
  saving.value = true
  try {
    if (editingAdmin.value) {
      const data = { ...adminForm.value }
      if (!data.password) delete data.password
      await api.put(`/admin/admins/${editingAdmin.value.id}`, data)
    } else {
      await api.post('/admin/admins', adminForm.value)
    }
    await fetchAdmins()
    closeAdminModal()
  } catch (e) {
    alert(e.response?.data?.error?.message || '저장에 실패했습니다.')
  } finally {
    saving.value = false
  }
}

async function deleteAdmin(admin) {
  if (admin.id === authStore.admin?.id) {
    alert('자신의 계정은 삭제할 수 없습니다.')
    return
  }
  if (!confirm(`"${admin.name}" 관리자를 삭제하시겠습니까?`)) return

  try {
    await api.delete(`/admin/admins/${admin.id}`)
    await fetchAdmins()
  } catch (e) {
    alert(e.response?.data?.error?.message || '삭제에 실패했습니다.')
  }
}

async function changePassword() {
  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    passwordError.value = '새 비밀번호가 일치하지 않습니다.'
    return
  }

  changingPassword.value = true
  passwordError.value = null
  passwordSuccess.value = false

  try {
    await api.post('/admin/change-password', passwordForm.value)
    passwordSuccess.value = true
    passwordForm.value = {
      current_password: '',
      new_password: '',
      new_password_confirmation: '',
    }
  } catch (e) {
    passwordError.value = e.response?.data?.error?.message || '비밀번호 변경에 실패했습니다.'
  } finally {
    changingPassword.value = false
  }
}

function getRoleBadgeClass(role) {
  switch (role) {
    case 'super_admin':
      return 'bg-purple-100 text-purple-800'
    case 'admin':
      return 'bg-blue-100 text-blue-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

function getRoleText(role) {
  switch (role) {
    case 'super_admin':
      return '슈퍼관리자'
    case 'admin':
      return '관리자'
    default:
      return '열람자'
  }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div>
        <h1 class="text-2xl font-bold text-gray-800">설정</h1>
        <p class="text-gray-500 mt-1">시스템 설정 및 관리자 계정을 관리합니다.</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- System Settings -->
        <div class="bg-white rounded-xl shadow-sm p-6">
          <h2 class="text-lg font-semibold text-gray-800 mb-4">시스템 설정</h2>

          <form @submit.prevent="saveSettings" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                기본 데이터 보존 기간 (일)
              </label>
              <input
                v-model.number="settings.default_purge_days"
                type="number"
                min="1"
                max="30"
                class="input-field"
              />
              <p class="text-xs text-gray-500 mt-1">
                훈련 종료 후 개인정보 자동 파기까지의 기간
              </p>
            </div>

            <div>
              <label class="flex items-center gap-2">
                <input
                  v-model="settings.auto_entry_on_qr"
                  type="checkbox"
                  class="rounded"
                />
                <span class="text-sm text-gray-700">QR 발급 시 자동 입소 처리</span>
              </label>
              <p class="text-xs text-gray-500 mt-1 ml-6">
                활성화하면 QR 발급 즉시 입소 상태로 변경됩니다
              </p>
            </div>

            <button type="submit" :disabled="saving" class="btn-primary">
              <span v-if="saving">저장 중...</span>
              <span v-else>설정 저장</span>
            </button>
          </form>
        </div>

        <!-- Change Password -->
        <div class="bg-white rounded-xl shadow-sm p-6">
          <h2 class="text-lg font-semibold text-gray-800 mb-4">비밀번호 변경</h2>

          <form @submit.prevent="changePassword" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">현재 비밀번호</label>
              <input
                v-model="passwordForm.current_password"
                type="password"
                class="input-field"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">새 비밀번호</label>
              <input
                v-model="passwordForm.new_password"
                type="password"
                class="input-field"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">새 비밀번호 확인</label>
              <input
                v-model="passwordForm.new_password_confirmation"
                type="password"
                class="input-field"
              />
            </div>

            <div v-if="passwordError" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">
              {{ passwordError }}
            </div>

            <div v-if="passwordSuccess" class="bg-green-50 text-green-600 p-3 rounded-lg text-sm">
              비밀번호가 변경되었습니다.
            </div>

            <button type="submit" :disabled="changingPassword" class="btn-primary">
              <span v-if="changingPassword">변경 중...</span>
              <span v-else>비밀번호 변경</span>
            </button>
          </form>
        </div>
      </div>

      <!-- Admin Management -->
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-800">관리자 계정</h2>
          <button @click="openAdminModal()" class="btn-primary text-sm">
            관리자 추가
          </button>
        </div>

        <div v-if="loading" class="p-8 text-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-military-600 mx-auto"></div>
        </div>

        <table v-else class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">이름</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">이메일</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">권한</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">최근 로그인</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">작업</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <tr v-for="admin in admins" :key="admin.id">
              <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-800">
                {{ admin.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ admin.email }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="['px-2 py-1 text-xs font-medium rounded-full', getRoleBadgeClass(admin.role)]">
                  {{ getRoleText(admin.role) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ admin.last_login_at ? new Date(admin.last_login_at).toLocaleString('ko-KR') : '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                <button @click="openAdminModal(admin)" class="text-blue-600 hover:text-blue-800 mr-3">
                  수정
                </button>
                <button @click="deleteAdmin(admin)" class="text-red-600 hover:text-red-800">
                  삭제
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Admin Modal -->
    <div v-if="showAdminModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl w-full max-w-md">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-800">
            {{ editingAdmin ? '관리자 수정' : '관리자 추가' }}
          </h2>
          <button @click="closeAdminModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="saveAdmin" class="p-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">이름 *</label>
            <input v-model="adminForm.name" type="text" class="input-field" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">이메일 *</label>
            <input v-model="adminForm.email" type="email" class="input-field" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              비밀번호 {{ editingAdmin ? '(변경 시에만 입력)' : '*' }}
            </label>
            <input v-model="adminForm.password" type="password" class="input-field" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">권한</label>
            <select v-model="adminForm.role" class="input-field">
              <option value="viewer">열람자</option>
              <option value="admin">관리자</option>
              <option value="super_admin">슈퍼관리자</option>
            </select>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button type="button" @click="closeAdminModal" class="btn-secondary">
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
