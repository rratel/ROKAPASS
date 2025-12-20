<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::set('default_purge_days', 3, 'integer', '기본 데이터 보존 기간(일)');
        Setting::set('auto_entry_on_qr', true, 'boolean', 'QR 발급 시 자동 입소 처리');
    }
}
