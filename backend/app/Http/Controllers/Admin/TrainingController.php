<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Training;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    public function index(Request $request)
    {
        $query = Training::query();

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $trainings = $query->orderBy('training_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $trainings,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'training_date' => 'required|date',
            'purge_days' => 'integer|min:1|max:30',
            'lunch_image_mon' => 'nullable|url|max:500',
            'lunch_image_tue' => 'nullable|url|max:500',
            'lunch_image_wed' => 'nullable|url|max:500',
            'lunch_image_thu' => 'nullable|url|max:500',
            'lunch_image_fri' => 'nullable|url|max:500',
        ]);

        $training = Training::create([
            'name' => $request->name,
            'training_date' => $request->training_date,
            'status' => 'scheduled',
            'purge_days' => $request->purge_days ?? 3,
            'lunch_image_mon' => $request->lunch_image_mon,
            'lunch_image_tue' => $request->lunch_image_tue,
            'lunch_image_wed' => $request->lunch_image_wed,
            'lunch_image_thu' => $request->lunch_image_thu,
            'lunch_image_fri' => $request->lunch_image_fri,
        ]);

        AuditLog::log('create', 'training', $training->id, null, $training->toArray());

        return response()->json([
            'success' => true,
            'data' => $training,
        ], 201);
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
            'data' => $training,
        ]);
    }

    public function update(Request $request, $id)
    {
        $training = Training::find($id);

        if (!$training) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '훈련을 찾을 수 없습니다.'],
            ], 404);
        }

        $request->validate([
            'name' => 'string|max:200',
            'training_date' => 'date',
            'purge_days' => 'integer|min:1|max:30',
            'lunch_image_mon' => 'nullable|url|max:500',
            'lunch_image_tue' => 'nullable|url|max:500',
            'lunch_image_wed' => 'nullable|url|max:500',
            'lunch_image_thu' => 'nullable|url|max:500',
            'lunch_image_fri' => 'nullable|url|max:500',
        ]);

        $oldValues = $training->toArray();
        $training->update($request->only([
            'name', 'training_date', 'purge_days',
            'lunch_image_mon', 'lunch_image_tue', 'lunch_image_wed',
            'lunch_image_thu', 'lunch_image_fri',
        ]));

        AuditLog::log('update', 'training', $training->id, $oldValues, $training->toArray());

        return response()->json([
            'success' => true,
            'data' => $training,
        ]);
    }

    public function destroy($id)
    {
        $training = Training::find($id);

        if (!$training) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '훈련을 찾을 수 없습니다.'],
            ], 404);
        }

        $oldValues = $training->toArray();
        $training->delete();

        AuditLog::log('delete', 'training', $id, $oldValues, null);

        return response()->json([
            'success' => true,
            'message' => '삭제되었습니다.',
        ]);
    }

    public function activate($id)
    {
        $training = Training::find($id);

        if (!$training) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '훈련을 찾을 수 없습니다.'],
            ], 404);
        }

        $training->activate();

        AuditLog::log('activate', 'training', $training->id);

        return response()->json([
            'success' => true,
            'data' => $training,
        ]);
    }

    public function pause($id)
    {
        $training = Training::find($id);

        if (!$training) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '훈련을 찾을 수 없습니다.'],
            ], 404);
        }

        if ($training->status !== 'active') {
            return response()->json([
                'success' => false,
                'error' => ['message' => '진행중인 훈련만 일시정지할 수 있습니다.'],
            ], 400);
        }

        $training->pause();

        AuditLog::log('pause', 'training', $training->id);

        return response()->json([
            'success' => true,
            'data' => $training,
        ]);
    }

    public function complete($id)
    {
        $training = Training::find($id);

        if (!$training) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '훈련을 찾을 수 없습니다.'],
            ], 404);
        }

        if ($training->status !== 'active') {
            return response()->json([
                'success' => false,
                'error' => ['message' => '진행중인 훈련만 종료할 수 있습니다.'],
            ], 400);
        }

        $training->complete();

        AuditLog::log('complete', 'training', $training->id);

        return response()->json([
            'success' => true,
            'data' => $training,
        ]);
    }
}
