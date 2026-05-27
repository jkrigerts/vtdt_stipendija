<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->timestamps();
        });

        Schema::create('calculation_sessions', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('monthly_budget', 10, 2);
            $table->json('grade_table');
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('calculation_sessions')->cascadeOnDelete();
            $table->string('surname');
            $table->string('first_name');
            $table->string('personal_id');
            $table->string('group_name');
            $table->timestamps();
        });

        Schema::create('grade_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('subject_name');
            $table->string('grade_type');
            $table->string('grade_value');
            $table->date('grade_date');
            $table->boolean('excluded')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_records');
        Schema::dropIfExists('students');
        Schema::dropIfExists('calculation_sessions');
        Schema::dropIfExists('subjects');
    }
};
