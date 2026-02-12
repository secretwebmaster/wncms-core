# 为 Core Models 添加关联

WNCMS 允许套件扩展**核心 models**（User, Post, Website, Tag 等），而无需修改核心原始码。  
这是透过 `MacroableModels` facade 实现的，它在执行时动态注册 Eloquent 关联、accessors 和自订方法。

## 何时使用 MacroableModels

当您的套件需要以下功能时，请使用 `MacroableModels::addMacro()`：

- 为 WNCMS core models 添加关联
- 添加计算属性（例如 `getXxxAttribute`）
- 添加辅助方法（例如 `hasPurchased()`, `latestChapter()`）
- 在不编辑 WNCMS 核心档案的情况下扩展 models

Macros 在您套件的 **ServiceProvider** 中注册。

## 基本用法

### 引入 Facade

```php
use Wncms\Facades\MacroableModels;
```

### 在 boot() 中注册 Macros

Macros 在 service provider 的 `boot()` 方法中注册：

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
始终使用 `wncms()->getModelClass('user')` 而不是硬编码 model 类别。这确保了当使用者在设定中覆盖核心 models 时的相容性。
:::

## 范例：Novel 套件关联

### 关联 1：User hasMany Novels

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

### 关联 2：Novel hasMany Chapters

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

### 关联 3：NovelChapter belongsTo Novel

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

Macros 也可以使用 `getXxxAttribute` 命名惯例来定义**计算属性**：

```php
MacroableModels::addMacro(Novel::class, 'getChapterCountAttribute', function () {
    $this->loadMissing('chapters');
    return $this->chapters->count();
});
```

**使用方式：**

```php
$novel = Novel::find(1);
echo $novel->chapter_count; // Accessor，不是资料库栏位
```

## 添加自订方法

定义既不是关联也不是 accessors 的辅助方法：

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

## 完整范例：Novel Service Provider

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

| Model        | 添加的方法        | 类型     | 说明                    |
| ------------ | ----------------- | -------- | ----------------------- |
| User         | `novels()`        | 关联     | User 拥有多个 novels    |
| Novel        | `chapters()`      | 关联     | Novel 包含多个 chapters |
| NovelChapter | `novel()`         | 关联     | Chapter 属于一个 novel  |
| Novel        | `chapter_count`   | Accessor | 计算的章节总数          |
| Novel        | `latestChapter()` | 方法     | 取得最新章节            |

## 最佳实践

### 使用 wncms()->getModelClass()

始终动态取得核心 models：

```php
$userModel = wncms()->getModelClass('user');
$postModel = wncms()->getModelClass('post');
$tagModel = wncms()->getModelClass('tag');
```

这确保即使使用者在 `config/wncms.php` 中覆盖核心 models，您的套件仍能正常运作。

### 使用 try-catch 包裹

防止启动失败破坏整个应用程式：

```php
try {
    MacroableModels::addMacro(...);
} catch (\Throwable $e) {
    info('Macro registration failed: ' . $e->getMessage());
}
```

### 检查类别是否存在

在为核心 models 添加 macros 之前：

```php
if (class_exists($userModel)) {
    MacroableModels::addMacro($userModel, 'novels', function () { ... });
}
```

### 避免冲突

使用描述性的方法名称以防止与其他套件冲突：

```php
// ❌ 不好（通用名称）
MacroableModels::addMacro($userModel, 'items', ...);

// ✅ 好（特定于您的套件）
MacroableModels::addMacro($userModel, 'novels', ...);
```

## 注意事项

- 所有 macros 都与**快取**、**预载入**和 **API resources** 相容
- Macros 在应用程式启动期间注册一次
- 使用 `MacroableModels::removeMacro($model, 'name')` 来取消注册 macro
- 检查 macro 是否存在：`MacroableModels::modelHasMacro($model, 'name')`

参见 [Create a Model](./create-a-model) 以了解如何建立自订 models。
