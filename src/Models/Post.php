<?php

namespace Wncms\Models;

use Wncms\Services\Models\WncmsModel;
use Wncms\Traits\WnModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wncms\Tags\HasTags;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Str;
use Wncms\Translatable\Traits\HasTranslations;

class Post extends WncmsModel implements HasMedia
{
    use HasFactory;
    use HasTags;
    use InteractsWithMedia;
    use SoftDeletes;
    use HasTranslations;
    use WnModelTrait;

    protected $guarded = [];

    protected $casts = [
        'published_at'=>'datetime',
        'expired_at'=>'datetime'
    ];

    protected $removeViewsOnDelete = true;

    protected $translatable = ['title','excerpt','keywords','content','label'];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-pencil'
    ];

    public const IGNORED_LOCALIZED_PATH_KEYWORDS = [
        'storage',
    ];

    public const ROUTES = [
        'index',
        'create',
    ];
    
    public const ORDERS = [
        'order',
        'view_today',
        'view_yesterday',
        'view_week',
        'view_month',
        'view_total',
        'published_at',
        'expired_at',
        'created_at',
        'updated_at',
    ];
    
    public const STATUSES = [
        'published',
        'drafted',
        'trashed',
    ];
    
    public const VISIBILITIES = [
        'public',
        'member',
        'admin',
    ];
    
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('post_thumbnail')->singleFile();
        $this->addMediaCollection('post_content');
    }


    //! Relationship
    public function websites()
    {
        return $this->belongsToMany(Website::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * ! Attributes Accessor
     * ----------------------------------------------------------------------------------------------------
     */
    public function getThumbnailAttribute()
    {
        $media = $this->getMedia('post_thumbnail')->first();
        if ($media) return $media->getUrl();
        return $this->external_thumbnail;
    }

    public function getIntroAttribute()
    {
        if(gto('post_excerpt_length') === 0 || gto('post_excerpt_length') === "0"){
            return "";
        }elseif(is_numeric(gto('post_excerpt_length'))){
            $limit = gto('post_excerpt_length');
        }else{
            $limit = 200;
        }

        return !empty($this->excerpt) ? str()->limit($this->excerpt, $limit, '..') : str()->limit(str_replace("&nbsp;", '', strip_tags($this->content)), $limit, '..') ;
    }

    //! tag function. Will soon be deprecated
    public function getPostCategoriesAttribute()
    {
        return $this->tags->where('type','post_category');
    }

    public function getPostCategoryArrayAttribute()
    {
        return $this->tags->where('type','post_category')->pluck('name', 'id')->toArray();
    }

    public function getPostTagsAttribute()
    {
        return $this->tags->where('type','post_tag');
    }

    public function getSingleUrlAttribute()
    {
        return route('frontend.posts.single', $this->slug);
    }

    public function getFakeViewTotalAttribute()
    {
        if(empty(gto('fake_views'))) return $this->view_total ?? 0;

        // Define some variables to make the formula more complex
        $fake_view_factor_view_total = gto('fake_view_factor_view_total', 37);
        $fake_view_factor_id = gto('fake_view_factor_id', 77);
        $fake_view_factor_title  = gto('fake_view_factor_title', 1107);
        
        // Apply a custom mathematical formula to the original view_total
        $fakeViewTotal = ($fake_view_factor_view_total * $this->view_total) + ($fake_view_factor_id * $this->id) + ($fake_view_factor_title  * strlen($this->title));
        
        // Ensure the generated value is always positive or zero
        $fakeViewTotal = ceil(max(0, $fakeViewTotal));
        
        return $fakeViewTotal;
    }

    public function getTags($type = 'post_tag')
    {
        return $this->tags->where('type', $type);
    }

    public function getFirstTag($type = 'post_tag')
    {
        return $this->tags->where('type', $type)->first();
    }

    public function getFirstTagName($type = 'post_tag')
    {
        return $this->tags->where('type', $type)->first()?->name;
    }

    //% Depracated soon
    public function getFirstCategory()
    {
        return $this->getFirstTag('post_category');
        // return $this->tags->where('type', 'post_category')->first();
    }

    //TODO:: Eagar loading
    public function getPrevious()
    {
        $website = wncms()->website()->getCurrent();
        if(!$website) return;
        return $this->where('id', '<', $this->id)->whereRelation('websites', 'websites.id', $website->id )->orderBy('id','desc')->first();
    }
 
    public function getNext()
    {
        $website = wncms()->website()->getCurrent();
        if(!$website) return;
        return $this->where('id', '>', $this->id)->whereRelation('websites', 'websites.id', $website->id )->orderBy('id','asc')->first();
    }

    public function getRelated(
        $tagType = 'post_category',
        $count = 0,
        $pageSize = 0,
        $order = 'id',
        $sequence = 'desc',
        $status = 'published',
    )
    {
        return wncms()->post()->getRelated(
            post:$this,
            tagType: $tagType,
            count: $count,
            pageSize: $pageSize,
            order: $order,
            sequence: $sequence,
            status: $status,
        );
    }
    
    
    //Base
    public function getTagAttributeArray($type = 'post_category', $attribute = "name")
    {
        $shouldAuth = false;
        $cacheKey = wncms()->cache()->createKey("wncms_post", __FUNCTION__, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $this->id);
        $cacheTags = ['posts','tags'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);
        
        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use($type, $attribute){
            return $this->tagsWithType($type)->pluck($attribute)->toArray();
        });
    }


    //Using Base
    public function getTagNameArray($type = 'post_category')
    {
        return $this->getTagAttributeArray($type, 'name');
    }

    public function getCategoryIds()
    {
        return $this->getTagAttributeArray('post_category', 'id');
    }

    public function getTagIds()
    {
        return $this->getTagAttributeArray('post_tags', 'id');
    }

    public function getTagNameString($type = 'post_category', string $separator = ",")
    {
        return implode($separator, $this->getTagNameArray($type));
    }

    public function getTagNameWithWrapper($type = 'post_category', $prefix = "", $suffix = "")
    {
        $html = "";
        foreach($this->getTagNameArray($type) as $tagName){
            $html .= $prefix . $tagName . $suffix;
        }
        return $html;
    }

    public function getTagNameWitHtmlTag($type = 'post_category', $htmlTag = "span", $class = "", $id = "")
    {
        $html = "";
        foreach($this->getTagNameArray($type) as $tagName){
            $html .= "<{$htmlTag}" . (!empty($class) ? " class=\"{$class}\"" : '') . (!empty($id) ? " id=\"{$id}\"" : '') .">" . $tagName . "</{$htmlTag}>";
        }
        return $html;
    }


    //query category trees
    public function getCategoriesWithSiblings()
    {
        $method = "getCategoriesWithSiblings";
        $shouldAuth = false;
        $cacheKey = wncms()->cache()->createKey("wncms_post", $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()));
        $cacheTags = ['posts','tags'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function (){
            // Get the categories related to the post
            $categories = $this->postCategories;

            // Initialize an empty array to store the categories with siblings
            $categoriesWithSiblings = [];

            // Iterate through each category
            foreach ($categories as $category) {
                // Get the siblings (including itself)
                $siblings = $category->siblingsAndSelf;

                // Add the category and its siblings to the result array
                $categoriesWithSiblings[] = $siblings;
            }

            // Flatten the result array to remove nested arrays
            $flattenedCategories = collect($categoriesWithSiblings)->flatten()->unique();

            return $flattenedCategories;
        });
    }


    //Handling data
    public function localizeImages()
    {
        // Get the content from the post
        $content = $this->content;

        // Use regular expressions to find image src attributes in the content
        preg_match_all('/<img[^>]+src="([^"]+)"/i', $content, $matches);

        foreach ($matches[1] as $imageUrl) {
            // dd($imageUrl);
            // Check if the image URL is already a localized path or contains ignored keywords
            if ($this->containsIgnoredKeywords($imageUrl)) {
                // Skip this image, as it's already localized or contains ignored keywords
                continue; 
            }

            // Check if the image URL exists
            if (!$this->imageUrlExists($imageUrl)) {
                // The image URL doesn't exist, so skip it
                continue;
            }
            

            // Download the image from the web (you may want to add error handling)
            $imageContents = file_get_contents($imageUrl);

            // Convert the image to .webp format
            $webpImageContents = $this->convertToWebp($imageContents);

            $fileName = Str::random(16); 
            $extension = 'webp';


            // Store the image in the media library using Spatie with the original file name
            $media = $this->addMediaFromString($webpImageContents)
                ->usingFileName("{$fileName}.{$extension}")
                ->toMediaCollection('post_content'); // You can define your own collection name

            // Remove the domain or subdomain from the URL
            $mediaUrl = $this->removeDomainFromUrl($media->getUrl());

            // Replace the original image source with the media URL
            $content = str_replace($imageUrl, $mediaUrl, $content);

            //prevent ip being blocked
            sleep(1);
        }

        // Update the post's content with localized image URLs
        $this->content = $content;
        $this->save();

        return $this;
    }

    /**
     * Check if an image URL exists by sending an HTTP HEAD request.
     *
     * @param string $imageUrl
     * @return bool
     */
    public function imageUrlExists($imageUrl)
    {
        $headers = @get_headers($imageUrl);
        return $headers && strpos($headers[0], '200 OK') !== false;
    }

    public function convertToWebp($imageContents)
    {
        // Create an image resource from the image contents
        $image = imagecreatefromstring($imageContents);

        // Create a new image in .webp format
        ob_start();
        imagewebp($image, null, 90); // 90 is the quality level (adjust as needed)
        $webpImageContents = ob_get_clean();

        // Free up memory
        imagedestroy($image);

        return $webpImageContents;
    }

    public function containsIgnoredKeywords($imageUrl)
    {
        foreach (self::IGNORED_LOCALIZED_PATH_KEYWORDS as $keyword) {
            if (strpos($imageUrl, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    public function removeDomainFromUrl($url)
    {
        $parsedUrl = parse_url($url);
        return $parsedUrl['path'] ?? $url;
    }

    public function wrapTables()
    {
        // Get the content from the post
        $content = $this->content;

        // Use regular expressions to wrap <table> elements with <div class="wn-content-table">
        $content = preg_replace('/<table(.*?)>(.*?)<\/table>/is', '<div class="wn-content-table"><table$1>$2</table></div>', $content);

        // Update the post's content with wrapped tables
        $this->content = $content;
        $this->save();

        return $this;
    }

    public function syncTagsFromRequest($data, $tagType, $autoGenerate = false, array $content = [])
    {
        $tagNames = [];
        if (!empty($data)) {
            $tags = json_decode($data);
            foreach ($tags as $tag) {
                $tagNames[] = $tag->value;
            }
        }

        if (!empty($autoGenerate)) {
            $tagIdsFromKeywordBinding = wncms()->tag()->getTagsToBind(
                tagType: $tagType,
                contents: $content,
                column: 'name'
            );
            $tagNames = array_merge($tagNames, $tagIdsFromKeywordBinding);
        }

        $tagNames = array_filter($tagNames);
        if (!empty($tagNames)) {
            $this->syncTagsWithType($tagNames, $tagType);
            wncms()->cache()->flush(['tags']);
        }

        return $this;
    }

    // public function getAttribute($key)
    // {
    //     dd("getAttribute from Post Model");
    // }
    
    //! Static
    public static function getTagClassName(): string
    {
        return Tag::class;
    }
}
