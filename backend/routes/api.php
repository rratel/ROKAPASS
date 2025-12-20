<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TrainingController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\KioskController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TrainingController as AdminTrainingController;
use App\Http\Controllers\Admin\ResponseController;
use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ExitScannerController;

/*
|--------------------------------------------------------------------------
| Public API Routes (예비군용)
|--------------------------------------------------------------------------
*/

// 훈련 정보
Route::get('/trainings/active', [TrainingController::class, 'active']);
Route::get('/trainings/code/{code}', [TrainingController::class, 'showByCode']);
Route::get('/trainings/{id}', [TrainingController::class, 'show']);

// 문진표 문항
Route::get('/questions/active', [QuestionController::class, 'active']);

// 설문 제출
Route::post('/survey/submit', [SurveyController::class, 'submit']);
Route::post('/survey/signature', [SurveyController::class, 'signature']);
Route::post('/survey/reissue', [SurveyController::class, 'reissue']);

// 키오스크
Route::post('/kiosk/scan', [KioskController::class, 'scan']);
Route::post('/kiosk/confirm-exit', [KioskController::class, 'confirmExit']);

/*
|--------------------------------------------------------------------------
| Auth Routes (관리자 인증)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin API Routes (관리자용 - 인증 필요)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    // 대시보드
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // 훈련 관리
    Route::get('/trainings', [AdminTrainingController::class, 'index']);
    Route::post('/trainings', [AdminTrainingController::class, 'store']);
    Route::get('/trainings/{id}', [AdminTrainingController::class, 'show']);
    Route::put('/trainings/{id}', [AdminTrainingController::class, 'update']);
    Route::delete('/trainings/{id}', [AdminTrainingController::class, 'destroy']);
    Route::post('/trainings/{id}/activate', [AdminTrainingController::class, 'activate']);
    Route::post('/trainings/{id}/pause', [AdminTrainingController::class, 'pause']);
    Route::post('/trainings/{id}/complete', [AdminTrainingController::class, 'complete']);

    // 응답 관리
    Route::get('/responses', [ResponseController::class, 'index']);
    Route::get('/responses/export', [ResponseController::class, 'export']);

    // 문진표 관리
    Route::get('/questions', [AdminQuestionController::class, 'index']);
    Route::post('/questions', [AdminQuestionController::class, 'store']);
    Route::put('/questions/{id}', [AdminQuestionController::class, 'update']);
    Route::delete('/questions/{id}', [AdminQuestionController::class, 'destroy']);
    Route::patch('/questions/{id}/toggle', [AdminQuestionController::class, 'toggle']);
    Route::post('/questions/reorder', [AdminQuestionController::class, 'reorder']);

    // 관리자 계정 관리
    Route::get('/admins', [AdminController::class, 'index']);
    Route::post('/admins', [AdminController::class, 'store']);
    Route::put('/admins/{id}', [AdminController::class, 'update']);
    Route::delete('/admins/{id}', [AdminController::class, 'destroy']);

    // 설정
    Route::get('/settings', [SettingController::class, 'index']);
    Route::put('/settings', [SettingController::class, 'update']);

    // 비밀번호 변경
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // 퇴소 스캐너
    Route::post('/exit-scan', [ExitScannerController::class, 'scan']);
    Route::post('/confirm-exit', [ExitScannerController::class, 'confirmExit']);
});
