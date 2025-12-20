<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->date('training_date');
            $table->enum('status', ['scheduled', 'active', 'completed', 'purged'])->default('scheduled');
            // 요일별 중식 이미지 (월~금)
            $table->string('lunch_image_mon', 500)->nullable();
            $table->string('lunch_image_tue', 500)->nullable();
            $table->string('lunch_image_wed', 500)->nullable();
            $table->string('lunch_image_thu', 500)->nullable();
            $table->string('lunch_image_fri', 500)->nullable();
            // 퇴소 모드 설정
            $table->enum('exit_mode', ['auto', 'confirm'])->default('auto');
            $table->timestamp('auto_purge_at')->nullable();
            $table->integer('purge_days')->default(3);
            $table->timestamps();

            $table->index('training_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
