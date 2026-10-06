<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compteurs du profil (photos, vidéos, likes) en texte libre :
 * permet d'afficher "31,3k", "1,2M", "935"... exactement comme saisi dans l'admin.
 * Les valeurs existantes (ex : 371) sont conservées telles quelles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('photos_count', 30)->default('0')->change();
            $table->string('videos_count', 30)->default('0')->change();
            $table->string('likes_count', 30)->default('0')->change();
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->integer('photos_count')->default(0)->change();
            $table->integer('videos_count')->default(0)->change();
            $table->integer('likes_count')->default(0)->change();
        });
    }
};
