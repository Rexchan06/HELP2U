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
        Schema::create('volunteer_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('volunteer_profile_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('PENDING')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            
            $table->unique(['support_request_id', 'volunteer_profile_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_matches');
    }
};
