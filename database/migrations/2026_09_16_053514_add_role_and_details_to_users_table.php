<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('employee')->after('email');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete()->after('role');
            $table->string('phone')->nullable()->after('department_id');
            $table->string('job_title')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('job_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['role', 'department_id', 'phone', 'job_title', 'is_active']);
        });
    }
};
