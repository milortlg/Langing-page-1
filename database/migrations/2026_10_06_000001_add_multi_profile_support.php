<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Multi-profils :
 *  - chaque profil a un "slug" => lien public monvip.fr/{slug}
 *  - chaque publication appartient à un profil (profile_id)
 *
 * Les données existantes sont conservées : les profils existants reçoivent
 * un slug généré depuis leur nom, et toutes les publications existantes
 * sont rattachées au premier profil (Angelina).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        // Slug pour les profils existants
        $used = [];
        foreach (DB::table('profiles')->orderBy('id')->get() as $profile) {
            $base = Str::slug(Str::before($profile->name, '_')) ?: 'profil';
            if (in_array($base, \App\Models\Profile::RESERVED_SLUGS, true)) {
                $base .= '-vip';
            }
            $slug = $base;
            $i = 2;
            while (in_array($slug, $used, true)) {
                $slug = $base.'-'.$i++;
            }
            $used[] = $slug;
            DB::table('profiles')->where('id', $profile->id)->update(['slug' => $slug]);
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->unique('slug');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('profile_id')
                ->nullable()
                ->after('id')
                ->constrained('profiles')
                ->nullOnDelete();
        });

        // Rattacher les publications existantes au premier profil
        $firstProfileId = DB::table('profiles')->orderBy('id')->value('id');
        if ($firstProfileId) {
            DB::table('posts')->whereNull('profile_id')->update(['profile_id' => $firstProfileId]);
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('profile_id');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
