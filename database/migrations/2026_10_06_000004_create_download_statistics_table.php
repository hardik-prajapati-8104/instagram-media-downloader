<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_statistics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('media_type', 16);
            $table->unsignedInteger('total_requests')->default(0);
            $table->unsignedInteger('successful_requests')->default(0);
            $table->unsignedInteger('failed_requests')->default(0);
            $table->timestamps();

            $table->unique(['date', 'media_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_statistics');
    }
};
