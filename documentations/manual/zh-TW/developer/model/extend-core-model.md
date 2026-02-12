# 擴展 WNCMS Core Models

## Morph Class

當您擴展 WNCMS core model（例如，建立 `App\Models\Page` 並繼承 `Wncms\Models\Page`）時，您必須了解 Laravel 如何儲存多型關聯。

Laravel 將 model 的**類別名稱**儲存到 morph 表中，例如：

- `tags`（HasTags）
- `translations`
- `media`
- `options`（theme options, template options）

`options` 表中的範例資料行：

```
optionable_type = "Wncms\Models\Page"
optionable_id   = 5
```

如果您這樣擴展此 model：

```php
namespace App\Models;

class Page extends \Wncms\Models\Page
{
}
```

Laravel 現在看到的是**完全不同的類別名稱**：

```
App\Models\Page
```

這意味著：

- `Wncms\Models\Page` 的 options 不會載入到 `App\Models\Page` 中
- `App\Models\Page` 的 options 會以不同的 morph type 儲存
- Tags、translations、media 和 options 都會中斷，因為它們不再指向相同的 morph 目標

### 如何修復

在您的擴展 model 中添加：

```php
protected $morphClass = \Wncms\Models\Page::class;
```

這確保：

- `App\Models\Page` 儲存**與 WNCMS core 相同的 morph type**
- 兩個類別共享：
  - Options
  - Tags
  - Translations
  - Media
  - Template blocks
  - Theme options
- 無資料重複
- 無中斷的關聯

### 範例

```php
namespace App\Models;

class Page extends \Wncms\Models\Page
{
    protected $morphClass = \Wncms\Models\Page::class;
}
```
