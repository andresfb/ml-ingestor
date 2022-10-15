<?php

namespace App\ViewModels;

use App\Models\Media;
use App\Models\Post;
use App\Services\AvatarGeneratorService;
use Exception;
use GrahamCampbell\Markdown\Facades\Markdown;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Imagick;
use Spatie\Tags\Tag;
use Spatie\ViewModels\ViewModel;
use Illuminate\Support\Collection as SimpleCollection;

class PostListViewModel extends ViewModel
{
    private LengthAwarePaginator $postList;

    private AvatarGeneratorService $service;

    public function __construct(LengthAwarePaginator $postList)
    {
        $this->postList = $postList;
        $this->service = resolve(AvatarGeneratorService::class);
    }

    /**
     * posts Method.
     *
     * @return SimpleCollection
     */
    public function posts(): SimpleCollection
    {
        return $this->postList->map(function (Post $post) {

            if (empty($post->item->media)) {
                return [];
            }

            $media = $post->item->media->where('collection_name', $post->type)->first();
            if (empty($media)) {
                return [];
            }

            $measurement = "KB";
            $fileSize = ceil($media->size / 1024);
            if ($fileSize > 999) {
                $fileSize = ceil($fileSize / 1024);
                $measurement = "MB";
            }

            $avatar = $this->service->getAvatar();
            $extra = [
                'Original Location' => sprintf("%s%s/", config('raw-files.path'), $post->item->og_path),
                'Original File' => $post->item->og_file,
                'File Size' => number_format($fileSize) . " $measurement",
                'Imported On' => $post->item->created_at->toDateTimeString()
            ];

            if (!empty($post->item->exif)) {
                $extra = array_merge($extra, $post->item->exif);
            }

            return [
                'name' => $avatar['name'],
                'avatar' => $avatar['image'],
                'id' => $post->id,
                'media' => $this->generateLink($media),
                'type' => $post->type,
                'slug' => $post->slug,
                'title' => $post->title,
                'source' => $post->source,
                'content' => Markdown::convert($post->content)->getContent(),
                'date' => $post->created_at->longAbsoluteDiffForHumans(),
                'extra_info' => $extra,
                'tags' => $post->tags->map(function (Tag $tag) {
                    return [
                        'id' => $tag->id,
                        'tag' => $tag->name,
                        'slug' => $tag->slug
                    ];
                }),
            ];
        })->collect();
    }

    /**
     * generateLink Method.
     *
     * @param Media $media
     * @return string
     */
    private function generateLink(Media $media): string
    {
        return URL::temporarySignedRoute(
            'preview', // route name
            now()->addMinutes(45), // TTL
            ['media' => $media->id] // object id
        );
    }
}
