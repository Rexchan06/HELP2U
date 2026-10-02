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
        Schema::create('support_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volunteer_match_id')->unique()->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_start');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('status', 20)->default('SCHEDULED')->index();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_sessions');
    }
};
