<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;

class ExitScannerController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate([
            'training_id' => 'required|exists:trainings,id',
            'uuid' => 'required|string',
        ]);

        $response = SurveyResponse::where('uuid', $request->uuid)
            ->where('training_id', $request->training_id)
            ->first();

        if (!$response) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '등록된 QR 코드가 아닙니다.'],
            ], 404);
        }

        if ($response->attendance_status === 'registered') {
            return response()->json([
                'success' => false,
                'error' => ['message' => '아직 입소하지 않은 인원입니다.'],
            ], 400);
        }

        if ($response->attendance_status === 'exited') {
            return response()->json([
                'success' => false,
                'error' => ['message' => '이미 퇴소 처리된 인원입니다.'],
            ], 400);
        }

        // confirm 모드에서는 바로 처리하지 않고 정보만 반환
        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $response->uuid,
                'name' => $response->name,
                'survey_result' => $response->survey_result,
            ],
        ]);
    }

    public function confirmExit(Request $request)
    {
        $request->validate([
            'uuid' => 'required|string',
        ]);

        $response = SurveyResponse::where('uuid', $request->uuid)->first();

        if (!$response) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '등록된 QR 코드가 아닙니다.'],
            ], 404);
        }

        if ($response->attendance_status === 'registered') {
            return response()->json([
                'success' => false,
                'error' => ['message' => '아직 입소하지 않은 인원입니다.'],
            ], 400);
        }

        if ($response->attendance_status === 'exited') {
            return response()->json([
                'success' => false,
                'error' => ['message' => '이미 퇴소 처리된 인원입니다.'],
            ], 400);
        }

        $adminId = $request->user()->id;
        $response->markAsExited($adminId);

        AuditLog::log('exit', 'survey_response', $response->id, null, [
            'name' => $response->name,
            'exited_at' => $response->exited_at,
        ]);

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
