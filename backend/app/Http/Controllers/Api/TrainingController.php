<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Training;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    public function active()
    {
        $trainings = Training::whereIn('status', ['scheduled', 'active'])
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(function ($training) {
                return [
                    'id' => $training->id,
                    'name' => $training->name,
                    'start_date' => $training->start_date->format('Y-m-d'),
                    'end_date' => $training->end_date->format('Y-m-d'),
                    'status' => $training->status,
                    'exit_mode' => $training->exit_mode,
                    'lunch_image_mon' => $training->lunch_image_mon,
                    'lunch_image_tue' => $training->lunch_image_tue,
                    'lunch_image_wed' => $training->lunch_image_wed,
                    'lunch_image_thu' => $training->lunch_image_thu,
                    'lunch_image_fri' => $training->lunch_image_fri,
                    'lunch_image_mon_url' => $training->lunch_image_mon_url,
                    'lunch_image_tue_url' => $training->lunch_image_tue_url,
                    'lunch_image_wed_url' => $training->lunch_image_wed_url,
                    'lunch_image_thu_url' => $training->lunch_image_thu_url,
                    'lunch_image_fri_url' => $training->lunch_image_fri_url,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $trainings,
        ]);
    }

    public function show($id)
    {
        $training = Training::find($id);

        if (!$training) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '훈련을 찾을 수 없습니다.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $training->id,
                'name' => $training->name,
                'start_date' => $training->start_date->format('Y-m-d'),
                'end_date' => $training->end_date->format('Y-m-d'),
                'status' => $training->status,
                'exit_mode' => $training->exit_mode,
                'lunch_image_mon' => $training->lunch_image_mon,
                'lunch_image_tue' => $training->lunch_image_tue,
                'lunch_image_wed' => $training->lunch_image_wed,
                'lunch_image_thu' => $training->lunch_image_thu,
                'lunch_image_fri' => $training->lunch_image_fri,
                'lunch_image_mon_url' => $training->lunch_image_mon_url,
                'lunch_image_tue_url' => $training->lunch_image_tue_url,
                'lunch_image_wed_url' => $training->lunch_image_wed_url,
                'lunch_image_thu_url' => $training->lunch_image_thu_url,
                'lunch_image_fri_url' => $training->lunch_image_fri_url,
            ],
        ]);
    }

    /**
     * Get training by access code (for QR scanned entry)
     */
    public function showByCode($code)
    {
        $training = Training::where('access_code', strtoupper($code))->first();

        if (!$training) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '유효하지 않은 훈련 코드입니다.'],
            ], 404);
        }

        // 훈련이 활성화되지 않은 경우 (예정/일시정지 상태)
        if ($training->status === 'scheduled') {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => '훈련이 아직 시작되지 않았습니다.',
                    'code' => 'NOT_STARTED',
                ],
            ], 403);
        }

        // 훈련이 종료된 경우
        if (in_array($training->status, ['completed', 'purged'])) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => '종료된 훈련입니다.',
                    'code' => 'COMPLETED',
                ],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $training->id,
                'name' => $training->name,
                'access_code' => $training->access_code,
                'start_date' => $training->start_date->format('Y-m-d'),
                'end_date' => $training->end_date->format('Y-m-d'),
                'status' => $training->status,
                'exit_mode' => $training->exit_mode,
                'lunch_image_mon' => $training->lunch_image_mon,
                'lunch_image_tue' => $training->lunch_image_tue,
                'lunch_image_wed' => $training->lunch_image_wed,
                'lunch_image_thu' => $training->lunch_image_thu,
                'lunch_image_fri' => $training->lunch_image_fri,
                // URL 형식으로도 반환 (프론트엔드에서 사용)
                'lunch_image_mon_url' => $training->lunch_image_mon_url,
                'lunch_image_tue_url' => $training->lunch_image_tue_url,
                'lunch_image_wed_url' => $training->lunch_image_wed_url,
                'lunch_image_thu_url' => $training->lunch_image_thu_url,
                'lunch_image_fri_url' => $training->lunch_image_fri_url,
            ],
        ]);
    }
}
