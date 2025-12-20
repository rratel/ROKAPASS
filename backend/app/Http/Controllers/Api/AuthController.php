<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => '이메일 또는 비밀번호가 올바르지 않습니다.',
                ],
            ], 401);
        }

        if (!$admin->is_active) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => '비활성화된 계정입니다.',
                ],
            ], 403);
        }

        $admin->update(['last_login_at' => now()]);

        $token = $admin->createToken('admin-token')->plainTextToken;

        AuditLog::log('login', 'admin', $admin->id);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $admin->role,
                ],
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $admin = $request->user();

        if ($admin) {
            AuditLog::log('logout', 'admin', $admin->id);
            $admin->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => '로그아웃되었습니다.',
        ]);
    }

    public function me(Request $request)
    {
        $admin = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
            ],
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $admin = $request->user();

        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => '현재 비밀번호가 올바르지 않습니다.',
                ],
            ], 422);
        }

        $admin->update([
            'password' => Hash::make($request->new_password),
        ]);

        AuditLog::log('password_change', 'admin', $admin->id);

        return response()->json([
            'success' => true,
            'message' => '비밀번호가 변경되었습니다.',
        ]);
    }
}
