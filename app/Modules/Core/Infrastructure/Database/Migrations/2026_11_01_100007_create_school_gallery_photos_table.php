<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_gallery_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('title', 120)->nullable();
            $table->string('caption', 255)->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['school_id', 'is_published']);
            $table->index(['school_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_gallery_photos');
    }
};
