# HasTranslations Trait

## 概述

`HasTranslations` trait 使 WNCMS 模型能夠支援多語言內容。它提供了一個簡單的 API 來儲存、擷取和管理不同語言環境的翻譯，支援 JSON 欄位儲存和獨立翻譯表格兩種儲存方式。

## 基本用法

### 在模型中使用

要在模型中啟用翻譯，使用 `HasTranslations` trait 並定義 `$translatable` 屬性：

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Wncms\Translatable\Traits\HasTranslations;

class Post extends Model
{
    use HasTranslations;

    /**
     * 可翻譯的屬性
     */
    protected array $translatable = [
        'title',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
    ];
}
```

### 資料庫遷移

可翻譯欄位應定義為 JSON 類型：

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->json('title');
    $table->json('excerpt')->nullable();
    $table->json('content');
    $table->json('meta_title')->nullable();
    $table->json('meta_description')->nullable();
    $table->timestamps();
});
```

## 可用方法

### setTranslation()

為特定語言環境設定單一屬性的翻譯：

```php
// 為英文設定標題
$post->setTranslation('title', 'en', 'My Article');

// 為繁體中文設定標題
$post->setTranslation('title', 'zh_TW', '我的文章');

// 鏈式呼叫
$post->setTranslation('title', 'en', 'My Article')
     ->setTranslation('title', 'zh_TW', '我的文章')
     ->save();
```

### getTranslation()

擷取特定語言環境的翻譯：

```php
// 取得英文標題
$title = $post->getTranslation('title', 'en');

// 取得繁體中文標題
$title = $post->getTranslation('title', 'zh_TW');

// 取得當前語言環境的標題
$title = $post->getTranslation('title');

// 帶回退值
$title = $post->getTranslation('title', 'fr', '未翻譯');
```

### setTranslations()

一次為一個屬性設定多個語言環境：

```php
$post->setTranslations('title', [
    'en' => 'My Article',
    'zh_TW' => '我的文章',
    'zh_CN' => '我的文章',
]);
```

### getTranslations()

取得屬性的所有翻譯：

```php
// 取得所有標題翻譯
$titles = $post->getTranslations('title');
// 回傳：['en' => 'My Article', 'zh_TW' => '我的文章']

// 檢查可用的翻譯
foreach ($titles as $locale => $title) {
    echo "$locale: $title\n";
}
```

### hasTranslation()

檢查特定語言環境的翻譯是否存在：

```php
// 檢查是否存在英文翻譯
if ($post->hasTranslation('title', 'en')) {
    // 翻譯存在
}

// 檢查是否存在繁體中文翻譯
if ($post->hasTranslation('title', 'zh_TW')) {
    // 翻譯存在
}
```

### forgetTranslation()

移除特定語言環境的翻譯：

```php
// 移除英文翻譯
$post->forgetTranslation('title', 'en');

// 移除繁體中文翻譯
$post->forgetTranslation('title', 'zh_TW');

$post->save();
```

### forgetAllTranslations()

移除屬性的所有翻譯：

```php
// 移除所有標題翻譯
$post->forgetAllTranslations('title');

$post->save();
```

## 屬性存取器

`HasTranslations` trait 自動處理屬性存取，根據當前語言環境回傳翻譯：

```php
// 設定應用程式語言環境
app()->setLocale('zh_TW');

// 存取可翻譯屬性 - 自動取得繁體中文
echo $post->title; // 輸出：我的文章

// 切換語言環境
app()->setLocale('en');
echo $post->title; // 輸出：My Article
```

### 明確語言環境存取

您可以使用點標記法存取特定語言環境：

```php
// 取得特定語言環境（無論當前語言環境為何）
echo $post->{'title.en'}; // My Article
echo $post->{'title.zh_TW'}; // 我的文章
```

## 實務範例

### 建立多語言文章

```php
// 建立包含多語言內容的文章
$post = Post::create([
    'title' => [
        'en' => 'Getting Started with Laravel',
        'zh_TW' => 'Laravel 入門指南',
        'zh_CN' => 'Laravel 入门指南',
    ],
    'content' => [
        'en' => 'Laravel is a web application framework...',
        'zh_TW' => 'Laravel 是一個網頁應用程式框架...',
        'zh_CN' => 'Laravel 是一个网页应用程序框架...',
    ],
]);

// 或逐步建立
$post = new Post();
$post->setTranslation('title', 'en', 'Getting Started with Laravel');
$post->setTranslation('title', 'zh_TW', 'Laravel 入門指南');
$post->setTranslation('content', 'en', 'Laravel is a web application framework...');
$post->setTranslation('content', 'zh_TW', 'Laravel 是一個網頁應用程式框架...');
$post->save();
```

### 在表單中更新翻譯

```php
// 處理多語言表單提交
public function update(Request $request, Post $post)
{
    $validated = $request->validate([
        'title.*' => 'required|string|max:255',
        'content.*' => 'required|string',
    ]);

    // 更新翻譯
    foreach ($validated['title'] as $locale => $title) {
        $post->setTranslation('title', $locale, $title);
    }

    foreach ($validated['content'] as $locale => $content) {
        $post->setTranslation('content', $locale, $content);
    }

    $post->save();

    return redirect()->back()->with('success', '文章已更新');
}
```

### 使用語言環境切換顯示內容

```php
// 在控制器中
public function show(Post $post, $locale = null)
{
    if ($locale) {
        app()->setLocale($locale);
    }

    return view('posts.show', compact('post'));
}

// 在視圖中
<h1>{{ $post->title }}</h1>
<div class="content">
    {!! $post->content !!}
</div>

<!-- 語言切換器 -->
<div class="language-switcher">
    @foreach(['en', 'zh_TW', 'zh_CN'] as $locale)
        <a href="{{ route('posts.show', ['post' => $post, 'locale' => $locale]) }}">
            {{ $locale }}
        </a>
    @endforeach
</div>
```

## 進階用法

### 回退機制

實作回退到預設語言環境：

```php
class Post extends Model
{
    use HasTranslations;

    protected array $translatable = ['title', 'content'];

    /**
     * 取得帶回退的翻譯
     */
    public function getTranslationWithFallback(string $key, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        // 嘗試請求的語言環境
        if ($this->hasTranslation($key, $locale)) {
            return $this->getTranslation($key, $locale);
        }

        // 回退到英文
        if ($locale !== 'en' && $this->hasTranslation($key, 'en')) {
            return $this->getTranslation($key, 'en');
        }

        // 回退到第一個可用的翻譯
        $translations = $this->getTranslations($key);
        return reset($translations) ?: '';
    }
}
```

### 批次翻譯更新

一次更新多個屬性的翻譯：

```php
// 批次更新多個欄位
$translations = [
    'en' => [
        'title' => 'My Article',
        'excerpt' => 'This is an excerpt',
        'content' => 'Full content here...',
    ],
    'zh_TW' => [
        'title' => '我的文章',
        'excerpt' => '這是摘要',
        'content' => '完整內容在此...',
    ],
];

foreach ($translations as $locale => $fields) {
    foreach ($fields as $key => $value) {
        $post->setTranslation($key, $locale, $value);
    }
}

$post->save();
```

### 翻譯驗證

驗證至少一種語言環境有內容：

```php
public function validateTranslations(Request $request)
{
    $request->validate([
        'title' => 'required|array',
        'title.*' => 'required|string|max:255',
        'content' => 'required|array',
        'content.*' => 'required|string',
    ]);

    // 確保至少有英文或繁體中文
    $requiredLocales = ['en', 'zh_TW'];
    $hasRequired = false;

    foreach ($requiredLocales as $locale) {
        if (!empty($request->input("title.$locale"))) {
            $hasRequired = true;
            break;
        }
    }

    if (!$hasRequired) {
        throw ValidationException::withMessages([
            'title' => '標題必須至少有英文或繁體中文翻譯',
        ]);
    }
}
```

## 使用獨立翻譯表格

對於非常大的可翻譯內容，您可以使用獨立表格：

### 遷移

```php
// 主要表格
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('status');
    $table->timestamps();
});

// 翻譯表格
Schema::create('post_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained()->onDelete('cascade');
    $table->string('locale', 10);
    $table->string('title');
    $table->text('excerpt')->nullable();
    $table->longText('content');

    $table->unique(['post_id', 'locale']);
});
```

### 模型設定

```php
class Post extends Model
{
    use HasTranslations;

    protected array $translatable = ['title', 'excerpt', 'content'];

    /**
     * 使用獨立表格進行翻譯
     */
    public function translations()
    {
        return $this->hasMany(PostTranslation::class);
    }

    public function translation(?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return $this->translations()->where('locale', $locale)->first();
    }
}

class PostTranslation extends Model
{
    protected $fillable = ['post_id', 'locale', 'title', 'excerpt', 'content'];
    public $timestamps = false;
}
```

## 最佳實踐

### 1. 定義明確的可翻譯屬性

僅標記真正需要翻譯的欄位：

```php
protected array $translatable = [
    'title',        // 需要翻譯
    'content',      // 需要翻譯
    // 不包括：'slug', 'status', 'created_at' 等
];
```

### 2. 使用語言環境常數

定義支援的語言環境：

```php
// config/wncms.php
'supported_locales' => [
    'en' => 'English',
    'zh_TW' => '繁體中文',
    'zh_CN' => '简体中文',
],
```

### 3. 驗證所有語言環境

建立或更新時驗證所有可用語言環境的翻譯：

```php
public function rules()
{
    $rules = [];
    foreach (config('wncms.supported_locales') as $locale => $name) {
        $rules["title.$locale"] = 'required|string|max:255';
        $rules["content.$locale"] = 'required|string';
    }
    return $rules;
}
```

### 4. 預載入翻譯

在 JSON 欄位的情況下，翻譯會自動載入。對於獨立表格：

```php
$posts = Post::with('translations')->get();
```

## 疑難排解

### 翻譯未儲存

確保屬性在 `$translatable` 陣列中：

```php
protected array $translatable = [
    'title', // 新增所有可翻譯欄位
];
```

### 取得原始 JSON

若要繞過翻譯存取器：

```php
// 取得原始 JSON
$rawTitle = $post->getAttributes()['title'];
$allTitles = json_decode($rawTitle, true);
```

### 遷移現有資料

將非翻譯欄位轉換為可翻譯欄位：

```php
// 遷移
Schema::table('posts', function (Blueprint $table) {
    $table->json('title_new')->nullable();
});

// 資料轉換
Post::chunk(100, function ($posts) {
    foreach ($posts as $post) {
        $post->title_new = ['en' => $post->title];
        $post->save();
    }
});

// 移除舊欄位，重新命名新欄位
Schema::table('posts', function (Blueprint $table) {
    $table->dropColumn('title');
    $table->renameColumn('title_new', 'title');
});
```

## 另請參閱

- [本地化概述](../locale/localization-overview.md)
- [翻譯檔案](../locale/translation-files.md)
- [新增新語言](../locale/add-new-language.md)
