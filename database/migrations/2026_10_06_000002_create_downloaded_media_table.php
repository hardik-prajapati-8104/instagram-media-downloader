<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downloaded_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('download_request_id')->constrained('download_requests')->cascadeOnDelete();
            $table->string('media_type', 16); // image|video|audio|zip
            $table->text('original_media_url')->nullable(); // stored WITHOUT query string (may contain tokens)
            $table->text('thumbnail_url')->nullable();
            $table->string('storage_path', 512);
            $table->string('file_name', 255);
            $table->string('file_extension', 10);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->decimal('duration', 10, 2)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['download_request_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloaded_media');
    }
};
