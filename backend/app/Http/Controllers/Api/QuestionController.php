<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;

class QuestionController extends Controller
{
    public function active()
    {
        $questions = Question::active()
            ->ordered()
            ->get()
            ->map(function ($question) {
                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'description' => $question->description,
                    'question_type' => $question->question_type,
                    'options' => $question->options,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $questions,
        ]);
    }
}
