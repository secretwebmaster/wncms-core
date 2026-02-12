# Base Controller

WNCMS 的 base controller 是一个精简的基础，集中处理视图解析。在大多数情况下，**您不应该直接扩展此类别**。相反，应扩展其子 controllers，它们封装了特定层级的行为。

## 我应该扩展哪个 Controller？

### 对于 Backend（后台 CRUD）

**扩展：** `Wncms\Http\Controllers\Backend\BackendController`

**用于：** 后台 CRUD 页面、设定页面、列表和编辑 models。

**原因：** 提供 model 命名、cache tag 辅助方法和统一的 backend CRUD 模式。

**命名空间：** `App\Http\Controllers\Backend\...` 或套件 backend controllers。

**视图：** `backend.{models}.*` Blade 视图。

### 对于 Frontend（公开主题页面）

**扩展：** `Wncms\Http\Controllers\Frontend\FrontendController`

**用于：** 由当前主题渲染的公开网站页面（首页、文章、页面、标签等）。

**原因：** 主题感知渲染、网站上下文和 frontend 惯例。

**命名空间：** `App\Http\Controllers\Frontend\...` 或套件 frontend controllers。

**视图：** `frontend.*` Blade 视图，透过主题解析。

### 对于 API（JSON 端点）

**扩展：** `Wncms\Http\Controllers\Api\ApiController`

**用于：** 供外部应用程式（Vue、Next.js、mobile）使用的 JSON APIs。

**原因：** API 相关问题，如认证、标准化回应/resources。

**命名空间：** `App\Http\Controllers\Api\V1\...` 或套件 API controllers。

**回应：** JSON 回应 / API resources。

## 何时直接扩展 Base Class

- 建立新的 controller **层级**（例如，专用子系统），其他 controllers 将会扩展它。
- 建立共享抽象，在分层之前添加横切辅助方法（罕见）。

如果您不符合这些情况，请使用上述子 controller。

## 下一步

- Backend：参见 [Backend Controller](./backend-controller)
- Frontend：参见 [Frontend Controller](./frontend-controller)
- API：参见 [API Controller](./api-controller)
- 脚手架：参见 [Create a Controller](./create-a-controller)
