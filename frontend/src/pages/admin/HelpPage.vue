<script setup>
import { ref } from 'vue'
import AdminLayout from '@/components/admin/AdminLayout.vue'

const expandedSections = ref({
  overview: true,
  dashboard: false,
  trainings: false,
  responses: false,
  questions: false,
  exitScanner: false,
  settings: false,
  workflow: false,
})

function toggleSection(key) {
  expandedSections.value[key] = !expandedSections.value[key]
}

function scrollToSection(key) {
  expandedSections.value[key] = true
  setTimeout(() => {
    const el = document.getElementById(key)
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }, 100)
}

const sections = [
  {
    key: 'overview',
    title: '시스템 개요',
    icon: 'info',
    content: {
      description: 'ROKAPASS는 예비군 훈련 입소 관리 시스템입니다. 예비군이 문진표를 작성하고 QR코드를 발급받아 입소/퇴소를 관리할 수 있습니다.',
      features: [
        { title: '문진표 기반 입소 관리', desc: '예비군이 모바일로 문진표를 작성하면 자동으로 결과(정상/주의/위험)가 판정됩니다.' },
        { title: 'QR코드 입퇴소', desc: '문진 완료 후 QR코드가 발급되어 입소 및 퇴소 시 스캔으로 관리됩니다.' },
        { title: '실시간 현황 파악', desc: '대시보드에서 입소/퇴소 현황과 문진 결과를 실시간으로 확인할 수 있습니다.' },
      ],
    },
  },
  {
    key: 'workflow',
    title: '기본 운영 흐름',
    icon: 'flow',
    content: {
      description: '훈련 당일 운영하는 기본 흐름입니다.',
      steps: [
        { num: 1, title: '훈련 생성 및 활성화', desc: '훈련 관리에서 새 훈련을 만들고 "활성화" 버튼을 클릭합니다.' },
        { num: 2, title: '예비군 문진표 작성', desc: '예비군이 모바일로 문진표를 작성합니다. 정상/주의는 QR 발급, 위험은 군의관 면담이 필요합니다.' },
        { num: 3, title: '입소 확인', desc: 'QR 발급 시 자동으로 입소 처리됩니다. (설정에서 변경 가능)' },
        { num: 4, title: '퇴소 처리', desc: '훈련 종료 시 퇴소 스캐너로 QR을 스캔하면 퇴소 처리됩니다.' },
        { num: 5, title: '훈련 종료', desc: '훈련 관리에서 "종료" 버튼을 클릭하여 훈련을 마감합니다.' },
      ],
    },
  },
  {
    key: 'dashboard',
    title: '대시보드',
    icon: 'dashboard',
    content: {
      description: '훈련 현황을 한눈에 볼 수 있는 메인 화면입니다.',
      features: [
        { title: '통계 카드', desc: '오늘 등록, 입소, 퇴소, 중식 신청 인원을 실시간으로 표시합니다. 각 카드를 클릭하면 해당 필터가 적용된 응답 관리 페이지로 이동합니다.' },
        { title: '문진 결과 현황', desc: '정상/주의/위험 판정 인원 수를 보여줍니다. 클릭하면 해당 결과로 필터링된 응답 목록을 볼 수 있습니다.' },
        { title: '최근 등록', desc: '최근 등록된 예비군 목록을 표시합니다. 행을 클릭하면 상세 정보를 볼 수 있습니다.' },
        { title: '훈련 선택', desc: '상단의 훈련 선택 드롭다운으로 특정 훈련의 통계만 볼 수 있습니다.' },
      ],
    },
  },
  {
    key: 'trainings',
    title: '훈련 관리',
    icon: 'calendar',
    content: {
      description: '예비군 훈련을 생성하고 관리하는 페이지입니다.',
      features: [
        { title: '훈련 생성', desc: '"새 훈련 만들기" 버튼으로 훈련을 생성합니다. 훈련명, 날짜, 장소, 대상 인원을 입력하세요.' },
        { title: '훈련 상태', desc: '예정됨 → 진행 중 → 완료 순으로 상태가 변경됩니다. 일시정지도 가능합니다.' },
        { title: '활성화', desc: '"활성화" 버튼을 누르면 예비군이 해당 훈련에 문진표를 제출할 수 있습니다.' },
        { title: '일시정지', desc: '"일시정지" 버튼으로 훈련을 잠시 중단할 수 있습니다. 문진 제출이 차단됩니다.' },
        { title: '종료', desc: '"종료" 버튼으로 훈련을 완료 처리합니다. 종료된 훈련에는 더 이상 응답할 수 없습니다.' },
        { title: 'QR 코드', desc: '훈련 카드의 QR 아이콘을 클릭하면 해당 훈련 접속용 QR코드를 볼 수 있습니다.' },
      ],
      warnings: [
        '훈련이 "예정됨" 상태에서는 문진표 제출이 불가합니다. 반드시 "활성화"를 해주세요.',
        '진행 중인 훈련을 삭제하면 해당 훈련의 모든 응답 데이터도 삭제됩니다.',
      ],
    },
  },
  {
    key: 'responses',
    title: '응답 관리',
    icon: 'document',
    content: {
      description: '예비군이 제출한 문진표 응답을 관리하는 페이지입니다.',
      features: [
        { title: '필터링', desc: '훈련, 결과(정상/주의/위험), 상태(대기/입소/퇴소), 중식 신청 여부, 이름으로 필터링할 수 있습니다.' },
        { title: '상세 보기', desc: '행을 클릭하면 인적사항, 문진 답변, 시간 정보를 상세히 볼 수 있습니다.' },
        { title: '수정', desc: '"수정" 탭에서 인적사항(이름, 생년월일, 연락처, 계좌정보)을 수정할 수 있습니다.' },
        { title: '결과 직접 변경', desc: '"수정" 탭에서 문진 결과를 직접 변경할 수 있습니다. (예: 군의관 면담 후 위험→정상)' },
        { title: '재문진', desc: '"재문진" 탭에서 문진표를 다시 작성할 수 있습니다. 결과가 자동으로 재계산됩니다.' },
        { title: '삭제', desc: '잘못 등록된 응답을 삭제할 수 있습니다. 삭제된 데이터는 복구할 수 없습니다.' },
        { title: '엑셀 내보내기', desc: '"엑셀 내보내기" 버튼으로 현재 필터가 적용된 응답을 Excel 파일로 다운로드합니다.' },
      ],
      tips: [
        '"위험" 판정자는 QR 발급이 차단됩니다. 군의관 면담 후 "재문진" 또는 "결과 직접 변경"으로 정상/주의로 변경하면 QR 재발급이 가능합니다.',
        '응답의 빨간색 테두리는 위험 항목에 해당하는 답변을 표시합니다.',
      ],
    },
  },
  {
    key: 'questions',
    title: '문진표 관리',
    icon: 'clipboard',
    content: {
      description: '예비군에게 표시되는 문진표 질문을 관리합니다.',
      features: [
        { title: '질문 추가', desc: '"질문 추가" 버튼으로 새 질문을 만듭니다. 질문 내용과 선택지를 입력하세요.' },
        { title: '위험 항목 설정', desc: '각 선택지에서 "위험 항목"을 체크하면 해당 답변 선택 시 위험 카운트가 증가합니다.' },
        { title: '순서 변경', desc: '질문 카드를 드래그하여 순서를 변경할 수 있습니다.' },
        { title: '활성화/비활성화', desc: '토글 스위치로 질문을 숨기거나 표시할 수 있습니다. 비활성화된 질문은 예비군에게 보이지 않습니다.' },
        { title: '수정/삭제', desc: '기존 질문을 수정하거나 삭제할 수 있습니다.' },
      ],
      warnings: [
        '문진 결과는 위험 항목 수로 판정됩니다: 0개=정상, 1개=주의, 2개 이상=위험',
        '질문을 삭제해도 이미 제출된 응답에는 영향이 없습니다. (응답에 질문 내용이 스냅샷으로 저장됨)',
      ],
    },
  },
  {
    key: 'exitScanner',
    title: '퇴소 스캐너',
    icon: 'qrcode',
    content: {
      description: '훈련 종료 시 예비군의 QR코드를 스캔하여 퇴소 처리하는 페이지입니다.',
      features: [
        { title: '훈련 선택', desc: '상단에서 퇴소 처리할 훈련을 선택합니다. 해당 훈련에 등록된 예비군만 퇴소 처리됩니다.' },
        { title: 'QR 스캔', desc: '예비군의 QR코드를 카메라로 스캔합니다. 자동으로 인식됩니다.' },
        { title: '퇴소 확인', desc: '스캔 후 예비군 정보가 표시되며, "퇴소 처리" 버튼을 누르면 퇴소가 완료됩니다.' },
        { title: '음성 안내', desc: '퇴소 처리 완료 시 음성으로 안내됩니다. (TTS 지원 브라우저에서)' },
      ],
      tips: [
        '이미 퇴소 처리된 예비군을 다시 스캔하면 "이미 퇴소 처리됨" 메시지가 표시됩니다.',
        '다른 훈련에 등록된 QR코드는 인식되지 않습니다. 훈련 선택을 확인하세요.',
      ],
    },
  },
  {
    key: 'settings',
    title: '설정',
    icon: 'settings',
    content: {
      description: '시스템 설정을 관리합니다.',
      features: [
        { title: 'QR 발급 시 자동 입소', desc: '활성화하면 QR 발급 시 자동으로 "입소" 상태가 됩니다. 비활성화하면 "대기" 상태로 남습니다.' },
        { title: '비밀번호 변경', desc: '현재 비밀번호를 확인 후 새 비밀번호로 변경할 수 있습니다.' },
        { title: '관리자 관리', desc: '(최고관리자만) 새 관리자 계정을 추가하거나 기존 계정을 수정/삭제할 수 있습니다.' },
      ],
    },
  },
]

function getIcon(iconName) {
  const icons = {
    info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    flow: 'M13 5l7 7-7 7M5 5l7 7-7 7',
    dashboard: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
    calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    document: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    clipboard: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
    qrcode: 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z',
    settings: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
  }
  return icons[iconName] || icons.info
}
</script>

<template>
  <AdminLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Page Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-100 rounded-2xl mb-4">
          <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-slate-900 mb-2">도움말</h1>
        <p class="text-slate-500">ROKAPASS 관리자 시스템 사용 가이드</p>
      </div>

      <!-- Quick Links -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4">빠른 이동</h3>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="section in sections"
            :key="section.key"
            @click="scrollToSection(section.key)"
            class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIcon(section.icon)"/>
            </svg>
            {{ section.title }}
          </button>
        </div>
      </div>

      <!-- Sections -->
      <div class="space-y-4">
        <div
          v-for="section in sections"
          :key="section.key"
          :id="section.key"
          class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden"
        >
          <!-- Section Header -->
          <button
            @click="toggleSection(section.key)"
            class="w-full flex items-center justify-between p-5 hover:bg-slate-50 transition-colors"
          >
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIcon(section.icon)"/>
                </svg>
              </div>
              <h2 class="text-lg font-semibold text-slate-900">{{ section.title }}</h2>
            </div>
            <svg
              :class="['w-5 h-5 text-slate-400 transition-transform', expandedSections[section.key] ? 'rotate-180' : '']"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          <!-- Section Content -->
          <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-[2000px]"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 max-h-[2000px]"
            leave-to-class="opacity-0 max-h-0"
          >
            <div v-if="expandedSections[section.key]" class="px-5 pb-5 overflow-hidden">
              <!-- Description -->
              <p class="text-slate-600 mb-4 pl-14">{{ section.content.description }}</p>

              <!-- Steps (for workflow) -->
              <div v-if="section.content.steps" class="space-y-3 mb-4">
                <div
                  v-for="step in section.content.steps"
                  :key="step.num"
                  class="flex items-start gap-4 pl-14"
                >
                  <div class="w-8 h-8 bg-emerald-500 text-white rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">
                    {{ step.num }}
                  </div>
                  <div>
                    <h4 class="font-medium text-slate-900">{{ step.title }}</h4>
                    <p class="text-sm text-slate-500">{{ step.desc }}</p>
                  </div>
                </div>
              </div>

              <!-- Features -->
              <div v-if="section.content.features" class="space-y-3 mb-4">
                <div
                  v-for="(feature, idx) in section.content.features"
                  :key="idx"
                  class="flex items-start gap-3 pl-14"
                >
                  <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full mt-2 flex-shrink-0"></div>
                  <div>
                    <span class="font-medium text-slate-900">{{ feature.title }}:</span>
                    <span class="text-slate-600"> {{ feature.desc }}</span>
                  </div>
                </div>
              </div>

              <!-- Tips -->
              <div v-if="section.content.tips" class="pl-14 mt-4">
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                  <div class="flex items-center gap-2 text-blue-700 font-medium mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    팁
                  </div>
                  <ul class="space-y-1">
                    <li v-for="(tip, idx) in section.content.tips" :key="idx" class="text-sm text-blue-700">
                      • {{ tip }}
                    </li>
                  </ul>
                </div>
              </div>

              <!-- Warnings -->
              <div v-if="section.content.warnings" class="pl-14 mt-4">
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                  <div class="flex items-center gap-2 text-amber-700 font-medium mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    주의사항
                  </div>
                  <ul class="space-y-1">
                    <li v-for="(warning, idx) in section.content.warnings" :key="idx" class="text-sm text-amber-700">
                      • {{ warning }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-center py-8 text-slate-400 text-sm">
        <p>ROKAPASS 예비군 입소 관리 시스템</p>
        <p class="mt-1">문의사항이 있으시면 시스템 관리자에게 연락하세요.</p>
      </div>
    </div>
  </AdminLayout>
</template>
