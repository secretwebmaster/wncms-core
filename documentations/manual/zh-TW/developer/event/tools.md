# Tools Events

## Backend Tools

#### wncms.backend.tools.index.resolve

在後端 Tools 首頁視圖渲染前觸發。

Parameters:
- `&$view` (string)
- `&$params` (array)
- `$request` (Request)

#### wncms.view.backend.tools.index.cards

用於在後端 Tools 首頁網格中注入自訂工具卡片的視圖插槽事件。

Parameters:
- `$request` (Request)

Listener return:
- 回傳包含一個或多個工具網格欄位的 HTML 字串
- 每個注入卡片應自行包含 `.col-12.col-md-6.col-lg-3.d-flex` 外層包裝
