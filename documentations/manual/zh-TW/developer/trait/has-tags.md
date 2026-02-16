# HasTags Trait

## 概述

`HasTags` trait 為 WNCMS 模型提供標籤功能。它整合了 `spatie/laravel-tags` 套件，允許您為任何模型新增、管理和查詢標籤。

## 基本用法

### 在模型中使用

`HasTags` trait 已包含在 `BaseModel` 中，因此所有繼承自它的模型都自動具有標籤功能：

```php
<?php

namespace App\Models;

use Wncms\Models\BaseModel;

class Article extends BaseModel
{
    // HasTags 已透過 BaseModel 提供
}
```

如果您的模型不繼承 `BaseModel`，您可以直接使用該 trait：

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Wncms\Tags\HasTags;

class CustomModel extends Model
{
    use HasTags;
}
```

## 可用方法

### 新增標籤

#### attachTag()

為模型新增單個或多個標籤：

```php
// 新增單個標籤
$post->attachTag('Laravel');

// 新增多個標籤
$post->attachTag(['PHP', 'Web Development', 'Tutorial']);

// 使用標籤類型新增標籤
$post->attachTag('Featured', 'status');

// 使用標籤 ID 新增
$post->attachTag(1);
```

#### syncTags()

同步標籤（移除所有現有標籤並新增新的）：

```php
// 用新標籤取代所有標籤
$post->syncTags(['Laravel', 'PHP', 'Web Development']);

// 依類型同步標籤
$post->syncTags(['Featured', 'Popular'], 'status');
```

### 移除標籤

#### detachTag()

從模型中移除標籤：

```php
// 移除單個標籤
$post->detachTag('Laravel');

// 移除多個標籤
$post->detachTag(['PHP', 'Tutorial']);

// 移除特定類型的標籤
$post->detachTag('Featured', 'status');
```

#### detachTags()

移除所有標籤或特定類型的所有標籤：

```php
// 移除所有標籤
$post->detachTags();

// 移除特定類型的所有標籤
$post->detachTags('status');
```

### 查詢標籤

#### tags

存取模型的標籤關聯：

```php
// 取得所有標籤
$tags = $post->tags;

// 取得特定類型的標籤
$statusTags = $post->tags()->where('type', 'status')->get();

// 計算標籤數量
$tagCount = $post->tags()->count();
```

#### tagsWithType()

取得特定類型的標籤：

```php
// 取得所有狀態標籤
$statusTags = $post->tagsWithType('status');

// 取得所有類別標籤
$categoryTags = $post->tagsWithType('category');
```

## 查詢作用域

### withAnyTags()

查詢具有任何指定標籤的模型：

```php
// 取得包含任何指定標籤的文章
$posts = Post::withAnyTags(['Laravel', 'PHP'])->get();

// 使用標籤類型
$posts = Post::withAnyTags(['Featured', 'Popular'], 'status')->get();
```

### withAllTags()

查詢具有所有指定標籤的模型：

```php
// 取得同時包含所有指定標籤的文章
$posts = Post::withAllTags(['Laravel', 'PHP', 'Tutorial'])->get();

// 使用標籤類型
$posts = Post::withAllTags(['Featured', 'Verified'], 'status')->get();
```

### withAnyTagsOfAnyType()

查詢具有任何類型任何標籤的模型：

```php
$posts = Post::withAnyTagsOfAnyType(['Laravel', 'Featured'])->get();
```

### withAllTagsOfAnyType()

查詢具有所有指定標籤（跨類型）的模型：

```php
$posts = Post::withAllTagsOfAnyType(['Laravel', 'Featured'])->get();
```

## 標籤模型

### 建立標籤

使用 `Tag` 模型建立和管理標籤：

```php
use Spatie\Tags\Tag;

// 建立新標籤
$tag = Tag::create([
    'name' => 'Laravel',
    'slug' => 'laravel',
]);

// 使用類型建立標籤
$tag = Tag::create([
    'name' => 'Featured',
    'slug' => 'featured',
    'type' => 'status',
]);

// 尋找或建立標籤
$tag = Tag::findOrCreate('Laravel');
$tag = Tag::findOrCreate('Featured', 'status');
```

### 標籤結構

標籤表包含以下欄位：

```php
Schema::create('tags', function (Blueprint $table) {
    $table->id();
    $table->json('name');           // 多語系名稱
    $table->json('slug');           // 多語系別名
    $table->string('type')->nullable(); // 標籤類型（可選）
    $table->integer('order_column')->nullable();
    $table->timestamps();
});
```

## 標籤類型

標籤可以組織成類型以進行更好的分類。常見類型包括：

```php
// 在 config/wncms.php 中定義標籤類型
return [
    'tag_types' => [
        'default' => [
            'name' => '預設',
            'icon' => 'fa-tag',
        ],
        'category' => [
            'name' => '類別',
            'icon' => 'fa-folder',
        ],
        'status' => [
            'name' => '狀態',
            'icon' => 'fa-circle',
        ],
        'keyword' => [
            'name' => '關鍵字',
            'icon' => 'fa-key',
        ],
    ],
];
```

更多關於標籤類型的資訊，請參閱 [定義標籤類型](../model/define-tag-types.md)。

## Manager 整合

### 在 Manager 中使用標籤篩選

WNCMS Manager 類別提供了標籤篩選功能：

```php
<?php

namespace App\Services\Managers;

use Wncms\Services\Managers\BaseManager;

class PostManager extends BaseManager
{
    public function index(array $params = [])
    {
        $query = $this->model->query();

        // 套用標籤篩選
        $this->applyTagFilter($query, $params);

        return $query->paginate($params['per_page'] ?? 20);
    }
}
```

`applyTagFilter` 方法支援多種篩選選項：

```php
// 透過標籤 ID 篩選
$params = [
    'tag_ids' => [1, 2, 3],
];

// 透過標籤名稱篩選
$params = [
    'tags' => ['Laravel', 'PHP'],
];

// 透過標籤類型篩選
$params = [
    'tag_type' => 'category',
];

// 組合篩選
$params = [
    'tag_ids' => [1, 2],
    'tag_type' => 'status',
    'tag_match' => 'all', // 'any' 或 'all'
];
```

## 最佳實踐

### 1. 使用標籤類型進行組織

將標籤組織成類型以便更好地管理：

```php
// 不好：所有標籤混在一起
$post->attachTag(['Laravel', 'Featured', 'Technology']);

// 好：使用類型分離關注點
$post->attachTag(['Laravel', 'PHP'], 'keyword');
$post->attachTag('Featured', 'status');
$post->attachTag('Technology', 'category');
```

### 2. 標籤建立前進行標準化

在附加標籤前標準化標籤名稱：

```php
// 標準化標籤名稱
$tagName = trim(strtolower($request->tag));
$post->attachTag($tagName);
```

### 3. 使用同步進行批次更新

更新多個標籤時使用 `syncTags()`：

```php
// 不好：逐一移除和新增
$post->detachTags();
foreach ($tags as $tag) {
    $post->attachTag($tag);
}

// 好：使用 sync
$post->syncTags($tags);
```

### 4. 載入標籤關聯

查詢多個模型時預載入標籤：

```php
// 避免 N+1 查詢
$posts = Post::with('tags')->get();
```

## 依模型欄位進行關鍵字綁定

`TagManager::getTagsToBind()` 現在支援依欄位比對，適用於自動產生標籤流程。

```php
$tagNames = wncms()->tag()->getTagsToBind(
    tagType: 'post_category',
    contents: [
        'title' => $request->title,
        'content' => $request->content,
        'excerpt' => $request->excerpt,
    ],
    column: 'name',
    modelKey: 'post'
);
```

在後台關鍵字綁定頁（`tags.keywords.index`）中，可為每組關鍵字選擇 `field`（例如 `title`、`content`）。  
當 `field=*` 時，會對所有傳入欄位進行比對。

## 進階用法

### 自訂標籤關聯

您可以自訂標籤關聯以新增額外功能：

```php
class Post extends BaseModel
{
    // 覆寫標籤關聯
    public function tags()
    {
        return $this->morphToMany(
            Tag::class,
            'taggable',
            'taggables',
            'taggable_id',
            'tag_id'
        )->orderBy('order_column');
    }
}
```

### 標籤統計

取得標籤使用統計：

```php
// 取得每個標籤的文章數量
$tagStats = Tag::withCount('posts')->get();

// 取得最受歡迎的標籤
$popularTags = Tag::has('posts', '>=', 10)
    ->withCount('posts')
    ->orderByDesc('posts_count')
    ->take(10)
    ->get();
```

## 疑難排解

### 標籤未附加

確保您的模型使用了 `HasTags` trait：

```php
class YourModel extends Model
{
    use HasTags; // 確保包含此 trait
}
```

### 重複標籤

使用 `findOrCreate` 來避免重複標籤：

```php
$tag = Tag::findOrCreate('Laravel', 'keyword');
$post->attachTag($tag);
```

### 標籤查詢無效

使用正確的作用域進行標籤查詢：

```php
// 正確
Post::withAnyTags(['Laravel', 'PHP'])->get();

// 錯誤
Post::where('tags', 'like', '%Laravel%')->get(); // 無效
```

## 另請參閱

- [定義標籤類型](../model/define-tag-types.md)
- [Base Model](../model/base-model.md)
- [Link Manager](../manager/link-manager.md)
