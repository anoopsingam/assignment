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
        Schema::create('scan_infos', function (Blueprint $table) {
            $table->id();
            $table->string('totalScansCount')->default(0);
            $table->string('remainingScansCount')->default(0);
            $table->string('scansCountPercentage')->default(0);
            $table->string('estimatedDaysLeftToUtilize')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_infos');
    }
};
