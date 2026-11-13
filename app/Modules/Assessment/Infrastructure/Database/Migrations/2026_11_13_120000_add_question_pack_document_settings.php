<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_packs', function (Blueprint $table) {
            $table->json('document_settings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('question_packs', function (Blueprint $table) {
            $table->dropColumn('document_settings');
        });
    }
};
