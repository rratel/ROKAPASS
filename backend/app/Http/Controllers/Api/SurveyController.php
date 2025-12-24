<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\SurveyResponse;
use App\Models\Training;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SurveyController extends Controller
{
    public function submit(Request $request)
    {
        $request->validate([
            'training_id' => 'required|exists:trainings,id',
            'name' => 'required|string|max:100',
            'dob' => 'required|string|size:6',
            'phone' => 'required|string|min:10|max:11',
            'bank_name' => 'required|string|max:50',
            'account_num' => 'required|string',
            'lunch_yn' => 'boolean',
            'answers' => 'required|array',
        ]);

        $training = Training::find($request->training_id);

        if (!$training || !in_array($training->status, ['scheduled', 'active'])) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '현재 진행 중인 훈련이 아닙니다.'],
            ], 400);
        }

        // 중복 체크 (같은 훈련에서 동일 전화번호)
        $existing = SurveyResponse::where('training_id', $request->training_id)
            ->get()
            ->first(function ($response) use ($request) {
                return $response->phone === $request->phone;
            });

        if ($existing) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '이미 등록된 전화번호입니다. 동일한 번호로 중복 등록할 수 없습니다.'],
            ], 400);
        }

        // 문진 결과 계산
        $questions = Question::active()->ordered()->get();
        $surveyResult = $this->calculateResult($request->answers, $questions);

        // 질문-답변 스냅샷 생성 (나중에 질문이 바뀌어도 당시 내용 보존)
        $answersSnapshot = $this->createAnswersSnapshot($request->answers, $questions);

        // 저장
        $uuid = (string) Str::uuid();
        $response = SurveyResponse::create([
            'training_id' => $request->training_id,
            'uuid' => $uuid,
            'name' => $request->name,
            'dob' => $request->dob,
            'phone' => $request->phone,
            'bank_name' => $request->bank_name,
            'account_num' => $request->account_num,
            'lunch_yn' => $request->lunch_yn ?? false,
            'survey_result' => $surveyResult,
            'answers_json' => $answersSnapshot,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $uuid,
                'survey_result' => $surveyResult,
                'name' => $response->name,
                'lunch_yn' => $response->lunch_yn,
            ],
        ]);
    }

    public function signature(Request $request)
    {
        $request->validate([
            'uuid' => 'required|string',
            'signature' => 'required|string',
        ]);

        $response = SurveyResponse::where('uuid', $request->uuid)->first();

        if (!$response) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '응답을 찾을 수 없습니다.'],
            ], 404);
        }

        // QR 발급 시 자동 입소 처리 설정 확인
        $autoEntry = Setting::get('auto_entry_on_qr', true);

        $response->update([
            'signature' => $request->signature,
            'qr_generated_at' => now(),
            'attendance_status' => $autoEntry ? 'entered' : 'registered',
            'entered_at' => $autoEntry ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $response->uuid,
            ],
        ]);
    }

    public function reissue(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'dob' => 'required|string|size:6',
            'phone' => 'required|string|min:10|max:11',
        ]);

        // 오늘 날짜의 활성 훈련에서 찾기
        $response = SurveyResponse::whereHas('training', function ($query) {
                $query->whereIn('status', ['scheduled', 'active']);
            })
            ->where('name', $request->name)
            ->get()
            ->first(function ($r) use ($request) {
                return $r->dob === $request->dob && $r->phone === $request->phone;
            });

        if (!$response) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '등록된 정보를 찾을 수 없습니다.'],
            ], 404);
        }

        // DANGER 결과인 경우 QR 재발급 차단
        if ($response->survey_result === 'DANGER') {
            return response()->json([
                'success' => false,
                'error' => ['message' => '문진 결과가 위험으로 판정되어 QR 재발급이 불가합니다. 군의관 면담 후 관리자에게 문의하세요.'],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $response->uuid,
                'name' => $response->name,
                'survey_result' => $response->survey_result,
                'lunch_yn' => $response->lunch_yn,
            ],
        ]);
    }

    private function calculateResult(array $answers, $questions): string
    {
        $dangerCount = 0;

        foreach ($questions as $question) {
            $answer = $answers[$question->id] ?? null;
            if (!$answer) continue;

            if ($question->hasDangerOption($answer)) {
                $dangerCount++;
            }
        }

        if ($dangerCount >= 2) {
            return 'DANGER';
        } elseif ($dangerCount === 1) {
            return 'CAUTION';
        }

        return 'NORMAL';
    }

    /**
     * 질문-답변 스냅샷 생성
     * 나중에 질문이 수정/삭제되어도 당시 응답 내용을 그대로 볼 수 있도록 저장
     */
    private function createAnswersSnapshot(array $answers, $questions): array
    {
        $snapshot = [];

        foreach ($questions as $question) {
            $answerValue = $answers[$question->id] ?? null;
            if ($answerValue === null) continue;

            // 선택한 옵션 찾기
            $options = $question->options ?? [];
            $selectedOption = collect($options)->firstWhere('value', $answerValue);

            $snapshot[] = [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'answer_value' => $answerValue,
                'answer_label' => $selectedOption['label'] ?? $answerValue,
                'is_danger' => $selectedOption['is_danger'] ?? false,
            ];
        }

        return $snapshot;
    }
}
