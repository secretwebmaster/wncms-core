# Backend Controller

`Wncms\Http\Controllers\Backend\BackendController` 是 **后台 CRUD** controllers 的基础。它标准化了 model 解析、命名、cache-tag 处理和常见操作（`index/create/store/edit/update/destroy/bulk_delete`）。为每个后台资源扩展它。

## 主要职责

- 从 controller 名称解析 **model class**（`PostController` → model key `post` → `wncms()->getModelClass('post')`）。
- 派生用于视图和标签的**资料表 / 单数 / 复数**名称。
- 提供 **cache tag** 辅助方法和 `flush()` 方法。
- 提供具有合理预设值和 AJAX JSON 回应的主观 **CRUD** 方法。

## 属性和预设值

| 属性          | 类型     | 来源                  | 预设行为                                                                                           |
| ------------- | -------- | --------------------- | -------------------------------------------------------------------------------------------------- |
| `$modelClass` | `string` | `getModelClass()`     | 从不含 `Controller` 的 controller 基础名称，snake-cased，透过 `wncms()->getModelClass(...)` 解析。 |
| `$cacheTags`  | `array`  | `getModelCacheTags()` | 预设为 `[$this->getModelTable()]`。                                                                |
| `$singular`   | `string` | `getModelSingular()`  | `str()->singular($this->getModelTable())`。                                                        |
| `$plural`     | `string` | `getModelPlural()`    | `str()->plural($this->getModelSingular())`。                                                       |

> 在需要时，在子 controller 中将这些作为 protected 属性覆盖。

## 可覆盖的辅助方法

```php
// 从 controller 名称解析 model class；如需自订映射请覆盖。
public function getModelClass(): string

// 取得底层 Eloquent 资料表名称。
protected function getModelTable()

// 为此资源提供自订 cache tags。
protected function getModelCacheTags(): array

// 自订资源名词。
protected function getModelSingular(): string
protected function getModelPlural(): string

// 为 single/multi 网站模式套用当前网站列表筛选。
protected function applyBackendListWebsiteScope(Builder $q): void
```

## Cache 控制

```php
public function flush(string|array|null $tags = null): bool
```

- 透过 `wncms()->cache()->tags($tag)->flush()` 清除已标记的快取。
- 如果 `$tags` 为 `null`，使用 `$this->cacheTags`。

## 多站点列表筛选辅助方法

`applyBackendListWebsiteScope()` 用于标准化后台 index 列表筛选（仅针对网站模式为 `single` 或 `multi` 的模型）。

- 从 `wncms()->website()->get()?->id` 读取当前网站 ID。
- 仅在模型支持多站点作用域时调用 `applyWebsiteScope(...)`。
- 对 `global` 模型或无法解析当前网站时不做任何处理。
- 对 index 工具列筛选，建议统一使用 `website_id` 作为请求参数，并兼容读取旧键 `website`。

## 内建 CRUD 操作

所有操作假设标准的 backend Blade 路径：`backend.{plural}.*`。

- `index(Request $request)`

  - 在 `$modelClass` 上建立基础查询，按 `id desc` 排序，回传 `backend.{plural}.index`。
  - 传递 `page_title`、`models`。

- `create(int|string|null $id = null)`

  - 新实例或载入现有实例用于「复制/编辑为新」模式。
  - 回传 `backend.{plural}.create` 与 `model`。

- `store(Request $request)`

  - `create($request->all())`，然后：

    - 如果是 AJAX：JSON `{ status, message, redirect }`。
    - 否则：重定向到 `route('{plural}.edit', ['id' => $model->id])`。

- `edit(int|string $id)`

  - 载入 model，回传 `backend.{plural}.edit` 与 `model`。

- `update(Request $request, $id)`

  - 类似 `findOrFail` 的行为（如果缺少则回传讯息），`update($request->all())`。
  - 如果是 AJAX：JSON `{ status, message, redirect }`。
  - 否则：重定向回编辑页面。

- `destroy($id)`

  - 删除 model，呼叫 `$this->flush()`，重定向到 index 并显示成功讯息。

- `bulk_delete(Request $request)`

  - 接受 `model_ids` 作为 CSV 或阵列，批次删除。
  - 如果是 AJAX：包含已删除数量的 JSON；否则 `back()` 并显示讯息。

> 讯息遵循 WNCMS 翻译（例如 `__('wncms::word.successfully_updated')`）。标题使用 `__('wncms::word.' . $this->singular)`。

## 最小子类别范例

```php
namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Wncms\Http\Controllers\Backend\BackendController;

class ProductController extends BackendController
{
    // BackendController 会自动解析 'product' → wncms()->getModelClass('product')
    // 除非您覆盖 getModelClass() 或设定 protected $modelClass
}
```

## 覆盖范例

```php
namespace App\Http\Controllers\Backend;

use Illuminate\Http\Request;
use Wncms\Http\Controllers\Backend\BackendController;

class CustomProductController extends BackendController
{
    protected function getModelClass(): string
    {
        return \App\Models\Product::class; // 自订映射
    }

    protected function getModelCacheTags(): array
    {
        return ['products', 'catalog']; // 自订 tags
    }

    public function index(Request $request)
    {
        // 使用 parent 的逻辑，或完全覆盖
        $query = $this->modelClass::query();
        $this->applyBackendListWebsiteScope($query);

        // 添加自订筛选
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $models = $query->orderByDesc('id')->paginate(20);

        return $this->view("backend.{$this->plural}.index", [
            'page_title' => __('wncms::word.model_management', ['model_name' => __('wncms::word.' . $this->singular)]),
            'models' => $models,
        ]);
    }
}
```

## 总结

- 为每个后台资源扩展 `BackendController`。
- 依赖自动 model 解析或根据需要覆盖。
- 使用内建的 CRUD 方法或覆盖以进行自订逻辑。
- 利用 cache flushing 和 WNCMS 辅助方法保持程式码简洁。

## WNCMS 多站点兼容写入模式

当模型支援 WNCMS 多站点方法时，不要在 `create()/update()` payload 硬写旧版外键栏位（例如 `website_id`）。建议先更新一般栏位，再用 `bindWebsites(...)` 绑定站点关系：

```php
$payload = [
    'name' => $request->name,
    'type' => $request->type,
];

$model->update($payload);

if (method_exists($model, 'bindWebsites') && method_exists($model, 'getWebsiteMode')) {
    if (in_array($model::getWebsiteMode(), ['single', 'multi'], true) && $websiteId) {
        $model->bindWebsites($websiteId);
    }
}
```
