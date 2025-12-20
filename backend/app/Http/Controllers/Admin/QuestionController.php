<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index()
    {
        $questions = Question::ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $questions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'question_text' => 'required|string',
            'description' => 'nullable|string',
            'question_type' => 'in:yes_no,multiple_choice',
            'options' => 'required|array|min:2',
            'options.*.label' => 'required|string',
            'options.*.value' => 'required|string',
            'options.*.is_danger' => 'boolean',
            'order_num' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        $question = Question::create([
            'question_text' => $request->question_text,
            'description' => $request->description,
            'question_type' => $request->question_type ?? 'yes_no',
            'options' => $request->options,
            'order_num' => $request->order_num ?? (Question::max('order_num') + 1),
            'is_active' => $request->is_active ?? true,
        ]);

        AuditLog::log('create', 'question', $question->id, null, $question->toArray());

        return response()->json([
            'success' => true,
            'data' => $question,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $question = Question::find($id);

        if (!$question) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '문항을 찾을 수 없습니다.'],
            ], 404);
        }

        $request->validate([
            'question_text' => 'string',
            'description' => 'nullable|string',
            'question_type' => 'in:yes_no,multiple_choice',
            'options' => 'array|min:2',
            'options.*.label' => 'required_with:options|string',
            'options.*.value' => 'required_with:options|string',
            'options.*.is_danger' => 'boolean',
            'order_num' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        $oldValues = $question->toArray();
        $question->update($request->only([
            'question_text', 'description', 'question_type',
            'options', 'order_num', 'is_active',
        ]));

        AuditLog::log('update', 'question', $question->id, $oldValues, $question->toArray());

        return response()->json([
            'success' => true,
            'data' => $question,
        ]);
    }

    public function destroy($id)
    {
        $question = Question::find($id);

        if (!$question) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '문항을 찾을 수 없습니다.'],
            ], 404);
        }

        $oldValues = $question->toArray();
        $question->delete();

        AuditLog::log('delete', 'question', $id, $oldValues, null);

        return response()->json([
            'success' => true,
            'message' => '삭제되었습니다.',
        ]);
    }

    public function toggle($id)
    {
        $question = Question::find($id);

        if (!$question) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '문항을 찾을 수 없습니다.'],
            ], 404);
        }

        $question->update(['is_active' => !$question->is_active]);

        AuditLog::log('toggle', 'question', $question->id);

        return response()->json([
            'success' => true,
            'data' => $question,
        ]);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:questions,id',
            'items.*.order_num' => 'required|integer|min:1',
        ]);

        foreach ($request->items as $item) {
            Question::where('id', $item['id'])->update(['order_num' => $item['order_num']]);
        }

        AuditLog::log('reorder', 'question');

        return response()->json([
            'success' => true,
            'message' => '순서가 변경되었습니다.',
        ]);
    }
}
