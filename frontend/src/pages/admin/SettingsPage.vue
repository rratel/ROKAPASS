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

function getRoleBadge(role) {
  switch (role) {
    case 'super_admin':
      return { class: 'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20', label: '슈퍼관리자' }
    case 'admin':
      return { class: 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20', label: '관리자' }
    default:
      return { class: 'bg-slate-100 text-slate-600', label: '열람자' }
  }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-8">
      <!-- Page Header -->
      <div>
        <h2 class="text-2xl font-bold text-slate-900">설정</h2>
        <p class="text-slate-500 mt-1">시스템 설정 및 관리자 계정을 관리합니다.</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- System Settings -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-slate-900">시스템 설정</h3>
              <p class="text-sm text-slate-500">기본 동작 및 보안 설정</p>
            </div>
          </div>

          <form @submit.prevent="saveSettings" class="space-y-5">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">
                기본 데이터 보존 기간 (일)
              </label>
              <input
                v-model.number="settings.default_purge_days"
                type="number"
                min="1"
                max="30"
                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
              />
              <p class="text-xs text-slate-400 mt-2">
                훈련 종료 후 개인정보 자동 파기까지의 기간
              </p>
            </div>

            <div class="p-4 bg-slate-50 rounded-xl">
              <label class="flex items-start gap-3 cursor-pointer">
                <input
                  v-model="settings.auto_entry_on_qr"
                  type="checkbox"
                  class="w-5 h-5 mt-0.5 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500"
                />
                <div>
                  <span class="text-sm font-medium text-slate-700">QR 발급 시 자동 입소 처리</span>
                  <p class="text-xs text-slate-400 mt-1">
                    활성화하면 QR 발급 즉시 입소 상태로 변경됩니다
                  </p>
                </div>
              </label>
            </div>

            <button
              type="submit"
              :disabled="saving"
              class="w-full py-3 bg-slate-900 text-white rounded-xl font-medium hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
            >
              <svg v-if="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
              <span>{{ saving ? '저장 중...' : '설정 저장' }}</span>
            </button>
          </form>
        </div>

        <!-- Change Password -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
          <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center shadow-lg shadow-amber-500/30">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-slate-900">비밀번호 변경</h3>
              <p class="text-sm text-slate-500">계정 보안 관리</p>
            </div>
          </div>

          <form @submit.prevent="changePassword" class="space-y-4">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">현재 비밀번호</label>
              <input
                v-model="passwordForm.current_password"
                type="password"
                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
              />
            </div>

            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">새 비밀번호</label>
              <input
                v-model="passwordForm.new_password"
                type="password"
                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
              />
            </div>

            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">새 비밀번호 확인</label>
              <input
                v-model="passwordForm.new_password_confirmation"
                type="password"
                class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
              />
            </div>

            <div v-if="passwordError" class="flex items-center gap-3 p-4 bg-red-50 border border-red-100 rounded-xl">
              <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span class="text-sm text-red-600">{{ passwordError }}</span>
            </div>

            <div v-if="passwordSuccess" class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-100 rounded-xl">
              <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span class="text-sm text-emerald-600">비밀번호가 변경되었습니다.</span>
            </div>

            <button
              type="submit"
              :disabled="changingPassword"
              class="w-full py-3 bg-slate-900 text-white rounded-xl font-medium hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
            >
              <svg v-if="changingPassword" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
              <span>{{ changingPassword ? '변경 중...' : '비밀번호 변경' }}</span>
            </button>
          </form>
        </div>
      </div>

      <!-- Admin Management -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-violet-600 rounded-xl flex items-center justify-center shadow-lg shadow-violet-500/30">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-slate-900">관리자 계정</h3>
              <p class="text-sm text-slate-500">관리자 계정을 추가하고 관리합니다.</p>
            </div>
          </div>
          <button
            @click="openAdminModal()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white rounded-xl font-medium text-sm hover:bg-slate-800 transition-colors shadow-lg shadow-slate-900/20"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            관리자 추가
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="p-12 text-center">
          <div class="inline-flex items-center gap-3">
            <svg class="animate-spin h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-slate-500">관리자 목록을 불러오는 중...</span>
          </div>
        </div>

        <!-- Admin Table -->
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-slate-50/50">
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">관리자</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">이메일</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">권한</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">최근 로그인</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">작업</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="admin in admins" :key="admin.id" class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-gradient-to-br from-slate-100 to-slate-200 rounded-lg flex items-center justify-center">
                      <span class="text-sm font-medium text-slate-600">{{ admin.name?.charAt(0) }}</span>
                    </div>
                    <span class="font-medium text-slate-900">{{ admin.name }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                  {{ admin.email }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg',
                      getRoleBadge(admin.role).class
                    ]"
                  >
                    {{ getRoleBadge(admin.role).label }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                  {{ admin.last_login_at ? new Date(admin.last_login_at).toLocaleString('ko-KR') : '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button
                      @click="openAdminModal(admin)"
                      class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors"
                    >
                      수정
                    </button>
                    <button
                      @click="deleteAdmin(admin)"
                      class="px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                    >
                      삭제
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Admin Modal -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="showAdminModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="showAdminModal" class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
              <div>
                <h2 class="text-lg font-semibold text-slate-900">
                  {{ editingAdmin ? '관리자 수정' : '관리자 추가' }}
                </h2>
                <p class="text-sm text-slate-500 mt-0.5">관리자 정보를 입력하세요.</p>
              </div>
              <button @click="closeAdminModal" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- Modal Body -->
            <form @submit.prevent="saveAdmin" class="p-6 space-y-4">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">이름 *</label>
                <input
                  v-model="adminForm.name"
                  type="text"
                  class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">이메일 *</label>
                <input
                  v-model="adminForm.email"
                  type="email"
                  class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                  비밀번호 {{ editingAdmin ? '(변경 시에만 입력)' : '*' }}
                </label>
                <input
                  v-model="adminForm.password"
                  type="password"
                  class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
                />
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">권한</label>
                <div class="relative">
                  <select
                    v-model="adminForm.role"
                    class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200 appearance-none cursor-pointer"
                  >
                    <option value="viewer">열람자</option>
                    <option value="admin">관리자</option>
                    <option value="super_admin">슈퍼관리자</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </div>
                </div>
              </div>

              <div class="flex justify-end gap-3 pt-4">
                <button
                  type="button"
                  @click="closeAdminModal"
                  class="px-5 py-2.5 border border-slate-200 text-slate-700 rounded-xl font-medium hover:bg-slate-50 transition-colors"
                >
                  취소
                </button>
                <button
                  type="submit"
                  :disabled="saving"
                  class="px-5 py-2.5 bg-slate-900 text-white rounded-xl font-medium hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
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
        </Transition>
      </div>
    </Transition>
  </AdminLayout>
</template>
