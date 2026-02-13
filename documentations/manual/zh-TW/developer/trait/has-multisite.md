# HasMultisite Trait

## 概述

`HasMultisite` trait 使 WNCMS 模型能夠與多個網站關聯。它透過可配置的模式提供靈活的多網站支援，允許模型屬於單個網站、多個網站或全域適用於所有網站。

## 網站模式

每個使用 `HasMultisite` 的模型可以在三種模式下運作：

### 1. Global 模式

模型在所有網站上都可用，不需要明確關聯。

```php
// config/wncms.php
'model_website_modes' => [
    'posts' => 'global',
],
```

### 2. Single 模式

模型只能屬於一個網站。

```php
// config/wncms.php
'model_website_modes' => [
    'pages' => 'single',
],
```

### 3. Multi 模式

模型可以關聯到多個網站。

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
    // HasMultisite 已透過 BaseModel 提供
}
```

如果您的模型不繼承 `BaseModel`：

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

### 配置網站模式

在 `config/wncms.php` 中為您的模型定義網站模式：

```php
return [
    'model_website_modes' => [
        'posts' => 'multi',      // 文章可以屬於多個網站
        'pages' => 'single',     // 頁面只能屬於一個網站
        'tags' => 'global',      // 標籤在所有網站上共享
    ],
];
```

舊版專案也可能使用平鋪設定：

```php
'model_website_modes' => [
    'post' => 'multi',
    'page' => 'single',
],
```

WNCMS 會合併兩種設定來源，並在最後套用資料庫中的系統設定（`model_website_modes`）。優先順序如下：

1. 資料庫系統設定 `model_website_modes`
2. `config('wncms.models.*.website_mode')`
3. `config('wncms.model_website_modes')`（舊版）

## 可用方法

如果模型繼承 `Wncms\Models\BaseModel`，則已預設包含 `HasMultisite`，不需要在子模型再次 `use HasMultisite;`。

### getWebsiteMode()

取得模型的網站模式：

```php
$mode = $post->getWebsiteMode();
// 回傳：'global'、'single' 或 'multi'

// 檢查特定模式
if ($post->getWebsiteMode() === 'multi') {
    // 處理多網站模型
}
```

### websites()

存取模型的網站關聯（適用於 single 和 multi 模式）：

```php
// 取得所有關聯的網站
$websites = $post->websites;

// 使用關聯方法
$websiteCount = $post->websites()->count();

// 檢查模型是否屬於網站
$belongsToWebsite = $post->websites()
    ->where('id', $websiteId)
    ->exists();
```

### bindWebsites()

將模型關聯到一個或多個網站：

```php
// 關聯到單個網站
$post->bindWebsites(1);

// 關聯到多個網站
$post->bindWebsites([1, 2, 3]);

// 使用網站模型
$post->bindWebsites($website);
$post->bindWebsites([$website1, $website2]);
```

### unbindWebsites()

從網站中解除模型關聯：

```php
// 從所有網站解除關聯
$post->unbindWebsites();

// 從特定網站解除關聯
$post->unbindWebsites(1);
$post->unbindWebsites([1, 2, 3]);

// 使用網站模型
$post->unbindWebsites($website);
```

### syncWebsites()

同步模型的網站（移除現有並新增新的）：

```php
// 同步到特定網站
$post->syncWebsites([1, 2, 3]);

// 清除所有網站
$post->syncWebsites([]);
```

## 查詢作用域

### forWebsite()

查詢屬於特定網站的模型：

```php
// 為特定網站取得文章
$posts = Post::forWebsite(1)->get();

// 使用網站模型
$posts = Post::forWebsite($website)->get();

// 與其他作用域組合
$posts = Post::forWebsite($currentWebsite)
    ->where('status', 'published')
    ->latest()
    ->get();
```

### forCurrentWebsite()

為當前活動網站查詢模型：

```php
// 自動使用當前網站
$posts = Post::forCurrentWebsite()->get();
```

## 資料庫結構

多網站關聯儲存在 `model_has_websites` 表中：

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

### 在 Manager 中套用網站篩選

WNCMS Manager 類別自動處理網站篩選：

```php
<?php

namespace App\Services\Managers;

use Wncms\Services\Managers\BaseManager;

class PostManager extends BaseManager
{
    public function index(array $params = [])
    {
        $query = $this->model->query();

        // 自動套用網站範圍
        $this->applyWebsiteScope($query, $params);

        return $query->paginate($params['per_page'] ?? 20);
    }
}
```

手動套用網站篩選：

```php
// 透過網站 ID 篩選
$params = [
    'website_id' => 1,
];

// 為當前網站篩選
$params = [
    'website_id' => wncms()->website()->id,
];

// 全域模型忽略網站篩選
$params = [
    'ignore_website' => true,
];
```

## 實務範例

### 建立多網站文章

```php
// 建立文章
$post = Post::create([
    'title' => 'My Article',
    'content' => 'Article content...',
]);

// 關聯到網站
$post->bindWebsites([1, 2, 3]);

// 或在建立時
$post = Post::create([
    'title' => 'My Article',
    'content' => 'Article content...',
]);
$post->bindWebsites(wncms()->website()->id);
```

### 為網站查詢內容

```php
// 取得當前網站的所有文章
$posts = Post::forCurrentWebsite()
    ->where('status', 'published')
    ->get();

// 取得特定網站的文章
$websiteId = 1;
$posts = Post::forWebsite($websiteId)
    ->latest()
    ->paginate(20);
```

### 切換網站關聯

```php
// 將文章從一個網站移到另一個
$post->unbindWebsites($oldWebsiteId);
$post->bindWebsites($newWebsiteId);

// 或使用 sync
$post->syncWebsites([$newWebsiteId]);
```

### 跨網站內容

```php
// 尋找在多個網站上的文章
$posts = Post::whereHas('websites', function($query) {
    $query->whereIn('website_id', [1, 2, 3]);
})->get();

// 尋找恰好在 2 個網站上的文章
$posts = Post::has('websites', '=', 2)->get();
```

## 最佳實踐

### 1. 設定適當的網站模式

為每種內容類型選擇正確的模式：

```php
// config/wncms.php
'model_website_modes' => [
    // 特定於每個網站的內容
    'posts' => 'multi',
    'pages' => 'single',

    // 跨網站共享的內容
    'tags' => 'global',
    'categories' => 'global',
    'users' => 'global',
],
```

### 2. 建立時關聯網站

建立內容時始終關聯到網站：

```php
$post = Post::create($data);
$post->bindWebsites(wncms()->website()->id);
```

### 3. 使用作用域進行查詢

始終使用 `forWebsite()` 作用域進行網站特定查詢：

```php
// 好
$posts = Post::forWebsite($websiteId)->get();

// 避免
$posts = Post::whereHas('websites', function($query) use ($websiteId) {
    $query->where('website_id', $websiteId);
})->get();
```

### 4. 預載入網站

查詢多個模型時載入網站：

```php
$posts = Post::with('websites')->get();
```

## 進階用法

### 自訂網站關聯

您可以擴充網站關聯：

```php
class Post extends BaseModel
{
    // 使用額外資料新增自訂方法
    public function primaryWebsite()
    {
        return $this->websites()->wherePivot('is_primary', true)->first();
    }
}
```

### 條件式網站邏輯

根據網站模式實作不同邏輯：

```php
class Post extends BaseModel
{
    public function publish()
    {
        $this->update(['status' => 'published']);

        if ($this->getWebsiteMode() === 'global') {
            // 在所有網站上發布
            Cache::tags('posts')->flush();
        } else {
            // 僅清除特定網站快取
            foreach ($this->websites as $website) {
                Cache::tags("posts:website:{$website->id}")->flush();
            }
        }
    }
}
```

## 疑難排解

### 模型未顯示在網站上

檢查網站關聯：

```php
// 驗證關聯
$post = Post::find($id);
dd($post->websites->pluck('id'));

// 檢查網站模式
dd($post->getWebsiteMode());
```

### 錯誤的網站模式

確保在 `config/wncms.php` 中正確配置：

```php
'model_website_modes' => [
    'posts' => 'multi', // 使用正確的表格名稱（複數）
],
```

### 查詢回傳太多結果

始終套用網站作用域：

```php
// 不好：取得所有文章
$posts = Post::all();

// 好：僅取得當前網站
$posts = Post::forCurrentWebsite()->get();
```

## 另請參閱

- [Base Model](../model/base-model.md)
- [Manager](../manager/base-manager.md)
- [開發者總覽](../overview.md)
