<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Training;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->string('access_code', 10)->nullable()->unique()->after('name');
        });

        // 기존 훈련에 access_code 생성
        Training::whereNull('access_code')->each(function ($training) {
            $training->update([
                'access_code' => $this->generateUniqueCode()
            ]);
        });

        // nullable 제거
        Schema::table('trainings', function (Blueprint $table) {
            $table->string('access_code', 10)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn('access_code');
        });
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Training::where('access_code', $code)->exists());

        return $code;
    }
};
