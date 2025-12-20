<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponse;
use App\Models\Training;
use Illuminate\Http\Request;

class KioskController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate([
            'training_id' => 'required|exists:trainings,id',
            'uuid' => 'required|string',
        ]);

        $training = Training::find($request->training_id);

        if (!$training || $training->status !== 'active') {
            return response()->json([
                'success' => false,
                'error' => ['message' => '진행 중인 훈련이 아닙니다.'],
            ], 400);
        }

        $response = SurveyResponse::where('uuid', $request->uuid)
            ->where('training_id', $request->training_id)
            ->first();

        if (!$response) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '등록된 QR 코드가 아닙니다.'],
            ], 404);
        }

        // 현재 상태에 따라 입소/퇴소 처리
        if ($response->attendance_status === 'registered') {
            // 아직 입소하지 않은 경우 - 입소 처리
            $response->markAsEntered();

            return response()->json([
                'success' => true,
                'data' => [
                    'uuid' => $response->uuid,
                    'name' => $response->name,
                    'action' => 'entry',
                    'survey_result' => $response->survey_result,
                    'message' => '입소 처리되었습니다.',
                    'tts_message' => "{$response->name}님, 입소 처리되었습니다.",
                ],
            ]);
        } elseif ($response->attendance_status === 'entered') {
            // 이미 입소한 경우 - 퇴소 처리
            if ($training->exit_mode === 'auto') {
                $response->markAsExited();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'uuid' => $response->uuid,
                        'name' => $response->name,
                        'action' => 'exit',
                        'survey_result' => $response->survey_result,
                        'message' => '퇴소 처리되었습니다.',
                        'tts_message' => "{$response->name}님, 퇴소 처리되었습니다.",
                    ],
                ]);
            } else {
                // confirm 모드 - 확인 대기
                return response()->json([
                    'success' => true,
                    'data' => [
                        'uuid' => $response->uuid,
                        'name' => $response->name,
                        'action' => 'exit_pending',
                        'survey_result' => $response->survey_result,
                        'message' => '퇴소 확인이 필요합니다.',
                        'requires_confirmation' => true,
                    ],
                ]);
            }
        } else {
            // 이미 퇴소한 경우
            return response()->json([
                'success' => false,
                'error' => ['message' => '이미 퇴소 처리된 예비군입니다.'],
            ], 400);
        }
    }

    public function confirmExit(Request $request)
    {
        $request->validate([
            'uuid' => 'required|string',
        ]);

        $response = SurveyResponse::where('uuid', $request->uuid)
            ->where('attendance_status', 'entered')
            ->first();

        if (!$response) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '퇴소 처리할 수 없습니다.'],
            ], 400);
        }

        $response->markAsExited();

        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $response->uuid,
                'name' => $response->name,
                'message' => '퇴소 처리되었습니다.',
            ],
        ]);
    }
}
