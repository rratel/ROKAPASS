<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponse;
use App\Models\AuditLog;
use App\Models\Question;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ResponsesExport;

class ResponseController extends Controller
{
    /**
     * 개별 응답 상세 조회
     */
    public function show($id)
    {
        $response = SurveyResponse::with('training')->find($id);

        if (!$response) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '응답을 찾을 수 없습니다.'],
            ], 404);
        }

        // answers_json 형식 처리 (기존 데이터 호환)
        $answers = $response->answers_json;
        $formattedAnswers = [];

        if (is_array($answers) && !empty($answers)) {
            // 새 형식: 배열의 첫 요소가 question_text를 가지고 있는지 확인
            $firstItem = reset($answers);
            $isNewFormat = is_array($firstItem) && isset($firstItem['question_text']);

            if ($isNewFormat) {
                // 새 형식 - 그대로 사용
                $formattedAnswers = $answers;
            } else {
                // 기존 형식 - 현재 질문과 매칭 시도
                $questions = \App\Models\Question::whereIn('id', array_keys($answers))->get()->keyBy('id');

                foreach ($answers as $questionId => $answerValue) {
                    $question = $questions->get($questionId);

                    if ($question) {
                        $options = $question->options ?? [];
                        $selectedOption = collect($options)->firstWhere('value', $answerValue);

                        $formattedAnswers[] = [
                            'question_id' => (int) $questionId,
                            'question_text' => $question->question_text,
                            'answer_value' => $answerValue,
                            'answer_label' => $selectedOption['label'] ?? $answerValue,
                            'is_danger' => $selectedOption['is_danger'] ?? false,
                        ];
                    } else {
                        // 삭제된 질문
                        $formattedAnswers[] = [
                            'question_id' => (int) $questionId,
                            'question_text' => '(삭제된 질문)',
                            'answer_value' => $answerValue,
                            'answer_label' => $answerValue,
                            'is_danger' => false,
                        ];
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $response->id,
                'name' => $response->name,
                'dob' => $response->dob,
                'phone' => $response->phone,
                'bank_name' => $response->bank_name,
                'account_num' => $response->account_num,
                'lunch_yn' => $response->lunch_yn,
                'survey_result' => $response->survey_result,
                'attendance_status' => $response->attendance_status,
                'training' => $response->training,
                'answers' => $formattedAnswers,
                'entered_at' => $response->entered_at,
                'exited_at' => $response->exited_at,
                'created_at' => $response->created_at,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $query = SurveyResponse::with('training');

        if ($request->has('training_id') && $request->training_id) {
            $query->where('training_id', $request->training_id);
        }

        if ($request->has('survey_result') && $request->survey_result) {
            $query->where('survey_result', $request->survey_result);
        }

        if ($request->has('attendance_status') && $request->attendance_status) {
            $query->where('attendance_status', $request->attendance_status);
        }

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('lunch_yn')) {
            $query->where('lunch_yn', $request->lunch_yn === 'true' || $request->lunch_yn === '1');
        }

        $perPage = $request->get('per_page', 20);
        $limit = $request->get('limit');

        if ($limit) {
            $responses = $query->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $responses,
            ]);
        }

        $responses = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $responses->items(),
                'meta' => [
                    'current_page' => $responses->currentPage(),
                    'last_page' => $responses->lastPage(),
                    'per_page' => $responses->perPage(),
                    'total' => $responses->total(),
                ],
            ],
        ]);
    }

    public function export(Request $request)
    {
        $query = SurveyResponse::with('training');

        if ($request->has('training_id') && $request->training_id) {
            $query->where('training_id', $request->training_id);
        }

        if ($request->has('survey_result') && $request->survey_result) {
            $query->where('survey_result', $request->survey_result);
        }

        if ($request->has('attendance_status') && $request->attendance_status) {
            $query->where('attendance_status', $request->attendance_status);
        }

        if ($request->filled('lunch_yn')) {
            $query->where('lunch_yn', $request->lunch_yn === 'true' || $request->lunch_yn === '1');
        }

        $responses = $query->orderBy('created_at', 'desc')->get();

        return Excel::download(new ResponsesExport($responses), 'responses.xlsx');
    }

    /**
     * 응답 수정
     */
    public function update(Request $request, $id)
    {
        $response = SurveyResponse::find($id);

        if (!$response) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '응답을 찾을 수 없습니다.'],
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'dob' => 'sometimes|string|size:6',
            'phone' => 'sometimes|string|min:10|max:11',
            'bank_name' => 'sometimes|string|max:50',
            'account_num' => 'sometimes|string',
            'lunch_yn' => 'sometimes|boolean',
        ]);

        $oldData = $response->only(['name', 'dob', 'phone', 'bank_name', 'account_num', 'lunch_yn']);

        $response->update($request->only([
            'name', 'dob', 'phone', 'bank_name', 'account_num', 'lunch_yn'
        ]));

        AuditLog::log('update', 'survey_response', $response->id, $oldData, $response->only(['name', 'dob', 'phone', 'bank_name', 'account_num', 'lunch_yn']));

        return response()->json([
            'success' => true,
            'data' => $response,
            'message' => '응답이 수정되었습니다.',
        ]);
    }

    /**
     * 응답 삭제
     */
    public function destroy($id)
    {
        $response = SurveyResponse::find($id);

        if (!$response) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '응답을 찾을 수 없습니다.'],
            ], 404);
        }

        $responseData = $response->toArray();

        AuditLog::log('delete', 'survey_response', $response->id, $responseData, null);

        $response->delete();

        return response()->json([
            'success' => true,
            'message' => '응답이 삭제되었습니다.',
        ]);
    }

    /**
     * 문진 결과만 직접 변경 (군의관 면담 후)
     */
    public function updateResult(Request $request, $id)
    {
        $response = SurveyResponse::find($id);

        if (!$response) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '응답을 찾을 수 없습니다.'],
            ], 404);
        }

        $request->validate([
            'survey_result' => 'required|in:NORMAL,CAUTION,DANGER',
            'override_reason' => 'nullable|string|max:500',
        ]);

        $oldData = [
            'survey_result' => $response->survey_result,
            'manual_override' => $response->manual_override,
            'override_reason' => $response->override_reason,
        ];

        $response->update([
            'survey_result' => $request->survey_result,
            'manual_override' => true,
            'override_reason' => $request->override_reason,
        ]);

        AuditLog::log('override_result', 'survey_response', $response->id, $oldData, [
            'survey_result' => $response->survey_result,
            'manual_override' => true,
            'override_reason' => $response->override_reason,
        ]);

        return response()->json([
            'success' => true,
            'data' => $response,
            'message' => '문진 결과가 변경되었습니다.',
        ]);
    }

    /**
     * 문진표 재작성 (답변 변경 → 결과 자동 재계산)
     */
    public function updateAnswers(Request $request, $id)
    {
        $response = SurveyResponse::find($id);

        if (!$response) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '응답을 찾을 수 없습니다.'],
            ], 404);
        }

        $request->validate([
            'answers' => 'required|array',
            'override_reason' => 'nullable|string|max:500',
        ]);

        $oldData = [
            'survey_result' => $response->survey_result,
            'answers_json' => $response->answers_json,
            'manual_override' => $response->manual_override,
        ];

        // 활성 질문 가져오기
        $questions = Question::active()->ordered()->get();

        // 결과 계산
        $newResult = $this->calculateResult($request->answers, $questions);

        // 답변 스냅샷 생성
        $answersSnapshot = $this->createAnswersSnapshot($request->answers, $questions);

        $response->update([
            'survey_result' => $newResult,
            'answers_json' => $answersSnapshot,
            'manual_override' => true,
            'override_reason' => $request->override_reason,
        ]);

        AuditLog::log('resurvey', 'survey_response', $response->id, $oldData, [
            'survey_result' => $newResult,
            'answers_json' => $answersSnapshot,
            'manual_override' => true,
            'override_reason' => $response->override_reason,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'survey_result' => $newResult,
                'answers' => $answersSnapshot,
            ],
            'message' => '문진표가 재작성되었습니다.',
        ]);
    }

    /**
     * 문진 결과 계산
     */
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
     * 답변 스냅샷 생성
     */
    private function createAnswersSnapshot(array $answers, $questions): array
    {
        $snapshot = [];

        foreach ($questions as $question) {
            $answerValue = $answers[$question->id] ?? null;
            if ($answerValue === null) continue;

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
