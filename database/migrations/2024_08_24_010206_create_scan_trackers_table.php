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
        Schema::create('scan_trackers', function (Blueprint $table) {
            $table->id();
            $table->string('ciUploadId')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('respondent_email')->nullable();
            $table->string('progress')->nullable();
            $table->string('no_of_threats_found')->nullable();
            $table->string('details_url')->nullable();
            $table->string('processing_id')->unique();
            $table->longText('auth_token')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_trackers');
    }
};
