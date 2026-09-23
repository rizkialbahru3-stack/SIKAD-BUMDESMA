<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('photo');
            $table->date('visit_date');
            $table->timestamps();

            $table->index(['employee_id', 'visit_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_attendances');
    }
};
