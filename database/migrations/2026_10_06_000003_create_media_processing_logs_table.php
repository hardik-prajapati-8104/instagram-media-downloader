<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_processing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('download_request_id')->nullable()->constrained('download_requests')->nullOnDelete();
            $table->string('level', 16);
            $table->string('event', 64);
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('level');
            $table->index('event');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_processing_logs');
    }
};
