# 為 Core Models 添加關聯

WNCMS 允許套件擴展**核心 models**（User, Post, Website, Tag 等），而無需修改核心原始碼。  
這是透過 `MacroableModels` facade 實現的，它在執行時動態註冊 Eloquent 關聯、accessors 和自訂方法。

## 何時使用 MacroableModels

當您的套件需要以下功能時，請使用 `MacroableModels::addMacro()`：

- 為 WNCMS core models 添加關聯
- 添加計算屬性（例如 `getXxxAttribute`）
- 添加輔助方法（例如 `hasPurchased()`, `latestChapter()`）
- 在不編輯 WNCMS 核心檔案的情況下擴展 models

Macros 在您套件的 **ServiceProvider** 中註冊。

## 基本用法

### 引入 Facade

```php
use Wncms\Facades\MacroableModels;
```

### 在 boot() 中註冊 Macros

Macros 在 service provider 的 `boot()` 方法中註冊：

```php
public function boot(): void
{
    try {
        $userModel = wncms()->getModelClass('user');

        if (class_exists($userModel)) {
            MacroableModels::addMacro($userModel, 'novels', function () {
                return $this->hasMany(\Secretwebmaster\WncmsNovels\Models\Novel::class);
            });
        }
    } catch (\Throwable $e) {
        info('Novel macros not registered: ' . $e->getMessage());
    }
}
```

:::tip WNCMS v6.x.x
始終使用 `wncms()->getModelClass('user')` 而不是硬編碼 model 類別。這確保了當使用者在設定中覆蓋核心 models 時的相容性。
:::

## 範例：Novel 套件關聯

### 關聯 1：User hasMany Novels

```php
MacroableModels::addMacro($userModel, 'novels', function () {
    return $this->hasMany(\Secretwebmaster\WncmsNovels\Models\Novel::class);
});
```

**使用方式：**

```php
$user = wncms()->user()->get(['id' => 1]);
$userNovels = $user->novels;
```

### 關聯 2：Novel hasMany Chapters

```php
use Secretwebmaster\WncmsNovels\Models\Novel;
use Secretwebmaster\WncmsNovels\Models\NovelChapter;

MacroableModels::addMacro(Novel::class, 'chapters', function () {
    return $this->hasMany(NovelChapter::class);
});
```

**使用方式：**

```php
$novel = Novel::find(1);
$chapters = $novel->chapters;
```

### 關聯 3：NovelChapter belongsTo Novel

```php
MacroableModels::addMacro(NovelChapter::class, 'novel', function () {
    return $this->belongsTo(Novel::class);
});
```

**使用方式：**

```php
$chapter = NovelChapter::find(5);
$novel = $chapter->novel;
```

## 添加 Accessors

Macros 也可以使用 `getXxxAttribute` 命名慣例來定義**計算屬性**：

```php
MacroableModels::addMacro(Novel::class, 'getChapterCountAttribute', function () {
    $this->loadMissing('chapters');
    return $this->chapters->count();
});
```

**使用方式：**

```php
$novel = Novel::find(1);
echo $novel->chapter_count; // Accessor，不是資料庫欄位
```

## 添加自訂方法

定義既不是關聯也不是 accessors 的輔助方法：

```php
MacroableModels::addMacro(Novel::class, 'latestChapter', function () {
    return $this->chapters()->orderBy('number', 'desc')->first();
});
```

**使用方式：**

```php
$novel = Novel::find(1);
$latest = $novel->latestChapter(); // 方法呼叫
```

## 完整範例：Novel Service Provider

```php
namespace Secretwebmaster\WncmsNovels;

use Illuminate\Support\ServiceProvider;
use Wncms\Facades\MacroableModels;
use Secretwebmaster\WncmsNovels\Models\Novel;
use Secretwebmaster\WncmsNovels\Models\NovelChapter;

class WncmsNovelsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            $userModel = wncms()->getModelClass('user');

            // User → Novels
            if (class_exists($userModel)) {
                MacroableModels::addMacro($userModel, 'novels', function () {
                    return $this->hasMany(Novel::class);
                });
            }

            // Novel → Chapters
            MacroableModels::addMacro(Novel::class, 'chapters', function () {
                return $this->hasMany(NovelChapter::class);
            });

            // Chapter → Novel
            MacroableModels::addMacro(NovelChapter::class, 'novel', function () {
                return $this->belongsTo(Novel::class);
            });

            // Accessor: chapter_count
            MacroableModels::addMacro(Novel::class, 'getChapterCountAttribute', function () {
                $this->loadMissing('chapters');
                return $this->chapters->count();
            });

            // Method: latestChapter()
            MacroableModels::addMacro(Novel::class, 'latestChapter', function () {
                return $this->chapters()->orderBy('number', 'desc')->first();
            });

        } catch (\Throwable $e) {
            info('Novel macros not registered: ' . $e->getMessage());
        }
    }
}
```

## 摘要表格

| Model        | 添加的方法        | 類型     | 說明                    |
| ------------ | ----------------- | -------- | ----------------------- |
| User         | `novels()`        | 關聯     | User 擁有多個 novels    |
| Novel        | `chapters()`      | 關聯     | Novel 包含多個 chapters |
| NovelChapter | `novel()`         | 關聯     | Chapter 屬於一個 novel  |
| Novel        | `chapter_count`   | Accessor | 計算的章節總數          |
| Novel        | `latestChapter()` | 方法     | 取得最新章節            |

## 最佳實踐

### 使用 wncms()->getModelClass()

始終動態取得核心 models：

```php
$userModel = wncms()->getModelClass('user');
$postModel = wncms()->getModelClass('post');
$tagModel = wncms()->getModelClass('tag');
```

這確保即使使用者在 `config/wncms.php` 中覆蓋核心 models，您的套件仍能正常運作。

### 使用 try-catch 包裹

防止啟動失敗破壞整個應用程式：

```php
try {
    MacroableModels::addMacro(...);
} catch (\Throwable $e) {
    info('Macro registration failed: ' . $e->getMessage());
}
```

### 檢查類別是否存在

在為核心 models 添加 macros 之前：

```php
if (class_exists($userModel)) {
    MacroableModels::addMacro($userModel, 'novels', function () { ... });
}
```

### 避免衝突

使用描述性的方法名稱以防止與其他套件衝突：

```php
// ❌ 不好（通用名稱）
MacroableModels::addMacro($userModel, 'items', ...);

// ✅ 好（特定於您的套件）
MacroableModels::addMacro($userModel, 'novels', ...);
```

## 注意事項

- 所有 macros 都與**快取**、**預載入**和 **API resources** 相容
- Macros 在應用程式啟動期間註冊一次
- 使用 `MacroableModels::removeMacro($model, 'name')` 來取消註冊 macro
- 檢查 macro 是否存在：`MacroableModels::modelHasMacro($model, 'name')`

參見 [Create a Model](./create-a-model) 以了解如何建立自訂 models。
