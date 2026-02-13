# HasMultisite Trait

## 概述

`HasMultisite` trait 使 WNCMS 模型能够与多个网站关联。它透过可配置的模式提供灵活的多网站支援，允许模型属於单个网站、多个网站或全域适用于所有网站。

## 网站模式

每个使用 `HasMultisite` 的模型可以在三种模式下运作：

### 1. Global 模式

模型在所有网站上都可用，不需要明确关联。

```php
// config/wncms.php
'model_website_modes' => [
    'posts' => 'global',
],
```

### 2. Single 模式

模型只能属于一个网站。

```php
// config/wncms.php
'model_website_modes' => [
    'pages' => 'single',
],
```

### 3. Multi 模式

模型可以关联到多个网站。

```php
// config/wncms.php
'model_website_modes' => [
    'products' => 'multi',
],
```

## 基本用法

### 在模型中使用

`HasMultisite` trait 已包含在 `BaseModel` 中：

```php
<?php

namespace App\Models;

use Wncms\Models\BaseModel;

class Article extends BaseModel
{
    // HasMultisite 已透过 BaseModel 提供
}
```

如果您的模型不继承 `BaseModel`：

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Wncms\Traits\HasMultisite;

class CustomModel extends Model
{
    use HasMultisite;
}
```

### 配置网站模式

在 `config/wncms.php` 中为您的模型定义网站模式：

```php
return [
    'model_website_modes' => [
        'posts' => 'multi',      // 文章可以属于多个网站
        'pages' => 'single',     // 页面只能属于一个网站
        'tags' => 'global',      // 标签在所有网站上共享
    ],
];
```

旧版本也可能使用平铺设定：

```php
'model_website_modes' => [
    'post' => 'multi',
    'page' => 'single',
],
```

WNCMS 会合并两种设定来源，并在最后套用资料库中的系统设定（`model_website_modes`）。优先级如下：

1. 资料库系统设定 `model_website_modes`
2. `config('wncms.models.*.website_mode')`
3. `config('wncms.model_website_modes')`（旧版）

## 可用方法

如果模型继承 `Wncms\Models\BaseModel`，则已默认包含 `HasMultisite`，不需要在子模型再次 `use HasMultisite;`。

### getWebsiteMode()

取得模型的网站模式：

```php
$mode = $post->getWebsiteMode();
// 回传：'global'、'single' 或 'multi'

// 检查特定模式
if ($post->getWebsiteMode() === 'multi') {
    // 处理多网站模型
}
```

### websites()

存取模型的网站关联（适用于 single 和 multi 模式）：

```php
// 取得所有关联的网站
$websites = $post->websites;

// 使用关联方法
$websiteCount = $post->websites()->count();

// 检查模型是否属于网站
$belongsToWebsite = $post->websites()
    ->where('id', $websiteId)
    ->exists();
```

### bindWebsites()

将模型关联到一个或多个网站：

```php
// 关联到单个网站
$post->bindWebsites(1);

// 关联到多个网站
$post->bindWebsites([1, 2, 3]);

// 使用网站模型
$post->bindWebsites($website);
$post->bindWebsites([$website1, $website2]);
```

### unbindWebsites()

从网站中解除模型关联：

```php
// 从所有网站解除关联
$post->unbindWebsites();

// 从特定网站解除关联
$post->unbindWebsites(1);
$post->unbindWebsites([1, 2, 3]);

// 使用网站模型
$post->unbindWebsites($website);
```

### syncWebsites()

同步模型的网站（移除现有并新增新的）：

```php
// 同步到特定网站
$post->syncWebsites([1, 2, 3]);

// 清除所有网站
$post->syncWebsites([]);
```

## 查询作用域

### forWebsite()

查询属于特定网站的模型：

```php
// 为特定网站取得文章
$posts = Post::forWebsite(1)->get();

// 使用网站模型
$posts = Post::forWebsite($website)->get();

// 与其他作用域组合
$posts = Post::forWebsite($currentWebsite)
    ->where('status', 'published')
    ->latest()
    ->get();
```

### forCurrentWebsite()

为当前活动网站查询模型：

```php
// 自动使用当前网站
$posts = Post::forCurrentWebsite()->get();
```

## 资料库结构

多网站关联储存在 `model_has_websites` 表中：

```php
Schema::create('model_has_websites', function (Blueprint $table) {
    $table->unsignedBigInteger('website_id');
    $table->morphs('model'); // model_type + model_id

    $table->unique(['website_id', 'model_id', 'model_type']);

    $table->foreign('website_id')
        ->references('id')
        ->on('websites')
        ->onDelete('cascade');
});
```

## Manager 整合

### 在 Manager 中套用网站筛选

WNCMS Manager 类别自动处理网站筛选：

```php
<?php

namespace App\Services\Managers;

use Wncms\Services\Managers\BaseManager;

class PostManager extends BaseManager
{
    public function index(array $params = [])
    {
        $query = $this->model->query();

        // 自动套用网站范围
        $this->applyWebsiteScope($query, $params);

        return $query->paginate($params['per_page'] ?? 20);
    }
}
```

手动套用网站筛选：

```php
// 透过网站 ID 筛选
$params = [
    'website_id' => 1,
];

// 为当前网站筛选
$params = [
    'website_id' => wncms()->website()->id,
];

// 全域模型忽略网站筛选
$params = [
    'ignore_website' => true,
];
```

## 实务范例

### 建立多网站文章

```php
// 建立文章
$post = Post::create([
    'title' => 'My Article',
    'content' => 'Article content...',
]);

// 关联到网站
$post->bindWebsites([1, 2, 3]);

// 或在建立时
$post = Post::create([
    'title' => 'My Article',
    'content' => 'Article content...',
]);
$post->bindWebsites(wncms()->website()->id);
```

### 为网站查询内容

```php
// 取得当前网站的所有文章
$posts = Post::forCurrentWebsite()
    ->where('status', 'published')
    ->get();

// 取得特定网站的文章
$websiteId = 1;
$posts = Post::forWebsite($websiteId)
    ->latest()
    ->paginate(20);
```

### 切换网站关联

```php
// 将文章从一个网站移到另一个
$post->unbindWebsites($oldWebsiteId);
$post->bindWebsites($newWebsiteId);

// 或使用 sync
$post->syncWebsites([$newWebsiteId]);
```

### 跨网站内容

```php
// 寻找在多个网站上的文章
$posts = Post::whereHas('websites', function($query) {
    $query->whereIn('website_id', [1, 2, 3]);
})->get();

// 寻找恰好在 2 个网站上的文章
$posts = Post::has('websites', '=', 2)->get();
```

## 最佳实践

### 1. 设定适当的网站模式

为每种内容类型选择正确的模式：

```php
// config/wncms.php
'model_website_modes' => [
    // 特定于每个网站的内容
    'posts' => 'multi',
    'pages' => 'single',

    // 跨网站共享的内容
    'tags' => 'global',
    'categories' => 'global',
    'users' => 'global',
],
```

### 2. 建立时关联网站

建立内容时始终关联到网站：

```php
$post = Post::create($data);
$post->bindWebsites(wncms()->website()->id);
```

### 3. 使用作用域进行查询

始终使用 `forWebsite()` 作用域进行网站特定查询：

```php
// 好
$posts = Post::forWebsite($websiteId)->get();

// 避免
$posts = Post::whereHas('websites', function($query) use ($websiteId) {
    $query->where('website_id', $websiteId);
})->get();
```

### 4. 预载入网站

查询多个模型时载入网站：

```php
$posts = Post::with('websites')->get();
```

## 进阶用法

### 自订网站关联

您可以扩充网站关联：

```php
class Post extends BaseModel
{
    // 使用额外资料新增自订方法
    public function primaryWebsite()
    {
        return $this->websites()->wherePivot('is_primary', true)->first();
    }
}
```

### 条件式网站逻辑

根据网站模式实作不同逻辑：

```php
class Post extends BaseModel
{
    public function publish()
    {
        $this->update(['status' => 'published']);

        if ($this->getWebsiteMode() === 'global') {
            // 在所有网站上发布
            Cache::tags('posts')->flush();
        } else {
            // 仅清除特定网站快取
            foreach ($this->websites as $website) {
                Cache::tags("posts:website:{$website->id}")->flush();
            }
        }
    }
}
```

## 疑难排解

### 模型未显示在网站上

检查网站关联：

```php
// 验证关联
$post = Post::find($id);
dd($post->websites->pluck('id'));

// 检查网站模式
dd($post->getWebsiteMode());
```

### 错误的网站模式

确保在 `config/wncms.php` 中正确配置：

```php
'model_website_modes' => [
    'posts' => 'multi', // 使用正确的表格名称（复数）
],
```

### 查询回传太多结果

始终套用网站作用域：

```php
// 不好：取得所有文章
$posts = Post::all();

// 好：仅取得当前网站
$posts = Post::forCurrentWebsite()->get();
```

## 另请参阅

- [Base Model](../model/base-model.md)
- [Manager](../manager/base-manager.md)
- [开发者总览](../overview.md)
