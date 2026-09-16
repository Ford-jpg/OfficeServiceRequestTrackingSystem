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
        if (Schema::hasColumn('service_requests', 'location_id')) {
            Schema::table('service_requests', function (Blueprint $table) {
                $table->dropConstrainedForeignId('location_id');
            });
        }

        Schema::dropIfExists('locations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('building');
            $table->string('floor');
            $table->string('room_or_area');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->constrained('locations')->cascadeOnDelete();
        });
    }
};
