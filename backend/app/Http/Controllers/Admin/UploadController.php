<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function uploadLunchImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        $file = $request->file('image');
        $filename = 'lunch_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('lunch_images', $filename, 'public');

        return response()->json([
            'success' => true,
            'data' => [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ],
        ]);
    }

    public function deleteLunchImage(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->path;

        // Only allow deleting files in lunch_images directory
        if (!str_starts_with($path, 'lunch_images/')) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '잘못된 경로입니다.'],
            ], 400);
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
