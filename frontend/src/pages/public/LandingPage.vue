<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSurveyStore } from '@/stores/survey'

const router = useRouter()
const surveyStore = useSurveyStore()

onMounted(() => {
  // 기존 세션이 있는지 확인
  if (surveyStore.loadFromStorage()) {
    router.push({ name: 'QR' })
  }
})
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex flex-col">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
      <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;0.4&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <!-- Header -->
    <header class="relative py-8 px-4 text-center">
      <div class="w-16 h-16 bg-emerald-500/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4 border border-emerald-500/30">
        <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
      </div>
      <h1 class="text-3xl font-bold text-white tracking-tight">ROKAPASS</h1>
      <p class="text-slate-400 mt-2">예비군 One-Step 입소 시스템</p>
    </header>

    <!-- Main Content -->
    <main class="relative flex-1 flex flex-col items-center justify-center px-4 pb-8">
      <div class="w-full max-w-md">
        <!-- QR Scan Guide Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 mb-6">
          <!-- QR Icon -->
          <div class="flex items-center justify-center mb-6">
            <div class="w-24 h-24 bg-gradient-to-br from-emerald-100 to-emerald-50 rounded-2xl flex items-center justify-center">
              <svg class="w-14 h-14 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
              </svg>
            </div>
          </div>

          <!-- Instructions -->
          <div class="text-center mb-8">
            <h2 class="text-xl font-bold text-slate-900 mb-3">QR 코드를 스캔하세요</h2>
            <p class="text-slate-500 leading-relaxed">
              훈련장에 비치된 QR 코드를 스캔하여<br/>
              입소 절차를 시작하세요.
            </p>
          </div>

          <!-- Steps -->
          <div class="bg-slate-50 rounded-xl p-5 space-y-3">
            <div class="flex items-center gap-3">
              <span class="w-8 h-8 bg-emerald-500 text-white rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">1</span>
              <span class="text-slate-700">훈련장 QR 코드 스캔</span>
            </div>
            <div class="flex items-center gap-3">
              <span class="w-8 h-8 bg-emerald-500 text-white rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">2</span>
              <span class="text-slate-700">개인정보 및 문진표 작성</span>
            </div>
            <div class="flex items-center gap-3">
              <span class="w-8 h-8 bg-emerald-500 text-white rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">3</span>
              <span class="text-slate-700">본인 QR 코드 발급 완료</span>
            </div>
          </div>
        </div>

        <!-- QR Reissue Link -->
        <div class="text-center">
          <router-link
            :to="{ name: 'Reissue' }"
            class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors text-sm"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
            이미 작성하셨나요? QR코드 재발급
          </router-link>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="relative py-6 text-center text-slate-500 text-sm">
      <p>ROKAPASS - 예비군 One-Step 입소 시스템</p>
    </footer>
  </div>
</template>
