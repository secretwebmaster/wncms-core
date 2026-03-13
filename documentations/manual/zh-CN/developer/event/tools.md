# Tools Events

## Backend Tools

#### wncms.backend.tools.index.resolve

在后端 Tools 首页视图渲染前触发。

Parameters:
- `&$view` (string)
- `&$params` (array)
- `$request` (Request)

#### wncms.view.backend.tools.index.cards

用于在后端 Tools 首页网格中注入自定义工具卡片的视图插槽事件。

Parameters:
- `$request` (Request)

Listener return:
- 返回包含一个或多个工具网格列的 HTML 字符串
- 每个注入卡片应自行包含 `.col-12.col-md-6.col-lg-3.d-flex` 外层包装
