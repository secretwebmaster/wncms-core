# 扩展 WNCMS Core Models

## Morph Class

当您扩展 WNCMS core model（例如，建立 `App\Models\Page` 并继承 `Wncms\Models\Page`）时，您必须了解 Laravel 如何储存多型关联。

Laravel 将 model 的**类别名称**储存到 morph 表中，例如：

- `tags`（HasTags）
- `translations`
- `media`
- `options`（theme options, template options）

`options` 表中的范例资料行：

```
optionable_type = "Wncms\Models\Page"
optionable_id   = 5
```

如果您这样扩展此 model：

```php
namespace App\Models;

class Page extends \Wncms\Models\Page
{
}
```

Laravel 现在看到的是**完全不同的类别名称**：

```
App\Models\Page
```

这意味着：

- `Wncms\Models\Page` 的 options 不会载入到 `App\Models\Page` 中
- `App\Models\Page` 的 options 会以不同的 morph type 储存
- Tags、translations、media 和 options 都会中断，因为它们不再指向相同的 morph 目标

### 如何修复

在您的扩展 model 中添加：

```php
protected $morphClass = \Wncms\Models\Page::class;
```

这确保：

- `App\Models\Page` 储存**与 WNCMS core 相同的 morph type**
- 两个类别共享：
  - Options
  - Tags
  - Translations
  - Media
  - Template blocks
  - Theme options
- 无资料重复
- 无中断的关联

### 范例

```php
namespace App\Models;

class Page extends \Wncms\Models\Page
{
    protected $morphClass = \Wncms\Models\Page::class;
}
```
