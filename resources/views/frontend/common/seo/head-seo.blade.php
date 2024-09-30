<meta property="og:locale" content="{{ $wncms->getLocale() }}" />
<meta property="og:site_name" content="{{ $website->site_name }}" />
<meta property="og:url" content="{{ request()->fullUrl() }}" />
<meta property="og:type" content="{{ $seoType ?? 'article' }}" />
<meta property="og:title" content="{{ $seoTitle ?? $pageTitle ?? $website?->site_name ?? '' }}" />
<meta property="og:description" content="{{ $seoDescription ?? $website?->site_seo_description ?? ''  }}" />
<meta property="og:image" content="{{ $seoImage ?? '' }}" />
<meta property="og:image:width" content="{{ $seoImageWidth ?? '800' }}" />
<meta property="og:image:height" content="{{ $seoImageWidth ?? '450' }}" />
<meta property="og:image:type" content="{{ !empty($seoImage) ? getSeoImageType($seoImage) : 'image/jpeg' }}">
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $seoTitle ?? $pageTitle ?? $website?->site_name ?? ''}}" />
<meta name="twitter:description" content="{{ $seoDescription ?? $website?->site_seo_description ?? ''  }}" />
<meta name="twitter:image" content="{{ $seoImage ?? '' }}" />

{{-- For Menu: Push to head where menu locates --}}
{{-- <script type="application/ld+json">
    [
        {
            "@context": "http://schema.org",
            "@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": "Books",
                    "item": "https://example.com/books"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "Science Fiction",
                    "item": "https://example.com/books/sciencefiction"
                },
                {
                    "@type": "ListItem",
                    "position": 3,
                    "name": "Award Winners"
                }
            ]
        },
        {
            "@context": "http://schema.org",
            "@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": "Books",
                    "item": "https://example.com/books"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "Science Fiction",
                    "item": "https://example.com/books/sciencefiction"
                },
                {
                    "@type": "ListItem",
                    "position": 3,
                    "name": "Award Winners"
                }
            ]
        }
    ]
</script> --}}

{{-- For Content --}}
{{-- <script type="application/ld+json">
    {
    "@context": "https://schema.org",
    "@type": "FinancialService",
    "priceRange": "$$$",
    "name": "袋鼠金融",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "中正區羅斯福路二段102-1號23樓",
      "addressLocality": "台北市",
      "postalCode": "100",
      "addressCountry": "TW"
    },
    "url": "https://roo.cash/",
    "logo": "https://roo.cash/static/img/Roo_logo_v2%402x.svg",
    "image": "https://roo.cash/static/img/SEO_roo_v3.png",
    "description": "袋鼠金融為您比較各種信用卡、信用貸款等金融商品，幫你找到最適合方案 ",
    "email": "service@roo.cash"
  }
</script> --}}