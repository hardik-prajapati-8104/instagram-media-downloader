<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->text('original_url');
            $table->string('normalized_url', 512);
            $table->string('requested_mode', 16)->default('media'); // media | audio
            $table->string('media_type', 16)->nullable();           // video|reel|photo|carousel|audio (detected)
            $table->string('status', 16)->default('pending');       // pending|processing|completed|failed|expired
            $table->string('provider', 64)->nullable();
            $table->string('title', 255)->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->string('error_message', 500)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('normalized_url');
            $table->index('status');
            $table->index('media_type');
            $table->index('requested_at');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_requests');
    }
};
