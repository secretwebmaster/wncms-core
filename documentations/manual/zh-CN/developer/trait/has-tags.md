# HasTags Trait

## 概述

`HasTags` trait 为 WNCMS 模型提供标签功能。它整合了 `spatie/laravel-tags` 套件，允许您为任何模型新增、管理和查询标签。

## 基本用法

### 在模型中使用

`HasTags` trait 已包含在 `BaseModel` 中，因此所有继承自它的模型都自动具有标签功能：

```php
<?php

namespace App\Models;

use Wncms\Models\BaseModel;

class Article extends BaseModel
{
    // HasTags 已透过 BaseModel 提供
}
```

如果您的模型不继承 `BaseModel`，您可以直接使用该 trait：

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

### 新增标签

#### attachTag()

为模型新增单个或多个标签：

```php
// 新增单个标签
$post->attachTag('Laravel');

// 新增多个标签
$post->attachTag(['PHP', 'Web Development', 'Tutorial']);

// 使用标签类型新增标签
$post->attachTag('Featured', 'status');

// 使用标签 ID 新增
$post->attachTag(1);
```

#### syncTags()

同步标签（移除所有现有标签并新增新的）：

```php
// 用新标签取代所有标签
$post->syncTags(['Laravel', 'PHP', 'Web Development']);

// 依类型同步标签
$post->syncTags(['Featured', 'Popular'], 'status');
```

### 移除标签

#### detachTag()

从模型中移除标签：

```php
// 移除单个标签
$post->detachTag('Laravel');

// 移除多个标签
$post->detachTag(['PHP', 'Tutorial']);

// 移除特定类型的标签
$post->detachTag('Featured', 'status');
```

#### detachTags()

移除所有标签或特定类型的所有标签：

```php
// 移除所有标签
$post->detachTags();

// 移除特定类型的所有标签
$post->detachTags('status');
```

### 查询标签

#### tags

存取模型的标签关联：

```php
// 取得所有标签
$tags = $post->tags;

// 取得特定类型的标签
$statusTags = $post->tags()->where('type', 'status')->get();

// 计算标签数量
$tagCount = $post->tags()->count();
```

#### tagsWithType()

取得特定类型的标签：

```php
// 取得所有状态标签
$statusTags = $post->tagsWithType('status');

// 取得所有类别标签
$categoryTags = $post->tagsWithType('category');
```

## 查询作用域

### withAnyTags()

查询具有任何指定标签的模型：

```php
// 取得包含任何指定标签的文章
$posts = Post::withAnyTags(['Laravel', 'PHP'])->get();

// 使用标签类型
$posts = Post::withAnyTags(['Featured', 'Popular'], 'status')->get();
```

### withAllTags()

查询具有所有指定标签的模型：

```php
// 取得同时包含所有指定标签的文章
$posts = Post::withAllTags(['Laravel', 'PHP', 'Tutorial'])->get();

// 使用标签类型
$posts = Post::withAllTags(['Featured', 'Verified'], 'status')->get();
```

### withAnyTagsOfAnyType()

查询具有任何类型任何标签的模型：

```php
$posts = Post::withAnyTagsOfAnyType(['Laravel', 'Featured'])->get();
```

### withAllTagsOfAnyType()

查询具有所有指定标签（跨类型）的模型：

```php
$posts = Post::withAllTagsOfAnyType(['Laravel', 'Featured'])->get();
```

## 标签模型

### 建立标签

使用 `Tag` 模型建立和管理标签：

```php
use Spatie\Tags\Tag;

// 建立新标签
$tag = Tag::create([
    'name' => 'Laravel',
    'slug' => 'laravel',
]);

// 使用类型建立标签
$tag = Tag::create([
    'name' => 'Featured',
    'slug' => 'featured',
    'type' => 'status',
]);

// 寻找或建立标签
$tag = Tag::findOrCreate('Laravel');
$tag = Tag::findOrCreate('Featured', 'status');
```

### 标签结构

标签表包含以下栏位：

```php
Schema::create('tags', function (Blueprint $table) {
    $table->id();
    $table->json('name');           // 多语系名称
    $table->json('slug');           // 多语系别名
    $table->string('type')->nullable(); // 标签类型（可选）
    $table->integer('order_column')->nullable();
    $table->timestamps();
});
```

## 标签类型

标签可以组织成类型以进行更好的分类。常见类型包括：

```php
// 在 config/wncms.php 中定义标签类型
return [
    'tag_types' => [
        'default' => [
            'name' => '预设',
            'icon' => 'fa-tag',
        ],
        'category' => [
            'name' => '类别',
            'icon' => 'fa-folder',
        ],
        'status' => [
            'name' => '状态',
            'icon' => 'fa-circle',
        ],
        'keyword' => [
            'name' => '关键字',
            'icon' => 'fa-key',
        ],
    ],
];
```

更多关于标签类型的资讯，请参阅 [定义标签类型](../model/define-tag-types.md)。

## Manager 整合

### 在 Manager 中使用标签筛选

WNCMS Manager 类别提供了标签筛选功能：

```php
<?php

namespace App\Services\Managers;

use Wncms\Services\Managers\BaseManager;

class PostManager extends BaseManager
{
    public function index(array $params = [])
    {
        $query = $this->model->query();

        // 套用标签筛选
        $this->applyTagFilter($query, $params);

        return $query->paginate($params['per_page'] ?? 20);
    }
}
```

`applyTagFilter` 方法支援多种筛选选项：

```php
// 透过标签 ID 筛选
$params = [
    'tag_ids' => [1, 2, 3],
];

// 透过标签名称筛选
$params = [
    'tags' => ['Laravel', 'PHP'],
];

// 透过标签类型筛选
$params = [
    'tag_type' => 'category',
];

// 组合筛选
$params = [
    'tag_ids' => [1, 2],
    'tag_type' => 'status',
    'tag_match' => 'all', // 'any' 或 'all'
];
```

## 最佳实践

### 1. 使用标签类型进行组织

将标签组织成类型以便更好地管理：

```php
// 不好：所有标签混在一起
$post->attachTag(['Laravel', 'Featured', 'Technology']);

// 好：使用类型分离关注点
$post->attachTag(['Laravel', 'PHP'], 'keyword');
$post->attachTag('Featured', 'status');
$post->attachTag('Technology', 'category');
```

### 2. 标签建立前进行标准化

在附加标签前标准化标签名称：

```php
// 标准化标签名称
$tagName = trim(strtolower($request->tag));
$post->attachTag($tagName);
```

### 3. 使用同步进行批次更新

更新多个标签时使用 `syncTags()`：

```php
// 不好：逐一移除和新增
$post->detachTags();
foreach ($tags as $tag) {
    $post->attachTag($tag);
}

// 好：使用 sync
$post->syncTags($tags);
```

### 4. 载入标签关联

查询多个模型时预载入标签：

```php
// 避免 N+1 查询
$posts = Post::with('tags')->get();
```

## 按模型字段进行关键字绑定

`TagManager::getTagsToBind()` 现在支援按字段匹配，用于自动生成标签场景。

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

在后台关键字绑定页（`tags.keywords.index`）中，可为每组关键字选择 `field`（例如 `title`、`content`）。  
当 `field=*` 时，会对所有传入字段进行匹配。

## 进阶用法

### 自订标签关联

您可以自订标签关联以新增额外功能：

```php
class Post extends BaseModel
{
    // 覆写标签关联
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

### 标签统计

取得标签使用统计：

```php
// 取得每个标签的文章数量
$tagStats = Tag::withCount('posts')->get();

// 取得最受欢迎的标签
$popularTags = Tag::has('posts', '>=', 10)
    ->withCount('posts')
    ->orderByDesc('posts_count')
    ->take(10)
    ->get();
```

## 疑难排解

### 标签未附加

确保您的模型使用了 `HasTags` trait：

```php
class YourModel extends Model
{
    use HasTags; // 确保包含此 trait
}
```

### 重复标签

使用 `findOrCreate` 来避免重复标签：

```php
$tag = Tag::findOrCreate('Laravel', 'keyword');
$post->attachTag($tag);
```

### 标签查询无效

使用正确的作用域进行标签查询：

```php
// 正确
Post::withAnyTags(['Laravel', 'PHP'])->get();

// 错误
Post::where('tags', 'like', '%Laravel%')->get(); // 无效
```

## 另请参阅

- [定义标签类型](../model/define-tag-types.md)
- [Base Model](../model/base-model.md)
- [Link Manager](../manager/link-manager.md)
