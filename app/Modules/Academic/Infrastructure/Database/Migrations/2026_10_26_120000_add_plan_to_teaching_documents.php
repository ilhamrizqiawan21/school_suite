<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teaching_documents', function (Blueprint $table): void {
            $table->foreignId('plan_id')->nullable()->after('term_id')->constrained('teaching_plans')->cascadeOnDelete();
            $table->index(['teacher_id', 'term_id', 'plan_id', 'type', 'semester'], 'teaching_documents_owner_plan_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('teaching_documents', function (Blueprint $table): void {
            $table->dropIndex('teaching_documents_owner_plan_type_idx');
            $table->dropConstrainedForeignId('plan_id');
        });
    }
};
