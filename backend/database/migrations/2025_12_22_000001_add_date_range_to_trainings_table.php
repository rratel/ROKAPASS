<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            // training_date를 start_date로 변경하고 end_date 추가
            $table->date('start_date')->nullable()->after('name');
            $table->date('end_date')->nullable()->after('start_date');
        });

        // 기존 training_date 데이터를 start_date, end_date로 복사
        DB::statement('UPDATE trainings SET start_date = training_date, end_date = training_date');

        Schema::table('trainings', function (Blueprint $table) {
            $table->dropIndex(['training_date']);
            $table->dropColumn('training_date');
            $table->index('start_date');
            $table->index('end_date');
        });

        // NOT NULL 제약 추가
        Schema::table('trainings', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->date('training_date')->nullable()->after('name');
        });

        DB::statement('UPDATE trainings SET training_date = start_date');

        Schema::table('trainings', function (Blueprint $table) {
            $table->dropIndex(['start_date']);
            $table->dropIndex(['end_date']);
            $table->dropColumn(['start_date', 'end_date']);
            $table->index('training_date');
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->date('training_date')->nullable(false)->change();
        });
    }
};
