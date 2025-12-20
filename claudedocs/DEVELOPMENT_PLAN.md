# 예비군 One-Step 입소 시스템 - 종합 개발 계획서

**버전**: v1.1
**작성일**: 2025-07-08
**최종수정**: 2025-07-08
**프로젝트명**: ROKAPASS

---

## 1. 기획서 분석 및 보완사항

### 1.1 원본 기획서 핵심 요약

| 구분 | 내용 |
|------|------|
| **목적** | 예비군 입소 시간 단축, 수기 문진 오류 방지, 행정 자동화 |
| **사용자** | 예비군(모바일 웹), 관리자(부대 담당자 웹) |
| **기술 스택** | Laravel / Vue.js |
| **핵심 플로우** | QR 스캔 → 동의 → 정보입력 → 문진 → 결과 → QR 카드 생성 |

### 1.2 보완이 필요한 영역

| 영역 | 기획서 현황 | 보완 내용 |
|------|------------|----------|
| **인증 시스템** | 미정의 | Laravel Sanctum 기반 SPA 인증 |
| **권한 관리** | 미정의 | 역할 기반 접근 제어 (RBAC) |
| **감사 로그** | 미정의 | 모든 데이터 접근/변경 로깅 |
| **에러 처리** | 미정의 | 표준화된 에러 응답 체계 |
| **테스트 전략** | 미정의 | Unit/Feature/E2E 테스트 계획 |
| **배포 전략** | 미정의 | 스마일서브 Iwinv 배포 파이프라인 |
| **API 설계** | 개념만 언급 | RESTful API 상세 명세 |
| **훈련 관리** | 단순 설정만 언급 | 훈련별 독립 데이터 관리 |
| **다중 부대** | 미고려 | 향후 확장성 고려 설계 |
| **입소/퇴소 관리** | 미정의 | 자동 입소 + QR 스캔 퇴소 시스템 |
| **퇴소 키오스크** | 미정의 | 태블릿 키오스크 모드 (자동/확인 퇴소) |
| **QR 재발급** | 미정의 | 본인 확인 후 기존 QR 재발급 |
| **요일별 중식** | 단일 이미지만 언급 | 월~금 요일별 중식 이미지 관리 |

---

## 2. 시스템 아키텍처

### 2.1 전체 구조도

```
┌─────────────────────────────────────────────────────────────────┐
│                        클라이언트 레이어                          │
├─────────────────────────────────────────────────────────────────┤
│  [예비군 모바일 웹]              [관리자 웹 대시보드]              │
│   - Vue.js 3 SPA                - Vue.js 3 SPA                 │
│   - PWA 지원                    - ag-Grid 데이터 테이블          │
│   - Tailwind CSS                - Chart.js 통계                 │
│   - QR 스캔/생성                - 엑셀 처리                      │
└─────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────┐
│                        API Gateway (Nginx)                       │
│   - SSL 인증서 (Let's Encrypt)                                   │
│   - Rate Limiting                                                │
│   - Gzip 압축                                                    │
└─────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────┐
│                      애플리케이션 레이어                          │
├─────────────────────────────────────────────────────────────────┤
│                       Laravel 11 Backend                         │
│  ┌──────────────┬──────────────┬──────────────┬───────────────┐ │
│  │  인증/권한    │   문진 API   │  관리자 API  │   통계 API    │ │
│  │  Sanctum     │   Survey     │   Admin      │   Dashboard   │ │
│  └──────────────┴──────────────┴──────────────┴───────────────┘ │
│  ┌──────────────┬──────────────┬──────────────┬───────────────┐ │
│  │  암호화 서비스 │  스케줄러    │  감사 로그   │   파일 처리   │ │
│  │  AES-256     │  Auto Purge  │  Audit Log   │   Excel I/O   │ │
│  └──────────────┴──────────────┴──────────────┴───────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────┐
│                        데이터 레이어                              │
├─────────────────────────────────────────────────────────────────┤
│  [MySQL/MariaDB]           [Redis]           [File Storage]     │
│   - 메인 데이터베이스        - 세션 저장        - 중식 메뉴 이미지 │
│   - 암호화된 개인정보        - 캐시             - 엑셀 파일        │
│   - 감사 로그               - Rate Limit       - QR 이미지       │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 기술 스택 상세

| 계층 | 기술 | 버전 | 용도 |
|------|------|------|------|
| **Frontend** | Vue.js | 3.4+ | SPA 프레임워크 |
| | Vite | 5.x | 빌드 도구 |
| | Tailwind CSS | 3.4+ | UI 스타일링 |
| | Pinia | 2.x | 상태 관리 |
| | Vue Router | 4.x | 라우팅 |
| **Backend** | Laravel | 11.x | API 프레임워크 |
| | Laravel Sanctum | 4.x | SPA 인증 |
| | PHP | 8.2+ | 런타임 |
| **Database** | MySQL/MariaDB | 8.0/10.6+ | 메인 DB |
| | Redis | 7.x | 캐시/세션 |
| **Infra** | Nginx | 1.24+ | 웹서버 |
| | Supervisor | 4.x | 큐 워커 관리 |
| | Iwinv Cloud | - | 클라우드 호스팅 |

---

## 3. 데이터베이스 설계

### 3.1 ERD (Entity Relationship Diagram)

```
┌──────────────────┐       ┌──────────────────┐
│     admins       │       │    trainings     │
├──────────────────┤       ├──────────────────┤
│ id (PK)          │       │ id (PK)          │
│ name             │       │ name             │
│ email (unique)   │       │ training_date    │
│ password         │       │ status           │
│ role             │──┐    │ auto_purge_at    │
│ is_active        │  │    │ created_at       │
│ last_login_at    │  │    │ updated_at       │
│ created_at       │  │    └──────────────────┘
│ updated_at       │  │              │
└──────────────────┘  │              │ 1:N
                      │              ▼
┌──────────────────┐  │    ┌──────────────────┐
│ survey_questions │  │    │ survey_responses │
├──────────────────┤  │    ├──────────────────┤
│ id (PK)          │  │    │ id (PK)          │
│ content          │  │    │ training_id (FK) │◄─────┐
│ type             │  │    │ uuid (unique)    │      │
│ risk_level       │──┼───►│ name             │      │
│ is_active        │  │    │ dob (encrypted)  │      │
│ sort_order       │  │    │ phone (encrypted)│      │
│ created_at       │  │    │ bank_name        │      │
│ updated_at       │  │    │ account_num (enc)│      │
└──────────────────┘  │    │ lunch_yn         │      │
                      │    │ survey_result    │      │
┌──────────────────┐  │    │ answers_json     │      │
│   audit_logs     │  │    │ signature        │      │
├──────────────────┤  │    │ qr_generated_at  │      │
│ id (PK)          │  │    │ created_at       │      │
│ admin_id (FK)    │◄─┘    │ updated_at       │      │
│ action           │       └──────────────────┘      │
│ target_type      │                                 │
│ target_id        │◄────────────────────────────────┘
│ old_values       │
│ new_values       │
│ ip_address       │
│ user_agent       │
│ created_at       │
└──────────────────┘

┌──────────────────┐
│    settings      │
├──────────────────┤
│ id (PK)          │
│ key (unique)     │
│ value            │
│ created_at       │
│ updated_at       │
└──────────────────┘
```

### 3.2 테이블 상세 정의

#### 3.2.1 admins (관리자)
```sql
CREATE TABLE admins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'viewer') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_role (role)
);
```

#### 3.2.2 trainings (훈련)
```sql
CREATE TABLE trainings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    training_date DATE NOT NULL,
    status ENUM('scheduled', 'active', 'completed', 'purged') DEFAULT 'scheduled',
    -- 요일별 중식 이미지 (월~금)
    lunch_image_mon VARCHAR(500) NULL,
    lunch_image_tue VARCHAR(500) NULL,
    lunch_image_wed VARCHAR(500) NULL,
    lunch_image_thu VARCHAR(500) NULL,
    lunch_image_fri VARCHAR(500) NULL,
    -- 퇴소 모드 설정
    exit_mode ENUM('auto', 'confirm') DEFAULT 'auto',
    auto_purge_at TIMESTAMP NULL,
    purge_days INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_training_date (training_date),
    INDEX idx_status (status)
);
```

#### 3.2.3 survey_questions (문진 항목)
```sql
CREATE TABLE survey_questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    type ENUM('YES_NO', 'CHECKBOX', 'TEXT') DEFAULT 'YES_NO',
    risk_level ENUM('HIGH', 'MEDIUM', 'LOW') DEFAULT 'LOW',
    trigger_action ENUM('DANGER', 'CAUTION', 'NONE') DEFAULT 'NONE',
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_active_order (is_active, sort_order)
);
```

#### 3.2.4 survey_responses (응답 및 개인정보)
```sql
CREATE TABLE survey_responses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    training_id BIGINT UNSIGNED NOT NULL,
    uuid CHAR(36) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    dob VARBINARY(255) NOT NULL,           -- AES-256 암호화
    phone VARBINARY(255) NOT NULL,         -- AES-256 암호화
    bank_name VARCHAR(50) NOT NULL,
    account_num VARBINARY(255) NOT NULL,   -- AES-256 암호화
    lunch_yn BOOLEAN DEFAULT FALSE,
    survey_result ENUM('NORMAL', 'CAUTION', 'DANGER') NOT NULL,
    answers_json JSON NULL,
    signature MEDIUMTEXT NULL,             -- Base64 서명 이미지
    -- 입소/퇴소 상태
    attendance_status ENUM('registered', 'entered', 'exited') DEFAULT 'registered',
    entered_at TIMESTAMP NULL,             -- 입소 시간 (QR 발급 시 자동)
    exited_at TIMESTAMP NULL,              -- 퇴소 시간 (QR 스캔 시)
    exited_by BIGINT UNSIGNED NULL,        -- 퇴소 처리 관리자 (NULL이면 키오스크)
    -- 기타
    manual_override BOOLEAN DEFAULT FALSE,
    override_reason TEXT NULL,
    qr_generated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE,
    FOREIGN KEY (exited_by) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_training_date (training_id),
    INDEX idx_uuid (uuid),
    INDEX idx_result (survey_result),
    INDEX idx_attendance (attendance_status),
    UNIQUE INDEX idx_unique_entry (training_id, name, dob, phone)
);
```

#### 3.2.5 audit_logs (감사 로그)
```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id BIGINT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    target_type VARCHAR(100) NULL,
    target_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_admin (admin_id),
    INDEX idx_action (action),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created (created_at)
);
```

#### 3.2.6 settings (시스템 설정)
```sql
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_key (`key`)
);
```

---

## 4. API 설계

### 4.1 API 엔드포인트 목록

#### 4.1.1 인증 API (Auth)
| Method | Endpoint | 설명 | 인증 |
|--------|----------|------|------|
| POST | `/api/auth/login` | 관리자 로그인 | - |
| POST | `/api/auth/logout` | 로그아웃 | Required |
| GET | `/api/auth/me` | 현재 사용자 정보 | Required |
| PUT | `/api/auth/password` | 비밀번호 변경 | Required |

#### 4.1.2 문진 API (Survey) - 예비군용
| Method | Endpoint | 설명 | 인증 |
|--------|----------|------|------|
| GET | `/api/survey/check/{trainingId}` | 훈련 정보 확인 | - |
| GET | `/api/survey/questions` | 활성 문진 항목 조회 | - |
| POST | `/api/survey/submit` | 문진 제출 (자동 입소 처리) | - |
| GET | `/api/survey/result/{uuid}` | 결과 조회 | - |
| GET | `/api/survey/duplicate-check` | 중복 확인 | - |
| POST | `/api/survey/reissue` | QR 재발급 (본인확인) | - |
| GET | `/api/survey/lunch-image/{trainingId}` | 오늘 요일 중식 이미지 조회 | - |

#### 4.1.3 관리자 - 훈련 관리 API
| Method | Endpoint | 설명 | 인증 |
|--------|----------|------|------|
| GET | `/api/admin/trainings` | 훈련 목록 조회 | Required |
| POST | `/api/admin/trainings` | 훈련 생성 | Required |
| GET | `/api/admin/trainings/{id}` | 훈련 상세 조회 | Required |
| PUT | `/api/admin/trainings/{id}` | 훈련 수정 | Required |
| DELETE | `/api/admin/trainings/{id}` | 훈련 삭제 | Required |
| POST | `/api/admin/trainings/{id}/purge` | 훈련 데이터 파기 | Required (super_admin) |

#### 4.1.4 관리자 - 응답 관리 API
| Method | Endpoint | 설명 | 인증 |
|--------|----------|------|------|
| GET | `/api/admin/responses` | 응답 목록 조회 | Required |
| GET | `/api/admin/responses/{id}` | 응답 상세 조회 | Required |
| PUT | `/api/admin/responses/{id}/override` | 상태 수동 변경 | Required |
| GET | `/api/admin/responses/export` | 엑셀 다운로드 | Required |
| POST | `/api/admin/responses/import` | 엑셀 업로드 | Required |

#### 4.1.5 관리자 - 문진 항목 관리 API
| Method | Endpoint | 설명 | 인증 |
|--------|----------|------|------|
| GET | `/api/admin/questions` | 문진 항목 목록 | Required |
| POST | `/api/admin/questions` | 문진 항목 생성 | Required |
| PUT | `/api/admin/questions/{id}` | 문진 항목 수정 | Required |
| DELETE | `/api/admin/questions/{id}` | 문진 항목 삭제 | Required |
| PUT | `/api/admin/questions/reorder` | 순서 변경 | Required |

#### 4.1.6 관리자 - 대시보드 API
| Method | Endpoint | 설명 | 인증 |
|--------|----------|------|------|
| GET | `/api/admin/dashboard/stats` | 실시간 통계 | Required |
| GET | `/api/admin/dashboard/chart` | 차트 데이터 | Required |

#### 4.1.7 관리자 - 설정 API
| Method | Endpoint | 설명 | 인증 |
|--------|----------|------|------|
| GET | `/api/admin/settings` | 설정 조회 | Required |
| PUT | `/api/admin/settings` | 설정 저장 | Required |
| POST | `/api/admin/settings/qr` | 접속 QR 생성 | Required |
| POST | `/api/admin/settings/lunch-image` | 요일별 중식 이미지 업로드 | Required |

#### 4.1.8 퇴소 처리 API (키오스크/관리자)
| Method | Endpoint | 설명 | 인증 |
|--------|----------|------|------|
| POST | `/api/kiosk/exit` | QR 스캔 퇴소 처리 | Kiosk Token |
| GET | `/api/kiosk/settings` | 키오스크 설정 조회 | Kiosk Token |
| GET | `/api/admin/exit-scanner` | 관리자 퇴소 스캐너 페이지 | Required |
| POST | `/api/admin/exit/{uuid}` | 관리자 수동 퇴소 처리 | Required |

### 4.2 API 응답 형식

#### 성공 응답
```json
{
    "success": true,
    "data": { ... },
    "message": "요청이 성공적으로 처리되었습니다."
}
```

#### 에러 응답
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "입력값이 올바르지 않습니다.",
        "details": {
            "name": ["이름은 필수 입력 항목입니다."]
        }
    }
}
```

### 4.3 에러 코드 정의

| 코드 | HTTP Status | 설명 |
|------|-------------|------|
| `VALIDATION_ERROR` | 422 | 입력값 검증 실패 |
| `UNAUTHORIZED` | 401 | 인증 필요 |
| `FORBIDDEN` | 403 | 권한 없음 |
| `NOT_FOUND` | 404 | 리소스 없음 |
| `DUPLICATE_ENTRY` | 409 | 중복 등록 |
| `TRAINING_INACTIVE` | 400 | 비활성 훈련 |
| `SERVER_ERROR` | 500 | 서버 오류 |

---

## 5. 프론트엔드 설계

### 5.1 예비군 모바일 웹 화면 구성

```
/                          → 랜딩 페이지 (동의 화면)
/form                      → 개인정보 입력 폼
/survey                    → 자가 문진 (SPA 형태)
/result                    → 결과 화면
/qr                        → QR 카드 화면
/reissue                   → QR 재발급 (본인확인)
```

### 5.2 관리자 웹 화면 구성

```
/admin/login               → 로그인
/admin/dashboard           → 대시보드
/admin/trainings           → 훈련 관리
/admin/trainings/:id       → 훈련 상세
/admin/responses           → 응답 목록
/admin/responses/:id       → 응답 상세
/admin/questions           → 문진 항목 관리
/admin/settings            → 시스템 설정
/admin/audit-logs          → 감사 로그 조회
/admin/exit-scanner        → 퇴소 QR 스캐너
```

### 5.3 키오스크 모드 (퇴소 전용 태블릿)

```
/kiosk                     → 키오스크 메인 (QR 스캐너)
/kiosk/setup               → 키오스크 초기 설정
```

### 5.4 컴포넌트 구조

```
src/
├── assets/
│   ├── css/
│   └── images/
├── components/
│   ├── common/
│   │   ├── BaseButton.vue
│   │   ├── BaseInput.vue
│   │   ├── BaseSelect.vue
│   │   ├── BaseModal.vue
│   │   ├── LoadingSpinner.vue
│   │   └── ProgressBar.vue
│   ├── survey/
│   │   ├── QuestionCard.vue
│   │   ├── AnswerButtons.vue
│   │   ├── SignaturePad.vue
│   │   ├── QRCard.vue
│   │   └── ReissueForm.vue
│   ├── kiosk/
│   │   ├── QRScanner.vue
│   │   ├── ExitConfirmModal.vue
│   │   ├── SuccessAnimation.vue
│   │   └── TTSPlayer.vue
│   └── admin/
│       ├── DataGrid.vue
│       ├── StatsCard.vue
│       ├── ChartWidget.vue
│       ├── QuestionBuilder.vue
│       ├── LunchImageUploader.vue
│       └── ExitScanner.vue
├── layouts/
│   ├── DefaultLayout.vue
│   └── AdminLayout.vue
├── pages/
│   ├── public/
│   │   ├── LandingPage.vue
│   │   ├── FormPage.vue
│   │   ├── SurveyPage.vue
│   │   ├── ResultPage.vue
│   │   ├── QRPage.vue
│   │   └── ReissuePage.vue
│   ├── kiosk/
│   │   ├── KioskMainPage.vue
│   │   └── KioskSetupPage.vue
│   └── admin/
│       ├── LoginPage.vue
│       ├── DashboardPage.vue
│       ├── TrainingsPage.vue
│       ├── ResponsesPage.vue
│       ├── QuestionsPage.vue
│       ├── SettingsPage.vue
│       └── ExitScannerPage.vue
├── stores/
│   ├── auth.js
│   ├── survey.js
│   └── admin.js
├── services/
│   ├── api.js
│   └── storage.js
├── composables/
│   ├── useQR.js
│   ├── useValidation.js
│   └── useEncryption.js
├── router/
│   └── index.js
├── App.vue
└── main.js
```

---

## 6. 보안 설계

### 6.1 데이터 암호화

#### AES-256 암호화 대상 필드
- `survey_responses.dob` (생년월일)
- `survey_responses.phone` (연락처)
- `survey_responses.account_num` (계좌번호)

#### Laravel 암호화 구현
```php
// config/app.php에서 APP_KEY 사용
// Encryptable Trait 구현

class SurveyResponse extends Model
{
    protected $encryptable = ['dob', 'phone', 'account_num'];

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encryptable) && !empty($value)) {
            $value = encrypt($value);
        }
        return parent::setAttribute($key, $value);
    }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        if (in_array($key, $this->encryptable) && !empty($value)) {
            return decrypt($value);
        }
        return $value;
    }
}
```

### 6.2 감사 로그

#### 로깅 대상 액션
| 액션 | 설명 |
|------|------|
| `LOGIN` | 관리자 로그인 |
| `LOGOUT` | 관리자 로그아웃 |
| `VIEW_RESPONSE` | 응답 데이터 조회 |
| `EXPORT_EXCEL` | 엑셀 다운로드 |
| `OVERRIDE_STATUS` | 상태 수동 변경 |
| `PURGE_DATA` | 데이터 파기 |
| `CREATE_TRAINING` | 훈련 생성 |
| `UPDATE_QUESTION` | 문진 항목 수정 |

#### 감사 로그 미들웨어
```php
class AuditLogMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (auth()->check()) {
            AuditLog::create([
                'admin_id' => auth()->id(),
                'action' => $this->getAction($request),
                'target_type' => $this->getTargetType($request),
                'target_id' => $this->getTargetId($request),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $response;
    }
}
```

### 6.3 접근 통제

#### IP 화이트리스트 (선택적)
```php
// .env
ADMIN_ALLOWED_IPS=192.168.1.0/24,10.0.0.0/8

// Middleware
class IpWhitelistMiddleware
{
    public function handle($request, Closure $next)
    {
        $allowedIps = explode(',', config('app.admin_allowed_ips'));

        if (!$this->isIpAllowed($request->ip(), $allowedIps)) {
            abort(403, 'Access denied from this IP address.');
        }

        return $next($request);
    }
}
```

### 6.4 데이터 자동 파기

#### 스케줄러 설정
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // 매일 자정에 만료된 훈련 데이터 파기
    $schedule->command('training:purge-expired')
             ->dailyAt('00:00')
             ->withoutOverlapping();
}

// PurgeExpiredTrainingsCommand
public function handle()
{
    $expiredTrainings = Training::where('status', 'completed')
        ->where('auto_purge_at', '<=', now())
        ->get();

    foreach ($expiredTrainings as $training) {
        // Hard Delete
        $training->responses()->forceDelete();
        $training->update(['status' => 'purged']);

        Log::info("Training {$training->id} data purged");
    }
}
```

---

## 7. 개발 단계별 계획

### Phase 1: 프로젝트 초기 설정 (1단계)

#### 작업 내용
1. **Laravel 프로젝트 생성**
   - Laravel 11 설치
   - 필요 패키지 설치 (Sanctum, Excel, QR 등)
   - 환경 설정 (.env)

2. **Vue.js 프로젝트 설정**
   - Vite + Vue 3 프로젝트 생성
   - Tailwind CSS 설정
   - Vue Router, Pinia 설치

3. **데이터베이스 마이그레이션**
   - 모든 테이블 마이그레이션 파일 생성
   - 시더 파일 생성 (기본 문진 항목, 테스트 계정)

4. **기본 구조 설정**
   - API 라우트 구조
   - 미들웨어 설정
   - 에러 핸들링

#### 산출물
- 프로젝트 기본 구조
- 데이터베이스 스키마
- 개발 환경 구성

---

### Phase 2: Backend API 개발 (2단계)

#### 2-1. 인증 시스템
- Sanctum SPA 인증 설정
- 로그인/로그아웃 API
- 미들웨어 설정

#### 2-2. 문진 API (예비군용)
- 문진 항목 조회 API
- 문진 제출 API (트랜잭션 처리)
- 결과 조회 API
- 중복 확인 API

#### 2-3. 관리자 API
- 훈련 CRUD API
- 응답 관리 API
- 문진 항목 관리 API
- 대시보드 통계 API
- 엑셀 Import/Export API

#### 2-4. 보안 구현
- 암호화 Trait
- 감사 로그 미들웨어
- 데이터 파기 스케줄러

#### 산출물
- 완전한 REST API
- 암호화 시스템
- 감사 로그 시스템

---

### Phase 3: Frontend 개발 (3단계)

#### 3-1. 공통 컴포넌트
- BaseButton, BaseInput, BaseSelect
- LoadingSpinner, ProgressBar
- Modal, Toast 알림

#### 3-2. 예비군 모바일 웹
- 랜딩 페이지 (동의 화면)
- 개인정보 입력 폼
- 자가 문진 화면 (Progress Bar)
- 결과 화면
- QR 카드 생성 및 저장

#### 3-3. 관리자 웹
- 로그인 페이지
- 대시보드 (통계, 차트)
- 훈련 관리
- 응답 목록/상세 (ag-Grid)
- 문진 항목 관리 (드래그 정렬)
- 설정 페이지

#### 3-4. PWA 설정
- Service Worker
- Manifest
- 오프라인 대응

#### 산출물
- 완전한 SPA 애플리케이션
- PWA 지원
- 반응형 디자인

---

### Phase 4: 통합 및 테스트 (4단계)

#### 4-1. 통합 테스트
- API 연동 테스트
- 전체 플로우 테스트
- 크로스 브라우저 테스트

#### 4-2. 보안 테스트
- 암호화 검증
- 인증/권한 테스트
- SQL Injection, XSS 테스트

#### 4-3. 성능 테스트
- 로딩 속도 최적화
- API 응답 시간 측정
- 동시 접속 테스트

#### 산출물
- 테스트 보고서
- 버그 수정
- 성능 최적화

---

### Phase 5: 배포 및 운영 (5단계)

#### 5-1. 서버 구성 (Iwinv)
- 서버 프로비저닝
- Nginx 설정
- SSL 인증서 (Let's Encrypt)
- PHP/MySQL/Redis 설치

#### 5-2. 배포 파이프라인
- Git 기반 배포 스크립트
- 환경별 설정 (.env.production)
- 마이그레이션 자동화

#### 5-3. 모니터링
- 에러 로깅 (Laravel Log)
- 서버 모니터링
- 백업 정책

#### 산출물
- 프로덕션 환경
- 배포 문서
- 운영 가이드

---

## 8. 추가 기능 구현 사항

### 8.1 디지털 서명 (Canvas Pad)
```javascript
// SignaturePad 컴포넌트
const canvas = ref(null);
const isDrawing = ref(false);

const startDrawing = (e) => {
    isDrawing.value = true;
    ctx.beginPath();
    ctx.moveTo(e.offsetX, e.offsetY);
};

const draw = (e) => {
    if (!isDrawing.value) return;
    ctx.lineTo(e.offsetX, e.offsetY);
    ctx.stroke();
};

const getSignatureData = () => {
    return canvas.value.toDataURL('image/png');
};
```

### 8.2 QR 코드 생성
```javascript
// QR 페이로드 (Server-less Reader 호환)
const qrPayload = {
    name: "홍길동",
    dob: "900101",
    lunch: "Y",
    status: "NORMAL",
    id: "uuid-xxxx-xxxx"
};

// qrcode.vue 사용
<qrcode-vue
    :value="JSON.stringify(qrPayload)"
    :size="200"
    level="M"
/>
```

### 8.3 이미지 저장 (html2canvas)
```javascript
import html2canvas from 'html2canvas';

const saveAsImage = async () => {
    const element = document.getElementById('qr-card');
    const canvas = await html2canvas(element);

    const link = document.createElement('a');
    link.download = '입소QR카드.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
};
```

### 8.4 중복 제출 방지
```php
// SurveyController
public function submit(SurveyRequest $request)
{
    $existing = SurveyResponse::where('training_id', $request->training_id)
        ->where('name', $request->name)
        ->where('dob', encrypt($request->dob))
        ->where('phone', encrypt($request->phone))
        ->first();

    if ($existing) {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'DUPLICATE_ENTRY',
                'message' => '이미 등록된 사용자입니다.',
                'uuid' => $existing->uuid
            ]
        ], 409);
    }

    // 신규 등록 처리
}
```

### 8.5 퇴소 처리 시스템 (키오스크 모드)

#### 8.5.1 키오스크 아키텍처
```
┌─────────────────────────────────────────────────────────────┐
│                    키오스크 태블릿                           │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────────────────────────────────┐   │
│  │              실시간 QR 스캐너 (카메라)                │   │
│  │                                                      │   │
│  │    [예비군이 QR을 가져다 대면 자동 인식]              │   │
│  │                                                      │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              처리 결과 표시 영역                      │   │
│  │                                                      │   │
│  │    ✅ "홍길동님, 퇴소 처리되었습니다"                 │   │
│  │    🔊 TTS 음성 안내                                  │   │
│  │                                                      │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  [자동퇴소 모드] ←→ [확인퇴소 모드] (토글 스위치)           │
└─────────────────────────────────────────────────────────────┘
```

#### 8.5.2 두 가지 퇴소 모드

**모드 1: 자동퇴소 (Auto Mode)**
```javascript
// KioskMainPage.vue
const onQRScanned = async (qrData) => {
    try {
        const response = await api.post('/kiosk/exit', { uuid: qrData.id });

        if (response.success) {
            // 성공 애니메이션 표시
            showSuccessAnimation(response.data.name);

            // TTS 음성 재생
            speak(`${response.data.name}님, 퇴소 처리되었습니다.`);

            // 2초 후 자동으로 다음 스캔 대기
            setTimeout(() => {
                resetScanner();
            }, 2000);
        }
    } catch (error) {
        showErrorAnimation(error.message);
        speak('처리 중 오류가 발생했습니다.');
    }
};
```

**모드 2: 확인퇴소 (Confirm Mode)**
```javascript
// 확인 모달 표시 후 버튼 클릭 시 처리
const onQRScanned = async (qrData) => {
    // 확인 모달 표시
    showConfirmModal({
        name: qrData.name,
        message: '퇴소 처리하시겠습니까?',
        onConfirm: async () => {
            const response = await api.post('/kiosk/exit', { uuid: qrData.id });
            showSuccessAnimation(response.data.name);
            speak(`${response.data.name}님, 퇴소 처리되었습니다.`);
        },
        onCancel: () => {
            resetScanner();
        }
    });
};
```

#### 8.5.3 TTS 음성 안내 구현
```javascript
// composables/useTTS.js
export function useTTS() {
    const speak = (text) => {
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'ko-KR';
        utterance.rate = 1.0;
        utterance.pitch = 1.0;
        speechSynthesis.speak(utterance);
    };

    return { speak };
}
```

#### 8.5.4 연속 스캔 최적화
```javascript
// 스캔 간격 최소화를 위한 설정
const scannerConfig = {
    fps: 30,                    // 높은 프레임 레이트
    qrbox: { width: 300, height: 300 },
    aspectRatio: 1.0,
    disableFlip: false,
    // 중복 스캔 방지 (같은 QR 연속 스캔 차단)
    rememberLastUsedCamera: true,
};

// 마지막 스캔된 QR 중복 방지
const lastScannedUuid = ref(null);
const SCAN_COOLDOWN = 3000; // 3초 쿨다운

const handleScan = (decodedText) => {
    const data = JSON.parse(decodedText);

    if (data.id === lastScannedUuid.value) {
        return; // 동일 QR 연속 스캔 무시
    }

    lastScannedUuid.value = data.id;
    onQRScanned(data);

    // 쿨다운 후 초기화
    setTimeout(() => {
        lastScannedUuid.value = null;
    }, SCAN_COOLDOWN);
};
```

### 8.6 QR 재발급 기능

#### 8.6.1 본인 확인 프로세스
```
┌──────────────────────────────────────┐
│         QR 재발급 신청               │
├──────────────────────────────────────┤
│                                      │
│  이름:      [홍길동        ]         │
│                                      │
│  생년월일:  [900101        ]         │
│                                      │
│  연락처:    [010-1234-5678 ]         │
│                                      │
│        [QR 재발급 받기]              │
│                                      │
└──────────────────────────────────────┘
```

#### 8.6.2 재발급 API 구현
```php
// SurveyController.php
public function reissue(ReissueRequest $request)
{
    // 오늘 날짜의 활성 훈련 조회
    $training = Training::where('training_date', today())
        ->where('status', 'active')
        ->first();

    if (!$training) {
        return response()->json([
            'success' => false,
            'error' => ['code' => 'NO_ACTIVE_TRAINING', 'message' => '오늘 진행중인 훈련이 없습니다.']
        ], 400);
    }

    // 본인 확인 (이름 + 생년월일 + 연락처)
    $response = SurveyResponse::where('training_id', $training->id)
        ->where('name', $request->name)
        ->where('dob', encrypt($request->dob))
        ->where('phone', encrypt($request->phone))
        ->first();

    if (!$response) {
        return response()->json([
            'success' => false,
            'error' => ['code' => 'NOT_FOUND', 'message' => '등록된 정보를 찾을 수 없습니다.']
        ], 404);
    }

    // 기존 QR 데이터 반환
    return response()->json([
        'success' => true,
        'data' => [
            'uuid' => $response->uuid,
            'name' => $response->name,
            'dob' => decrypt($response->dob),
            'lunch_yn' => $response->lunch_yn,
            'survey_result' => $response->survey_result,
            'attendance_status' => $response->attendance_status,
        ]
    ]);
}
```

#### 8.6.3 재발급 페이지 컴포넌트
```vue
<!-- ReissuePage.vue -->
<template>
  <div class="reissue-container">
    <h1>QR 코드 재발급</h1>
    <p>등록하신 정보를 입력해주세요.</p>

    <form @submit.prevent="handleReissue">
      <BaseInput v-model="form.name" label="이름" required />
      <BaseInput v-model="form.dob" label="생년월일 (6자리)" maxlength="6" required />
      <BaseInput v-model="form.phone" label="연락처" type="tel" required />

      <BaseButton type="submit" :loading="loading">
        QR 재발급 받기
      </BaseButton>
    </form>

    <!-- 재발급 성공 시 QR 표시 -->
    <QRCard v-if="qrData" :data="qrData" />
  </div>
</template>
```

### 8.7 요일별 중식 이미지 관리

#### 8.7.1 요일별 이미지 업로드 UI
```vue
<!-- LunchImageUploader.vue -->
<template>
  <div class="lunch-image-manager">
    <h3>요일별 중식 메뉴 이미지</h3>

    <div class="weekday-grid">
      <div v-for="day in weekdays" :key="day.value" class="day-card">
        <h4>{{ day.label }}</h4>

        <div class="image-preview" v-if="images[day.value]">
          <img :src="images[day.value]" :alt="day.label + ' 메뉴'" />
          <button @click="removeImage(day.value)">삭제</button>
        </div>

        <input
          type="file"
          accept="image/*"
          @change="(e) => uploadImage(day.value, e)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
const weekdays = [
  { value: 'mon', label: '월요일' },
  { value: 'tue', label: '화요일' },
  { value: 'wed', label: '수요일' },
  { value: 'thu', label: '목요일' },
  { value: 'fri', label: '금요일' },
];

const images = ref({
  mon: null,
  tue: null,
  wed: null,
  thu: null,
  fri: null,
});

const uploadImage = async (day, event) => {
  const file = event.target.files[0];
  const formData = new FormData();
  formData.append('image', file);
  formData.append('day', day);

  const response = await api.post('/admin/settings/lunch-image', formData);
  images.value[day] = response.data.url;
};
</script>
```

#### 8.7.2 오늘 요일 중식 이미지 조회
```php
// SurveyController.php
public function getLunchImage($trainingId)
{
    $training = Training::findOrFail($trainingId);

    // 오늘 요일에 맞는 이미지 반환
    $dayOfWeek = strtolower(now()->format('D')); // mon, tue, wed, thu, fri

    $imageField = "lunch_image_{$dayOfWeek}";
    $imageUrl = $training->$imageField;

    return response()->json([
        'success' => true,
        'data' => [
            'day' => $dayOfWeek,
            'day_korean' => $this->getDayKorean($dayOfWeek),
            'image_url' => $imageUrl ? Storage::url($imageUrl) : null,
        ]
    ]);
}

private function getDayKorean($day)
{
    return match($day) {
        'mon' => '월요일',
        'tue' => '화요일',
        'wed' => '수요일',
        'thu' => '목요일',
        'fri' => '금요일',
        default => '주말',
    };
}
```

#### 8.7.3 예비군 화면에서 오늘의 중식 표시
```vue
<!-- FormPage.vue 내 중식 신청 섹션 -->
<template>
  <div class="lunch-section">
    <h3>오늘의 점심 메뉴 ({{ lunchInfo.day_korean }})</h3>

    <div class="menu-card" v-if="lunchInfo.image_url">
      <img :src="lunchInfo.image_url" alt="오늘의 메뉴" />
    </div>
    <div class="no-menu" v-else>
      <p>메뉴 이미지가 등록되지 않았습니다.</p>
    </div>

    <div class="lunch-options">
      <label>
        <input type="radio" v-model="form.lunch_yn" :value="true" />
        신청 (예)
      </label>
      <label>
        <input type="radio" v-model="form.lunch_yn" :value="false" />
        미신청 (아니오)
      </label>
    </div>
  </div>
</template>
```

---

## 9. 디렉토리 구조

### Backend (Laravel)
```
backend/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── PurgeExpiredTrainings.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── SurveyController.php
│   │   │   │   └── Admin/
│   │   │   │       ├── TrainingController.php
│   │   │   │       ├── ResponseController.php
│   │   │   │       ├── QuestionController.php
│   │   │   │       ├── DashboardController.php
│   │   │   │       └── SettingsController.php
│   │   ├── Middleware/
│   │   │   ├── AuditLog.php
│   │   │   └── IpWhitelist.php
│   │   └── Requests/
│   │       ├── SurveyRequest.php
│   │       └── Admin/
│   │           └── ...
│   ├── Models/
│   │   ├── Admin.php
│   │   ├── Training.php
│   │   ├── SurveyQuestion.php
│   │   ├── SurveyResponse.php
│   │   ├── AuditLog.php
│   │   └── Setting.php
│   ├── Services/
│   │   ├── EncryptionService.php
│   │   ├── SurveyService.php
│   │   └── ExcelService.php
│   └── Traits/
│       └── Encryptable.php
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
└── storage/
```

### Frontend (Vue.js)
```
frontend/
├── public/
│   ├── manifest.json
│   └── sw.js
├── src/
│   ├── assets/
│   ├── components/
│   ├── composables/
│   ├── layouts/
│   ├── pages/
│   ├── router/
│   ├── services/
│   ├── stores/
│   ├── App.vue
│   └── main.js
├── index.html
├── package.json
├── tailwind.config.js
└── vite.config.js
```

---

## 10. 필수 패키지

### Backend (Laravel)
```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "laravel/sanctum": "^4.0",
        "maatwebsite/excel": "^3.1",
        "simplesoftwareio/simple-qrcode": "^4.2"
    },
    "require-dev": {
        "pestphp/pest": "^2.0",
        "laravel/pint": "^1.0"
    }
}
```

### Frontend (Vue.js)
```json
{
    "dependencies": {
        "vue": "^3.4",
        "vue-router": "^4.3",
        "pinia": "^2.1",
        "axios": "^1.6",
        "qrcode.vue": "^3.4",
        "html5-qrcode": "^2.3",
        "html2canvas": "^1.4",
        "signature_pad": "^4.1",
        "ag-grid-vue3": "^31.0",
        "chart.js": "^4.4",
        "vue-chartjs": "^5.3"
    },
    "devDependencies": {
        "vite": "^5.0",
        "@vitejs/plugin-vue": "^5.0",
        "tailwindcss": "^3.4",
        "autoprefixer": "^10.4",
        "postcss": "^8.4"
    }
}
```

---

## 11. 환경 설정

### .env.example
```env
APP_NAME="ROKAPASS"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://rokapass.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rokapass
DB_USERNAME=rokapass_user
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

SESSION_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

SANCTUM_STATEFUL_DOMAINS=rokapass.example.com

# 암호화 키 (자동 생성)
# php artisan key:generate

# 데이터 파기 설정
AUTO_PURGE_DAYS=3

# IP 화이트리스트 (선택적)
ADMIN_ALLOWED_IPS=

# 엑셀 비밀번호 (선택적)
EXCEL_PASSWORD=
```

---

## 12. 참고 문서

- [Laravel 11 공식 문서](https://laravel.com/docs/11.x)
- [Vue.js 3 공식 문서](https://vuejs.org/guide/introduction.html)
- [Laravel Sanctum 공식 문서](https://laravel.com/docs/11.x/sanctum)
- [Tailwind CSS 공식 문서](https://tailwindcss.com/docs)
- [ag-Grid Vue 문서](https://www.ag-grid.com/vue-data-grid/)

---

**문서 끝**
