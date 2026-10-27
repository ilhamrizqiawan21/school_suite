<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('inventory_code', 80);
            $table->string('name', 180);
            $table->string('category', 100);
            $table->string('location', 180)->nullable();
            $table->string('condition', 30)->default('good');
            $table->string('status', 30)->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'inventory_code']);
            $table->unique(['id', 'school_id']);
            $table->index(['school_id', 'status', 'category']);
        });

        Schema::create('facility_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('asset_id');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('borrower_name', 180);
            $table->string('borrower_reference', 100)->nullable();
            $table->date('borrowed_at');
            $table->date('due_at')->nullable();
            $table->date('returned_at')->nullable();
            $table->string('return_condition', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign(['asset_id', 'school_id'])
                ->references(['id', 'school_id'])
                ->on('facility_assets')
                ->cascadeOnDelete();
            $table->index(['school_id', 'returned_at', 'due_at']);
            $table->index(['asset_id', 'returned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_loans');
        Schema::dropIfExists('facility_assets');
    }
};
