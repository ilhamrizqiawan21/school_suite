<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_gallery_photos', function (Blueprint $table): void {
            $table->foreignId('school_gallery_album_id')
                ->nullable()
                ->after('school_id')
                ->constrained('school_gallery_albums')
                ->cascadeOnDelete();
        });

        DB::table('school_gallery_photos')->orderBy('id')->get()->each(function (object $photo): void {
            $albumId = DB::table('school_gallery_albums')->insertGetId([
                'school_id' => $photo->school_id,
                'title' => $photo->title ?: ($photo->original_name ?: 'Foto sekolah'),
                'caption' => $photo->caption,
                'is_published' => $photo->is_published,
                'sort_order' => $photo->sort_order,
                'created_at' => $photo->created_at,
                'updated_at' => $photo->updated_at,
            ]);

            DB::table('school_gallery_photos')
                ->where('id', $photo->id)
                ->update(['school_gallery_album_id' => $albumId]);
        });
    }

    public function down(): void
    {
        Schema::table('school_gallery_photos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('school_gallery_album_id');
        });
    }
};
