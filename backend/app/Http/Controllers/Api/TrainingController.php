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
            ->orderBy('training_date', 'asc')
            ->get()
            ->map(function ($training) {
                return [
                    'id' => $training->id,
                    'name' => $training->name,
                    'training_date' => $training->training_date->format('Y-m-d'),
                    'status' => $training->status,
                    'exit_mode' => $training->exit_mode,
                    'lunch_image_mon' => $training->lunch_image_mon,
                    'lunch_image_tue' => $training->lunch_image_tue,
                    'lunch_image_wed' => $training->lunch_image_wed,
                    'lunch_image_thu' => $training->lunch_image_thu,
                    'lunch_image_fri' => $training->lunch_image_fri,
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
                'training_date' => $training->training_date->format('Y-m-d'),
                'status' => $training->status,
                'exit_mode' => $training->exit_mode,
                'lunch_image_mon' => $training->lunch_image_mon,
                'lunch_image_tue' => $training->lunch_image_tue,
                'lunch_image_wed' => $training->lunch_image_wed,
                'lunch_image_thu' => $training->lunch_image_thu,
                'lunch_image_fri' => $training->lunch_image_fri,
            ],
        ]);
    }
}
