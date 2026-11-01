<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_news_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_news_id')->constrained('school_news')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('caption', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['school_news_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_news_photos');
    }
};
