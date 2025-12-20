<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'default_purge_days' => Setting::get('default_purge_days', 3),
            'auto_entry_on_qr' => Setting::get('auto_entry_on_qr', true),
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'default_purge_days' => 'integer|min:1|max:30',
            'auto_entry_on_qr' => 'boolean',
        ]);

        if ($request->has('default_purge_days')) {
            Setting::set('default_purge_days', $request->default_purge_days, 'integer', '기본 데이터 보존 기간(일)');
        }

        if ($request->has('auto_entry_on_qr')) {
            Setting::set('auto_entry_on_qr', $request->auto_entry_on_qr, 'boolean', 'QR 발급 시 자동 입소 처리');
        }

        AuditLog::log('update', 'settings', null, null, $request->all());

        return response()->json([
            'success' => true,
            'message' => '설정이 저장되었습니다.',
        ]);
    }
}
