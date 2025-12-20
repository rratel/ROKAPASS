<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function index()
    {
        $admins = Admin::orderBy('created_at', 'desc')->get([
            'id', 'name', 'email', 'role', 'is_active', 'last_login_at', 'created_at',
        ]);

        return response()->json([
            'success' => true,
            'data' => $admins,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|min:8',
            'role' => 'in:super_admin,admin,viewer',
        ]);

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'admin',
            'is_active' => true,
        ]);

        AuditLog::log('create', 'admin', $admin->id, null, [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
            ],
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $admin = Admin::find($id);

        if (!$admin) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '관리자를 찾을 수 없습니다.'],
            ], 404);
        }

        $request->validate([
            'name' => 'string|max:100',
            'email' => 'email|unique:admins,email,' . $id,
            'password' => 'nullable|min:8',
            'role' => 'in:super_admin,admin,viewer',
        ]);

        $oldValues = [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role,
        ];

        $updateData = $request->only(['name', 'email', 'role']);

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $admin->update($updateData);

        AuditLog::log('update', 'admin', $admin->id, $oldValues, [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role,
        ]);

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

    public function destroy(Request $request, $id)
    {
        $currentAdmin = $request->user();

        if ($currentAdmin->id === (int) $id) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '자신의 계정은 삭제할 수 없습니다.'],
            ], 400);
        }

        $admin = Admin::find($id);

        if (!$admin) {
            return response()->json([
                'success' => false,
                'error' => ['message' => '관리자를 찾을 수 없습니다.'],
            ], 404);
        }

        $oldValues = [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role,
        ];

        $admin->delete();

        AuditLog::log('delete', 'admin', $id, $oldValues, null);

        return response()->json([
            'success' => true,
            'message' => '삭제되었습니다.',
        ]);
    }
}
