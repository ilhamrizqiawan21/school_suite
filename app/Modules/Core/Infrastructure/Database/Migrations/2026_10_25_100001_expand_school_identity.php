<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('name');
            $table->string('school_status', 30)->nullable()->after('level');
            $table->string('accreditation', 10)->nullable()->after('school_status');
            $table->string('principal_name')->nullable()->after('email');
            $table->string('website')->nullable()->after('principal_name');
            $table->text('vision')->nullable()->after('website');
            $table->text('mission')->nullable()->after('vision');
        });
    }

    public function down(): void
    {
        Schema::table('schools', fn (Blueprint $table) => $table->dropColumn(['legal_name', 'school_status', 'accreditation', 'principal_name', 'website', 'vision', 'mission']));
    }
};
