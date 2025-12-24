<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const sidebarOpen = ref(false)
const sidebarCollapsed = ref(false)

const navigation = [
  { name: '대시보드', route: 'Dashboard', icon: 'dashboard' },
  { name: '훈련 관리', route: 'Trainings', icon: 'calendar' },
  { name: '응답 관리', route: 'Responses', icon: 'document' },
  { name: '문진표 관리', route: 'Questions', icon: 'clipboard' },
  { name: '퇴소 스캐너', route: 'ExitScanner', icon: 'qrcode' },
  { name: '설정', route: 'Settings', icon: 'settings' },
  { name: '도움말', route: 'Help', icon: 'help' },
  { name: '사용 가이드', route: 'HelpGuide', icon: 'book' },
]

const currentRoute = computed(() => route.name)

const pageTitle = computed(() => {
  const current = navigation.find(n => n.route === route.name)
  return current?.name || '관리자'
})

function isActive(routeName) {
  return currentRoute.value === routeName
}

async function handleLogout() {
  await authStore.logout()
  router.push({ name: 'AdminLogin' })
}

function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value
}
</script>

<template>
  <div class="min-h-screen bg-slate-100">
    <!-- Mobile Sidebar Overlay -->
    <Transition
      enter-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-300"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 lg:hidden"
        @click="sidebarOpen = false"
      ></div>
    </Transition>

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-50 flex flex-col bg-slate-900 transform transition-all duration-300 ease-out lg:translate-x-0',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        sidebarCollapsed ? 'w-20' : 'w-72'
      ]"
    >
      <!-- Logo Section -->
      <div class="h-16 flex items-center justify-between px-5 border-b border-slate-800">
        <router-link
          :to="{ name: 'Dashboard' }"
          class="flex items-center gap-3 hover:opacity-80 transition-opacity cursor-pointer"
          @click="sidebarOpen = false"
        >
          <div class="w-9 h-9 bg-emerald-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
          </div>
          <span v-if="!sidebarCollapsed" class="text-lg font-bold text-white tracking-tight">ROKAPASS</span>
        </router-link>
        <button
          @click="sidebarCollapsed = !sidebarCollapsed"
          class="hidden lg:flex w-8 h-8 items-center justify-center text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors"
        >
          <svg :class="['w-4 h-4 transition-transform', sidebarCollapsed ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 py-6 px-3 space-y-1 overflow-y-auto">
        <router-link
          v-for="item in navigation"
          :key="item.route"
          :to="{ name: item.route }"
          :class="[
            'group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200',
            isActive(item.route)
              ? 'bg-emerald-500/20 text-emerald-400'
              : 'text-slate-400 hover:bg-slate-800 hover:text-white'
          ]"
          @click="sidebarOpen = false"
          :title="sidebarCollapsed ? item.name : ''"
        >
          <!-- Icons -->
          <div :class="['w-9 h-9 flex items-center justify-center rounded-lg transition-colors flex-shrink-0',
            isActive(item.route) ? 'bg-emerald-500/20' : 'bg-slate-800 group-hover:bg-slate-700']">
            <svg v-if="item.icon === 'dashboard'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <svg v-else-if="item.icon === 'calendar'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <svg v-else-if="item.icon === 'document'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <svg v-else-if="item.icon === 'clipboard'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <svg v-else-if="item.icon === 'qrcode'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
            <svg v-else-if="item.icon === 'settings'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <svg v-else-if="item.icon === 'help'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <svg v-else-if="item.icon === 'book'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
          </div>
          <span v-if="!sidebarCollapsed" class="font-medium">{{ item.name }}</span>
        </router-link>
      </nav>

      <!-- User Section -->
      <div class="border-t border-slate-800 p-4">
        <div :class="['flex items-center gap-3', sidebarCollapsed ? 'justify-center' : '']">
          <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center flex-shrink-0">
            <span class="text-white font-semibold text-sm">
              {{ authStore.adminName?.charAt(0) || 'A' }}
            </span>
          </div>
          <div v-if="!sidebarCollapsed" class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate">{{ authStore.adminName }}</p>
            <p class="text-xs text-slate-400 truncate">{{ authStore.adminRole === 'super_admin' ? '최고관리자' : '관리자' }}</p>
          </div>
          <button
            v-if="!sidebarCollapsed"
            @click="handleLogout"
            class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors"
            title="로그아웃"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div :class="['transition-all duration-300', sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72']">
      <!-- Top Bar -->
      <header class="sticky top-0 z-30 h-16 bg-white/80 backdrop-blur-md border-b border-slate-200 flex items-center px-4 lg:px-8">
        <!-- Mobile Menu Button -->
        <button
          @click="toggleSidebar"
          class="lg:hidden p-2 -ml-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>

        <!-- Page Title -->
        <div class="ml-4 lg:ml-0">
          <h1 class="text-lg font-semibold text-slate-900">{{ pageTitle }}</h1>
        </div>

        <div class="flex-1"></div>

        <!-- Right Side -->
        <div class="flex items-center gap-3">
          <!-- Current Time -->
          <div class="hidden sm:flex items-center gap-2 text-sm text-slate-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ new Date().toLocaleDateString('ko-KR', { month: 'long', day: 'numeric', weekday: 'short' }) }}</span>
          </div>

          <!-- User Avatar (Mobile) -->
          <div class="lg:hidden w-8 h-8 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-semibold text-xs">
              {{ authStore.adminName?.charAt(0) || 'A' }}
            </span>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="p-4 lg:p-8">
        <slot />
      </main>
    </div>
  </div>
</template>
