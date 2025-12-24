import { createRouter, createWebHistory } from 'vue-router'

// Public pages (예비군)
const LandingPage = () => import('@/pages/public/LandingPage.vue')
const TrainingEntryPage = () => import('@/pages/public/TrainingEntryPage.vue')
const FormPage = () => import('@/pages/public/FormPage.vue')
const SurveyPage = () => import('@/pages/public/SurveyPage.vue')
const ResultPage = () => import('@/pages/public/ResultPage.vue')
const QRPage = () => import('@/pages/public/QRPage.vue')
const ReissuePage = () => import('@/pages/public/ReissuePage.vue')

// Kiosk pages
const KioskMainPage = () => import('@/pages/kiosk/KioskMainPage.vue')
const KioskSetupPage = () => import('@/pages/kiosk/KioskSetupPage.vue')

// Admin pages
const AdminLoginPage = () => import('@/pages/admin/LoginPage.vue')
const DashboardPage = () => import('@/pages/admin/DashboardPage.vue')
const TrainingsPage = () => import('@/pages/admin/TrainingsPage.vue')
const ResponsesPage = () => import('@/pages/admin/ResponsesPage.vue')
const QuestionsPage = () => import('@/pages/admin/QuestionsPage.vue')
const SettingsPage = () => import('@/pages/admin/SettingsPage.vue')
const ExitScannerPage = () => import('@/pages/admin/ExitScannerPage.vue')
const HelpPage = () => import('@/pages/admin/HelpPage.vue')
const HelpGuidePage = () => import('@/pages/admin/HelpGuidePage.vue')

const routes = [
  // Public routes (예비군용)
  {
    path: '/',
    name: 'Landing',
    component: LandingPage,
  },
  // Training entry via QR code (새로운 흐름)
  {
    path: '/t/:code',
    name: 'TrainingEntry',
    component: TrainingEntryPage,
  },
  {
    path: '/form',
    name: 'Form',
    component: FormPage,
  },
  {
    path: '/survey',
    name: 'Survey',
    component: SurveyPage,
  },
  {
    path: '/result',
    name: 'Result',
    component: ResultPage,
  },
  {
    path: '/qr',
    name: 'QR',
    component: QRPage,
  },
  {
    path: '/reissue',
    name: 'Reissue',
    component: ReissuePage,
  },

  // Kiosk routes
  {
    path: '/kiosk',
    name: 'Kiosk',
    component: KioskMainPage,
  },
  {
    path: '/kiosk/setup',
    name: 'KioskSetup',
    component: KioskSetupPage,
  },

  // Admin routes
  {
    path: '/admin',
    redirect: '/admin/login',
  },
  {
    path: '/admin/login',
    name: 'AdminLogin',
    component: AdminLoginPage,
  },
  {
    path: '/admin/dashboard',
    name: 'Dashboard',
    component: DashboardPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/trainings',
    name: 'Trainings',
    component: TrainingsPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/responses',
    name: 'Responses',
    component: ResponsesPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/questions',
    name: 'Questions',
    component: QuestionsPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/settings',
    name: 'Settings',
    component: SettingsPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/exit-scanner',
    name: 'ExitScanner',
    component: ExitScannerPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/help',
    name: 'Help',
    component: HelpPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/guide',
    name: 'HelpGuide',
    component: HelpGuidePage,
    meta: { requiresAuth: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const isAuthenticated = localStorage.getItem('admin_token')

  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ name: 'AdminLogin' })
  } else {
    next()
  }
})

export default router
