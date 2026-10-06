<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Profile;
use Inertia\Inertia;
use Inertia\Response;

class ProfilePageController extends Controller
{
    /**
     * monvip.fr/ : affiche le premier profil (Angelina) pour que les liens
     * déjà en circulation continuent de fonctionner.
     */
    public function home(): Response
    {
        $profile = Profile::orderBy('id')->first();

        abort_unless($profile, 404);

        return $this->render($profile);
    }

    /**
     * monvip.fr/{slug} : landing page d'un profil précis.
     */
    public function show(string $slug): Response
    {
        $profile = Profile::where('slug', $slug)->firstOrFail();

        return $this->render($profile);
    }

    private function render(Profile $profile): Response
    {
        $mediaUrl = fn (string $collection) => $profile->getFirstMedia($collection)?->getUrl();

        $posts = Post::where('profile_id', $profile->id)
            ->visible()
            ->ordered()
            ->get()
            ->map(function (Post $post) {
                return [
                    'id' => $post->id,
                    'content' => $post->content,
                    'type' => $post->type,
                    'duration' => $post->duration,
                    'likes_count' => $post->likes_count,
                    'is_visible' => $post->is_visible,
                    'is_blurred' => $post->is_blurred,
                    'is_live' => $post->is_live,
                    'created_at' => $post->created_at,
                    'media' => $post->getMedia('media')->map(function ($media) use ($post) {
                        $routeName = $post->is_blurred ? 'media.blurred' : 'media.original';

                        return [
                            'id' => $media->id,
                            'url' => route($routeName, ['mediaId' => $media->id]),
                            'type' => $media->mime_type,
                        ];
                    })->toArray(),
                ];
            });

        return Inertia::render('Welcome', [
            'profile' => [
                'id' => $profile->id,
                'slug' => $profile->slug,
                'name' => $profile->name,
                'biography' => $profile->biography,
                'description' => $profile->description,
                'is_online' => $profile->is_online,
                'is_within_online_hours' => $profile->isWithinOnlineHours(now(), 'Europe/Paris'),
                'photos_count' => $profile->photos_count,
                'videos_count' => $profile->videos_count,
                'likes_count' => $profile->likes_count,
                'action_label' => $profile->action_label,
                'rencontre_primary_label' => $profile->rencontre_primary_label,
                'rencontre_secondary_label' => $profile->rencontre_secondary_label,
                'banner_url' => $mediaUrl('banner'),
                'avatar_url' => $mediaUrl('avatar'),
                'logo_url' => $mediaUrl('logo'),
                'certification_url' => $mediaUrl('certification'),
                'script_url' => $profile->script_url,
            ],
            'posts' => $posts,
        ]);
    }
}
