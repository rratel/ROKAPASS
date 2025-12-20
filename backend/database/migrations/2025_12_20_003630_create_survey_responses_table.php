<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('name', 100);
            $table->binary('dob');           // AES-256 암호화
            $table->binary('phone');         // AES-256 암호화
            $table->string('bank_name', 50);
            $table->binary('account_num');   // AES-256 암호화
            $table->boolean('lunch_yn')->default(false);
            $table->enum('survey_result', ['NORMAL', 'CAUTION', 'DANGER']);
            $table->json('answers_json')->nullable();
            $table->mediumText('signature')->nullable(); // Base64 서명 이미지
            // 입소/퇴소 상태
            $table->enum('attendance_status', ['registered', 'entered', 'exited'])->default('registered');
            $table->timestamp('entered_at')->nullable();  // 입소 시간 (QR 발급 시)
            $table->timestamp('exited_at')->nullable();   // 퇴소 시간 (QR 스캔 시)
            $table->foreignId('exited_by')->nullable()->constrained('admins')->onDelete('set null');
            // 기타
            $table->boolean('manual_override')->default(false);
            $table->text('override_reason')->nullable();
            $table->timestamp('qr_generated_at')->nullable();
            $table->timestamps();

            $table->index('training_id');
            $table->index('uuid');
            $table->index('survey_result');
            $table->index('attendance_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
