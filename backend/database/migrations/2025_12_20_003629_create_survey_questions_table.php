<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->enum('type', ['YES_NO', 'CHECKBOX', 'TEXT'])->default('YES_NO');
            $table->enum('risk_level', ['HIGH', 'MEDIUM', 'LOW'])->default('LOW');
            $table->enum('trigger_action', ['DANGER', 'CAUTION', 'NONE'])->default('NONE');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
