<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'profile_id',
        'content',
        'type',
        'likes_count',
        'is_visible',
        'is_blurred',
        'is_live',
        'duration',
        'order',
        'created_at'
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_blurred' => 'boolean',
        'is_live' => 'boolean',
        'likes_count' => 'integer',
        'order' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $post) {
            if (method_exists($post, 'isForceDeleting') && ! $post->isForceDeleting()) {
                return;
            }

            $post->clearMediaCollection('media');
        });
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function registerMediaCollections(): void
    {
        // Les originaux sont stockés en privé (non accessibles publiquement)
        $this->addMediaCollection('media')
            ->useDisk('local');
    }

    /**
     * Définir les conversions d'images (version floutée pour le public)
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('blurred')
            ->fit(Fit::Contain, 800, 800)
            ->pixelate(50)
            ->blur(100)
            ->fit(Fit::Contain, 800, 800)
            ->nonQueued()
            ->performOnCollections('media');
    
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 300, 300)
            ->nonQueued()
            ->performOnCollections('media');
    }

    /**
     * Scope a query to only include visible posts.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope a query to order posts.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('created_at', 'desc');
    }
}

