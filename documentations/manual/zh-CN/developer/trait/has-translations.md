# HasTranslations Trait

## 概述

`HasTranslations` trait 使 WNCMS 模型能够支援多语言内容。它提供了一个简单的 API 来储存、撷取和管理不同语言环境的翻译，支援 JSON 栏位储存和独立翻译表格两种储存方式。

## 基本用法

### 在模型中使用

要在模型中启用翻译，使用 `HasTranslations` trait 并定义 `$translatable` 属性：

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Wncms\Translatable\Traits\HasTranslations;

class Post extends Model
{
    use HasTranslations;

    /**
     * 可翻译的属性
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

### 资料库迁移

可翻译栏位应定义为 JSON 类型：

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

为特定语言环境设定单一属性的翻译：

```php
// 为英文设定标题
$post->setTranslation('title', 'en', 'My Article');

// 为繁体中文设定标题
$post->setTranslation('title', 'zh_TW', '我的文章');

// 链式呼叫
$post->setTranslation('title', 'en', 'My Article')
     ->setTranslation('title', 'zh_TW', '我的文章')
     ->save();
```

### getTranslation()

撷取特定语言环境的翻译：

```php
// 取得英文标题
$title = $post->getTranslation('title', 'en');

// 取得繁体中文标题
$title = $post->getTranslation('title', 'zh_TW');

// 取得当前语言环境的标题
$title = $post->getTranslation('title');

// 带回退值
$title = $post->getTranslation('title', 'fr', '未翻译');
```

### setTranslations()

一次为一个属性设定多个语言环境：

```php
$post->setTranslations('title', [
    'en' => 'My Article',
    'zh_TW' => '我的文章',
    'zh_CN' => '我的文章',
]);
```

### getTranslations()

取得属性的所有翻译：

```php
// 取得所有标题翻译
$titles = $post->getTranslations('title');
// 回传：['en' => 'My Article', 'zh_TW' => '我的文章']

// 检查可用的翻译
foreach ($titles as $locale => $title) {
    echo "$locale: $title\n";
}
```

### hasTranslation()

检查特定语言环境的翻译是否存在：

```php
// 检查是否存在英文翻译
if ($post->hasTranslation('title', 'en')) {
    // 翻译存在
}

// 检查是否存在繁体中文翻译
if ($post->hasTranslation('title', 'zh_TW')) {
    // 翻译存在
}
```

### forgetTranslation()

移除特定语言环境的翻译：

```php
// 移除英文翻译
$post->forgetTranslation('title', 'en');

// 移除繁体中文翻译
$post->forgetTranslation('title', 'zh_TW');

$post->save();
```

### forgetAllTranslations()

移除属性的所有翻译：

```php
// 移除所有标题翻译
$post->forgetAllTranslations('title');

$post->save();
```

## 属性存取器

`HasTranslations` trait 自动处理属性存取，根据当前语言环境回传翻译：

```php
// 设定应用程式语言环境
app()->setLocale('zh_TW');

// 存取可翻译属性 - 自动取得繁体中文
echo $post->title; // 输出：我的文章

// 切换语言环境
app()->setLocale('en');
echo $post->title; // 输出：My Article
```

### 明确语言环境存取

您可以使用点标记法存取特定语言环境：

```php
// 取得特定语言环境（无论当前语言环境为何）
echo $post->{'title.en'}; // My Article
echo $post->{'title.zh_TW'}; // 我的文章
```

## 实务范例

### 建立多语言文章

```php
// 建立包含多语言内容的文章
$post = Post::create([
    'title' => [
        'en' => 'Getting Started with Laravel',
        'zh_TW' => 'Laravel 入门指南',
        'zh_CN' => 'Laravel 入门指南',
    ],
    'content' => [
        'en' => 'Laravel is a web application framework...',
        'zh_TW' => 'Laravel 是一个网页应用程式框架...',
        'zh_CN' => 'Laravel 是一个网页应用程序框架...',
    ],
]);

// 或逐步建立
$post = new Post();
$post->setTranslation('title', 'en', 'Getting Started with Laravel');
$post->setTranslation('title', 'zh_TW', 'Laravel 入门指南');
$post->setTranslation('content', 'en', 'Laravel is a web application framework...');
$post->setTranslation('content', 'zh_TW', 'Laravel 是一个网页应用程式框架...');
$post->save();
```

### 在表单中更新翻译

```php
// 处理多语言表单提交
public function update(Request $request, Post $post)
{
    $validated = $request->validate([
        'title.*' => 'required|string|max:255',
        'content.*' => 'required|string',
    ]);

    // 更新翻译
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

### 使用语言环境切换显示内容

```php
// 在控制器中
public function show(Post $post, $locale = null)
{
    if ($locale) {
        app()->setLocale($locale);
    }

    return view('posts.show', compact('post'));
}

// 在视图中
<h1>{{ $post->title }}</h1>
<div class="content">
    {!! $post->content !!}
</div>

<!-- 语言切换器 -->
<div class="language-switcher">
    @foreach(['en', 'zh_TW', 'zh_CN'] as $locale)
        <a href="{{ route('posts.show', ['post' => $post, 'locale' => $locale]) }}">
            {{ $locale }}
        </a>
    @endforeach
</div>
```

## 进阶用法

### 回退机制

实作回退到预设语言环境：

```php
class Post extends Model
{
    use HasTranslations;

    protected array $translatable = ['title', 'content'];

    /**
     * 取得带回退的翻译
     */
    public function getTranslationWithFallback(string $key, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        // 尝试请求的语言环境
        if ($this->hasTranslation($key, $locale)) {
            return $this->getTranslation($key, $locale);
        }

        // 回退到英文
        if ($locale !== 'en' && $this->hasTranslation($key, 'en')) {
            return $this->getTranslation($key, 'en');
        }

        // 回退到第一个可用的翻译
        $translations = $this->getTranslations($key);
        return reset($translations) ?: '';
    }
}
```

### 批次翻译更新

一次更新多个属性的翻译：

```php
// 批次更新多个栏位
$translations = [
    'en' => [
        'title' => 'My Article',
        'excerpt' => 'This is an excerpt',
        'content' => 'Full content here...',
    ],
    'zh_TW' => [
        'title' => '我的文章',
        'excerpt' => '这是摘要',
        'content' => '完整内容在此...',
    ],
];

foreach ($translations as $locale => $fields) {
    foreach ($fields as $key => $value) {
        $post->setTranslation($key, $locale, $value);
    }
}

$post->save();
```

### 翻译验证

验证至少一种语言环境有内容：

```php
public function validateTranslations(Request $request)
{
    $request->validate([
        'title' => 'required|array',
        'title.*' => 'required|string|max:255',
        'content' => 'required|array',
        'content.*' => 'required|string',
    ]);

    // 确保至少有英文或繁体中文
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
            'title' => '标题必须至少有英文或繁体中文翻译',
        ]);
    }
}
```

## 使用独立翻译表格

对于非常大的可翻译内容，您可以使用独立表格：

### 迁移

```php
// 主要表格
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('status');
    $table->timestamps();
});

// 翻译表格
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

### 模型设定

```php
class Post extends Model
{
    use HasTranslations;

    protected array $translatable = ['title', 'excerpt', 'content'];

    /**
     * 使用独立表格进行翻译
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

## 最佳实践

### 1. 定义明确的可翻译属性

仅标记真正需要翻译的栏位：

```php
protected array $translatable = [
    'title',        // 需要翻译
    'content',      // 需要翻译
    // 不包括：'slug', 'status', 'created_at' 等
];
```

### 2. 使用语言环境常数

定义支援的语言环境：

```php
// config/wncms.php
'supported_locales' => [
    'en' => 'English',
    'zh_TW' => '繁体中文',
    'zh_CN' => '简体中文',
],
```

### 3. 验证所有语言环境

建立或更新时验证所有可用语言环境的翻译：

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

### 4. 预载入翻译

在 JSON 栏位的情况下，翻译会自动载入。对于独立表格：

```php
$posts = Post::with('translations')->get();
```

## 疑难排解

### 翻译未储存

确保属性在 `$translatable` 阵列中：

```php
protected array $translatable = [
    'title', // 新增所有可翻译栏位
];
```

### 取得原始 JSON

若要绕过翻译存取器：

```php
// 取得原始 JSON
$rawTitle = $post->getAttributes()['title'];
$allTitles = json_decode($rawTitle, true);
```

### 迁移现有资料

将非翻译栏位转换为可翻译栏位：

```php
// 迁移
Schema::table('posts', function (Blueprint $table) {
    $table->json('title_new')->nullable();
});

// 资料转换
Post::chunk(100, function ($posts) {
    foreach ($posts as $post) {
        $post->title_new = ['en' => $post->title];
        $post->save();
    }
});

// 移除旧栏位，重新命名新栏位
Schema::table('posts', function (Blueprint $table) {
    $table->dropColumn('title');
    $table->renameColumn('title_new', 'title');
});
```

## 另请参阅

- [本地化概述](../locale/localization-overview.md)
- [翻译档案](../locale/translation-files.md)
- [新增新语言](../locale/add-new-language.md)
