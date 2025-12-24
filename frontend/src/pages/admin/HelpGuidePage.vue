<script setup>
import { ref } from 'vue'
import AdminLayout from '@/components/admin/AdminLayout.vue'

const expandedSections = ref(['dashboard'])

function toggleSection(key) {
  const index = expandedSections.value.indexOf(key)
  if (index > -1) {
    expandedSections.value.splice(index, 1)
  } else {
    expandedSections.value.push(key)
  }
}

function isExpanded(key) {
  return expandedSections.value.includes(key)
}

const sections = [
  {
    key: 'dashboard',
    title: '대시보드',
    icon: 'squares',
    description: '시스템 현황을 한눈에 파악할 수 있는 메인 화면입니다.',
    steps: [
      {
        title: '대시보드 화면 구성',
        image: '/help-images/dashboard-main.png',
        description: '대시보드에서는 현재 진행 중인 훈련, 오늘의 통계, 최근 응답 목록을 확인할 수 있습니다.',
        items: [
          '진행중 훈련: 현재 활성화된 훈련 정보',
          '오늘의 통계: 금일 입소자 수, 문진 결과 현황',
          '최근 응답: 가장 최근에 등록된 응답 목록'
        ]
      }
    ]
  },
  {
    key: 'trainings',
    title: '훈련 관리',
    icon: 'calendar',
    description: '예비군 훈련 일정을 생성하고 관리합니다.',
    steps: [
      {
        title: '훈련 목록 화면',
        image: '/help-images/training-list.png',
        description: '등록된 모든 훈련 목록을 확인하고 관리할 수 있습니다.',
        items: [
          '상태 필터: 전체/진행중/예정/완료/파기됨 별로 필터링',
          '상태별 배지: 각 훈련의 현재 상태 표시',
          '액션 버튼: QR코드 보기, 수정, 상태 변경, 삭제'
        ]
      },
      {
        title: '새 훈련 추가',
        image: '/help-images/training-create.png',
        description: '우측 상단의 "새 훈련 추가" 버튼을 클릭하여 훈련을 생성합니다.',
        items: [
          '훈련명: 예비군 훈련의 이름 입력',
          '시작일/종료일: 훈련 기간 설정 (1일 훈련은 같은 날짜 선택)',
          '퇴소 모드: 자동(스캔시 즉시 퇴소) 또는 확인(관리자 확인 후 퇴소)',
          '중식 이미지: 요일별 중식 메뉴 이미지 업로드 (선택사항)'
        ],
        tip: '2박 3일 훈련의 경우 시작일과 종료일을 다르게 설정하세요.'
      },
      {
        title: '훈련 상태 관리',
        description: '훈련은 다음과 같은 상태로 관리됩니다.',
        items: [
          '예정(scheduled): 훈련 생성 직후 기본 상태, QR코드 스캔 불가',
          '진행중(active): "시작" 버튼 클릭 후, 예비군 문진표 작성 가능',
          '일시정지(paused): 임시 중단 상태, QR코드 스캔 불가',
          '완료(completed): 훈련 종료 상태',
          '파기됨(purged): 개인정보 파기 완료 상태'
        ],
        tip: '훈련 시작 전 반드시 QR코드를 출력하여 훈련장에 게시하세요.'
      },
      {
        title: 'QR 코드 사용',
        description: 'QR 코드 버튼을 클릭하면 예비군이 스캔할 수 있는 QR 코드가 표시됩니다.',
        items: [
          'QR 코드 다운로드: PNG 이미지로 저장하여 인쇄 가능',
          '접속 URL: QR 코드 스캔 시 이동하는 주소',
          '접속 코드: 수동 입력용 6자리 코드'
        ],
        tip: 'QR 코드는 훈련장 입구에 잘 보이도록 게시하세요.'
      }
    ]
  },
  {
    key: 'responses',
    title: '응답 관리',
    icon: 'document',
    description: '예비군들이 작성한 문진표 응답을 관리합니다.',
    steps: [
      {
        title: '응답 목록 화면',
        image: '/help-images/response-list.png',
        description: '등록된 모든 문진표 응답을 확인하고 관리할 수 있습니다.',
        items: [
          '훈련 필터: 특정 훈련의 응답만 필터링',
          '문진 결과 필터: 정상/주의/위험 별 필터링',
          '출결 상태 필터: 입소전/입소/퇴소/불참 별 필터링',
          '검색: 이름으로 응답자 검색',
          '엑셀 다운로드: 필터링된 결과를 Excel 파일로 내보내기'
        ]
      },
      {
        title: '응답 상세 보기',
        description: '응답 행을 클릭하면 상세 정보를 확인할 수 있습니다.',
        items: [
          '보기 탭: 인적사항, 문진 결과, 답변 내역 확인',
          '수정 탭: 인적사항 및 문진 결과 수정 (군의관 면담 후)',
          '재문진 탭: 답변을 다시 선택하여 결과 재계산'
        ]
      },
      {
        title: '문진 결과 수정',
        description: '군의관 면담 후 문진 결과를 수동으로 변경할 수 있습니다.',
        items: [
          '수정 사유 입력: 변경 사유를 기록 (감사 추적용)',
          '결과 변경: 정상/주의/위험 중 선택',
          '이력 관리: 모든 변경 내역이 기록됨'
        ],
        tip: '문진 결과 변경 시 반드시 사유를 입력하세요. 모든 변경은 감사 로그에 기록됩니다.'
      }
    ]
  },
  {
    key: 'questions',
    title: '문진표 관리',
    icon: 'clipboard',
    description: '문진표 질문 항목을 관리합니다.',
    steps: [
      {
        title: '질문 목록 화면',
        image: '/help-images/question-list.png',
        description: '문진표에 포함된 질문 목록을 관리할 수 있습니다.',
        items: [
          '드래그 앤 드롭: 질문 순서 변경',
          '활성화 토글: 질문 사용 여부 설정',
          '수정 버튼: 질문 내용 및 옵션 편집',
          '삭제 버튼: 질문 삭제 (기존 응답에는 영향 없음)'
        ]
      },
      {
        title: '질문 수정',
        description: '질문을 수정할 때 옵션 설정에 주의하세요.',
        items: [
          '질문 텍스트: 예비군에게 보여지는 질문 문구',
          '옵션 목록: 선택 가능한 답변 항목',
          '위험 옵션: 선택 시 위험 카운트에 포함되는 옵션',
          '옵션 값: 내부 저장용 값 (변경 시 주의)'
        ],
        tip: '이미 응답이 있는 질문을 수정하면 기존 응답의 표시에 영향을 줄 수 있습니다.'
      }
    ]
  },
  {
    key: 'scanner',
    title: '퇴소 스캐너',
    icon: 'qrcode',
    description: '예비군 퇴소 처리를 위한 QR 스캐너입니다.',
    steps: [
      {
        title: '스캐너 화면',
        image: '/help-images/scanner-main.png',
        description: '카메라를 사용하여 예비군의 QR 코드를 스캔합니다.',
        items: [
          '훈련 선택: 퇴소 처리할 훈련 선택',
          '카메라 스캔: 예비군 휴대폰의 QR 코드 스캔',
          '수동 검색: 이름으로 검색하여 퇴소 처리'
        ]
      },
      {
        title: '퇴소 처리 모드',
        description: '훈련 설정에 따라 퇴소 처리 방식이 다릅니다.',
        items: [
          '자동 모드: QR 스캔 즉시 퇴소 처리 완료',
          '확인 모드: QR 스캔 후 관리자가 확인 버튼 클릭 필요'
        ],
        tip: '대규모 훈련에서는 자동 모드가, 소규모 훈련에서는 확인 모드가 적합합니다.'
      }
    ]
  },
  {
    key: 'workflow',
    title: '전체 워크플로우',
    icon: 'book',
    description: '훈련 진행 전체 과정을 안내합니다.',
    steps: [
      {
        title: '1단계: 훈련 생성',
        description: '훈련 관리 페이지에서 새 훈련을 생성합니다.',
        items: [
          '훈련명, 기간, 퇴소 모드 설정',
          '필요시 중식 이미지 업로드'
        ]
      },
      {
        title: '2단계: QR 코드 준비',
        description: '생성된 훈련의 QR 코드를 출력합니다.',
        items: [
          'QR 코드 다운로드 및 인쇄',
          '훈련장 입구에 게시'
        ]
      },
      {
        title: '3단계: 훈련 시작',
        description: '훈련 당일 "시작" 버튼을 클릭하여 활성화합니다.',
        items: [
          '훈련 상태가 "진행중"으로 변경됨',
          '예비군 문진표 작성 가능 상태'
        ]
      },
      {
        title: '4단계: 예비군 입소',
        description: '예비군이 QR 코드를 스캔하여 문진표를 작성합니다.',
        items: [
          'QR 스캔 → 인적사항 입력 → 문진표 작성',
          '결과에 따라 정상/주의/위험 판정'
        ]
      },
      {
        title: '5단계: 응답 관리',
        description: '관리자가 응답을 모니터링하고 필요시 조치합니다.',
        items: [
          '위험/주의 판정자 면담',
          '필요시 문진 결과 수정'
        ]
      },
      {
        title: '6단계: 퇴소 처리',
        description: '훈련 종료 시 퇴소 스캐너로 퇴소 처리합니다.',
        items: [
          '예비군 QR 코드 스캔',
          '출결 상태 "퇴소"로 변경'
        ]
      },
      {
        title: '7단계: 훈련 종료',
        description: '모든 예비군 퇴소 후 훈련을 종료합니다.',
        items: [
          '"종료" 버튼 클릭',
          '필요시 엑셀로 결과 내보내기',
          '개인정보 보호를 위해 일정 기간 후 파기'
        ]
      }
    ]
  }
]

const iconComponents = {
  squares: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>`,
  calendar: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>`,
  document: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>`,
  clipboard: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>`,
  qrcode: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h2M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>`,
  book: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>`
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-slate-800">사용 가이드</h1>
            <p class="text-slate-500 mt-1">ROKAPASS 관리자 시스템 사용 방법을 안내합니다.</p>
          </div>
        </div>
      </div>

      <!-- Guide Sections -->
      <div class="space-y-4">
        <div
          v-for="section in sections"
          :key="section.key"
          class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden"
        >
          <!-- Section Header -->
          <button
            @click="toggleSection(section.key)"
            class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors"
          >
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <g v-html="iconComponents[section.icon]"></g>
                </svg>
              </div>
              <div class="text-left">
                <h2 class="text-lg font-semibold text-slate-800">{{ section.title }}</h2>
                <p class="text-sm text-slate-500">{{ section.description }}</p>
              </div>
            </div>
            <svg
              class="w-5 h-5 text-slate-400 transition-transform duration-200"
              :class="{ 'rotate-180': isExpanded(section.key) }"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          <!-- Section Content -->
          <div
            v-show="isExpanded(section.key)"
            class="px-6 pb-6 border-t border-slate-100"
          >
            <div class="space-y-8 pt-6">
              <div
                v-for="(step, stepIndex) in section.steps"
                :key="stepIndex"
                class="relative"
              >
                <!-- Step Number -->
                <div class="flex items-start gap-4">
                  <div
                    v-if="section.steps.length > 1"
                    class="flex-shrink-0 w-8 h-8 bg-emerald-500 text-white rounded-full flex items-center justify-center font-bold text-sm"
                  >
                    {{ stepIndex + 1 }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <!-- Step Title -->
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">
                      {{ step.title }}
                    </h3>

                    <!-- Step Image -->
                    <div v-if="step.image" class="mb-4">
                      <img
                        :src="step.image"
                        :alt="step.title"
                        class="rounded-lg border border-slate-200 shadow-sm w-full max-w-4xl"
                      />
                    </div>

                    <!-- Step Description -->
                    <p class="text-slate-600 mb-3">{{ step.description }}</p>

                    <!-- Step Items -->
                    <ul v-if="step.items" class="space-y-2 mb-4">
                      <li
                        v-for="(item, itemIndex) in step.items"
                        :key="itemIndex"
                        class="flex items-start gap-2 text-slate-600"
                      >
                        <span class="text-emerald-500 mt-1">•</span>
                        <span>{{ item }}</span>
                      </li>
                    </ul>

                    <!-- Step Tip -->
                    <div
                      v-if="step.tip"
                      class="bg-blue-50 border border-blue-200 rounded-lg p-4"
                    >
                      <div class="flex items-start gap-2">
                        <span class="text-blue-500 text-lg">💡</span>
                        <p class="text-blue-700 text-sm">{{ step.tip }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Connector Line -->
                <div
                  v-if="stepIndex < section.steps.length - 1 && section.steps.length > 1"
                  class="absolute left-4 top-10 w-0.5 h-full bg-slate-200 -translate-x-1/2"
                  style="height: calc(100% - 2rem)"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">바로가기</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <router-link
            to="/admin/dashboard"
            class="flex items-center gap-3 p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors"
          >
            <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <g v-html="iconComponents['squares']"></g>
              </svg>
            </div>
            <span class="font-medium text-slate-700">대시보드</span>
          </router-link>

          <router-link
            to="/admin/trainings"
            class="flex items-center gap-3 p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors"
          >
            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <g v-html="iconComponents['calendar']"></g>
              </svg>
            </div>
            <span class="font-medium text-slate-700">훈련 관리</span>
          </router-link>

          <router-link
            to="/admin/responses"
            class="flex items-center gap-3 p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors"
          >
            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <g v-html="iconComponents['document']"></g>
              </svg>
            </div>
            <span class="font-medium text-slate-700">응답 관리</span>
          </router-link>

          <router-link
            to="/admin/exit-scanner"
            class="flex items-center gap-3 p-4 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors"
          >
            <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <g v-html="iconComponents['qrcode']"></g>
              </svg>
            </div>
            <span class="font-medium text-slate-700">퇴소 스캐너</span>
          </router-link>
        </div>
      </div>

      <!-- Contact -->
      <div class="bg-slate-800 rounded-xl shadow-sm p-6 text-white">
        <h2 class="text-lg font-semibold mb-2">추가 지원이 필요하신가요?</h2>
        <p class="text-slate-300 text-sm">
          시스템 사용 중 문의사항이 있으시면 관리자에게 연락해 주세요.
        </p>
      </div>
    </div>
  </AdminLayout>
</template>
