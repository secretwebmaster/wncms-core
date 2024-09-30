@push('head_seo')
    @php
        $ldJsonData = [
            "@context" => "https://schema.org",
            "@type" => $ldJsonType ?? '',
            "headline" => $ldJsonHeadline ?? '',
            "url" => $ldJsonHeadline ?? '',
            "datePublished" => $ldJsonDateModified ?? '',
            "dateModified" => $ldJsonDateModified ?? '',
            "description" => $ldJsonDescription ?? '',
            "publisher" => $ldJsonPublisher ?? '',
        ];

        if(!empty($ldJsonImage)){
            try{
                $ldJsonData['image'] = [
                    "@type" => "ImageObject",
                    "url" => $ldJsonImage,
                    "width" => !empty($ldJsonImage) ? getimagesize($ldJsonImage)[0] : '',
                    "height" => !empty($ldJsonImage) ? getimagesize($ldJsonImage)[1] : '',
                ];
            }catch(\Exception $e){
                $ldJsonData['image'] = [];
            }
        }

        if(!empty($ldJsonAuthor)){
            $ldJsonData['author'] = [
                "@type" => "Person",
                "name" => $ldJsonAuthor,
            ];
        }

        if(!empty($ldJsonContentKey)){
            $ldJsonData[$ldJsonContentKey] = [];
        }

        if(!empty($ldJsonContentType) && !empty($ldJsonContents)){

            //posts
            if($ldJsonContentType == 'posts'){
                foreach ($ldJsonContents as $post) {
                    $ldJsonData[$ldJsonContentKey][] = [
                        "@type" => "BlogPosting",
                        "headline" => $post->title,
                        "url" => $post->singleUrl,
                        "datePublished" => $post->created_at,
                        "dateModified" => $post->updated_at,
                        "mainEntityOfPage" => $post->singleUrl,
                        "author" => [
                            "@type" => "Person",
                            "name" => $post?->user?->username ?? $website->site_name,
                            'url' => route('frontend.pages.home'),
                        ],
                        "description" => $post->excerpt,
                        "image" => [
                            "@type" => "ImageObject",
                            "url" => $post->thumbnail,
                            // "width" => !empty($post->thumbnail) ? getimagesize(asset($post->thumbnail))[0] : '1200',
                            // "height" => !empty($post->thumbnail) ? getimagesize(asset($post->thumbnail))[1] : '630',
                        ],
                    ];
                }
            }

            if($ldJsonContentType == 'itemListElement'){
                if(is_array($ldJsonContents)){
                    $ldJsonData[$ldJsonContentKey] = $ldJsonContents;
                }
            }

        }

        $ldJsonData = array_filter($ldJsonData);

    @endphp
    <script type="application/ld+json">@json($ldJsonData)</script>
@endpush