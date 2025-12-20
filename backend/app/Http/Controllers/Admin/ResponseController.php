<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ResponsesExport;

class ResponseController extends Controller
{
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

        $responses = $query->orderBy('created_at', 'desc')->get();

        return Excel::download(new ResponsesExport($responses), 'responses.xlsx');
    }
}
