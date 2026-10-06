<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Carbon\Carbon;
use Spatie\Image\Enums\Fit;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Profile extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * Mots réservés : ne peuvent pas être utilisés comme lien de profil
     * car ils correspondent déjà à des pages du site.
     */
    public const RESERVED_SLUGS = [
        'admin', 'media', 'settings', 'login', 'logout', 'register', 'livewire',
        'filament', 'storage', 'build', 'up', 'dashboard', 'password', 'email',
        'two-factor', 'user', 'api', 'fonts', 'css', 'js', 'favicon', 'robots',
    ];

    protected $fillable = [
        'name',
        'slug',
        'biography',
        'is_online',
        'photos_count',
        'videos_count',
        'likes_count',
        'action_label',
        'rencontre_primary_label',
        'rencontre_secondary_label',
        'online_from',
        'online_to',
        'script_url',
        'description',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'online_from' => 'string',
        'online_to' => 'string',
        'script_url' => 'string',
        'description' => 'string',
    ];

    protected static function booted(): void
    {
        // Génère automatiquement le lien si aucun n'a été saisi
        static::saving(function (self $profile) {
            if (blank($profile->slug)) {
                $profile->slug = static::uniqueSlugFrom($profile->name, $profile->id);
            } else {
                $profile->slug = Str::slug($profile->slug);
            }
        });
    }

    public static function uniqueSlugFrom(?string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug(Str::before((string) $value, '_')) ?: 'profil';
        if (in_array($base, static::RESERVED_SLUGS, true)) {
            $base .= '-vip';
        }

        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Lien public de la landing page du profil (ex : https://monvip.fr/alycia)
     */
    public function getPublicUrlAttribute(): string
    {
        return route('profile.show', ['slug' => $this->slug]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('avatar_preview')
            ->fit(Fit::Crop, 300, 300)
            ->nonQueued()
            ->performOnCollections('avatar');
    
        $this->addMediaConversion('banner_preview')
            ->fit(Fit::Crop, 1200, 450)
            ->nonQueued()
            ->performOnCollections('banner');

        $this->addMediaConversion('logo_preview')
            ->fit(Fit::Crop, 1200, 450)
            ->nonQueued()
            ->performOnCollections('logo');

        $this->addMediaConversion('certification_preview')
            ->fit(Fit::Crop, 100, 100)
            ->nonQueued()
            ->performOnCollections('certification');
    }
    

    public function isWithinOnlineHours(?Carbon $now = null, ?string $tz = null): bool
    {
        if (! $this->online_from || ! $this->online_to) {
            return false;
        }

        $now = ($now ?? now())->copy();
        if ($tz) {
            $now->setTimezone($tz);
        }

        $toMinutes = fn (string $time) => (int) Carbon::createFromFormat('H:i:s', strlen($time) === 5 ? "{$time}:00" : $time)
            ->format('H') * 60
            + (int) Carbon::createFromFormat('H:i:s', strlen($time) === 5 ? "{$time}:00" : $time)->format('i');

        $start = $toMinutes($this->online_from);
        $end   = $toMinutes($this->online_to); 
        $current = ((int) $now->format('H')) * 60 + (int) $now->format('i');

        if ($start <= $end) {
            return $current >= $start && $current <= $end;
        }

        return $current >= $start || $current <= $end;
    }
}

