<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(15);
            $table->json('working_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('employee')->after('email');
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_code')->unique();
            $table->string('phone')->nullable();
            $table->date('joined_at')->nullable();
            $table->decimal('basic_salary', 14, 2)->default(0);
            $table->decimal('attendance_allowance', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('work_minutes')->default(0);
            $table->string('status', 20)->default('present');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'attendance_date']);
            $table->index(['attendance_date', 'status']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('total_days');
            $table->text('reason');
            $table->string('attachment_path')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->index(['status', 'start_date']);
        });

        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20);
            $table->decimal('amount', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('basic_salary', 14, 2)->default(0);
            $table->decimal('attendance_allowance', 14, 2)->default(0);
            $table->decimal('reward_total', 14, 2)->default(0);
            $table->decimal('late_deduction', 14, 2)->default(0);
            $table->decimal('absence_deduction', 14, 2)->default(0);
            $table->decimal('punishment_total', 14, 2)->default(0);
            $table->decimal('net_salary', 14, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->unique(['employee_id', 'period_start', 'period_end']);
        });

        Schema::create('reward_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('condition_type', 30);
            $table->decimal('value', 14, 2)->default(0);
            $table->string('reward_type', 20);
            $table->decimal('reward_value', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 14, 2)->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('awarded_at');
            $table->timestamps();
        });

        Schema::create('punishment_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('condition_type', 30);
            $table->decimal('value', 14, 2)->default(0);
            $table->string('punishment_type', 20);
            $table->decimal('amount', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('punishments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('punishment_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('issued_at');
            $table->timestamps();
        });

        Schema::create('qr_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('token', 100)->unique();
            $table->string('type', 20)->default('check_in');
            $table->timestamp('valid_from');
            $table->timestamp('valid_until');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('qr_attendances');
        Schema::dropIfExists('punishments');
        Schema::dropIfExists('punishment_rules');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('reward_rules');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('salary_components');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('employees');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        Schema::dropIfExists('work_schedules');
        Schema::dropIfExists('positions');
    }
};
