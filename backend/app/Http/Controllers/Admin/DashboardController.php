<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponse;
use App\Models\Training;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        $today = today();

        $todayResponses = SurveyResponse::whereDate('created_at', $today)->count();
        $todayEntries = SurveyResponse::whereDate('entered_at', $today)->count();
        $todayExits = SurveyResponse::whereDate('exited_at', $today)->count();
        $activeTrainings = Training::active()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'todayResponses' => $todayResponses,
                'todayEntries' => $todayEntries,
                'todayExits' => $todayExits,
                'activeTrainings' => $activeTrainings,
            ],
        ]);
    }
}
