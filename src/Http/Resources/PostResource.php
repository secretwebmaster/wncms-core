<?php

namespace Wncms\Http\Resources;

class PostResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'thumbnail' => $this->getFirstMediaUrl('post_thumbnail'),
            'published_at' => $this->published_at,
            'tags' => $this->tagsWithType('post_tag')->pluck('name'),
            'category' => $this->tagsWithType('post_category')->pluck('name'),
            'url' => $this->slug ? route('frontend.posts.single', ['slug' => $this->slug]) : null,
        ];
    }
}
