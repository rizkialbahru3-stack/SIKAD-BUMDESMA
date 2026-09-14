<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('checkout_status', 20)->default('waiting')->after('status');
            $table->string('checkout_approval_type', 20)->nullable()->after('checkout_status');
            $table->dateTime('checkout_approved_at')->nullable()->after('checkout_approval_type');
            $table->foreignId('checkout_approved_by')->nullable()->after('checkout_approved_at')->constrained('users')->nullOnDelete();
        });

        DB::statement("UPDATE attendances SET checkout_status = 'checked_out' WHERE check_out_at IS NOT NULL");
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checkout_approved_by');
            $table->dropColumn(['checkout_status', 'checkout_approval_type', 'checkout_approved_at']);
        });
    }
};
