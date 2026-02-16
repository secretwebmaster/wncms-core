# 在 Model 上定義 Tag Types

WNCMS models 可以使用靜態屬性 `$tagMetas` 來宣告 **tag types**。  
這允許每個 model 描述它支援哪些 tag types，以及可選的路由元資料。

## 基本結構

直接在 model 上定義 `$tagMetas`：

```php
protected static array $tagMetas = [
    [
        'key'   => 'novel_category',
        'short' => 'category',
        'route' => 'frontend.novels.tag',
    ],
    [
        'key'   => 'novel_tag',
        'short' => 'tag',
        'route' => 'frontend.novels.tag',
    ],
];
```

每個項目描述**一種 tag type**。

## 欄位說明

| 欄位    | 必填 | 說明                                              |
| ------- | ---- | ------------------------------------------------- |
| `key`   | 是   | 儲存在 tag model 上的 tag type（`tags.type`）。   |
| `short` | 是   | 您的程式碼在識別此類型時使用的簡短別名。          |
| `route` | 否   | 您的專案在生成 tag 連結時可能使用的前台路由名稱。 |

WNCMS 除了透過 `BaseModel::getTagMeta()` 回傳此元資料外，不強制執行任何行為。

## BaseModel 如何處理 Tag Types

`BaseModel` 定義：

```php
protected static array $tagMetas = [];
```

當 model 覆蓋它時，`BaseModel::getTagMeta()` 會回傳一個陣列，其中每個 tag type 都會被豐富以下資訊：

- `model` – model 類別
- `model_key` – model 的 `$modelKey`
- `package` – model 的 `$packageId`
- `label` – 從套件和 tag key 生成的翻譯鍵

您的應用程式或套件可以讀取此元資料並決定如何使用它。

## 範例：Novel Model

```php
class Novel extends BaseModel implements HasMedia, ApiModelInterface
{
    protected static array $tagMetas = [
        [
            'key'   => 'novel_category',
            'short' => 'category',
            'route' => 'frontend.novels.tag',
        ],
        [
            'key'   => 'novel_tag',
            'short' => 'tag',
            'route' => 'frontend.novels.tag',
        ],
    ];
}
```

## 空的 Tag 定義

如果 model 不支援 tags，保持為空：

```php
protected static array $tagMetas = [];
```

`BaseModel::getTagMeta()` 將回傳空陣列。

## 後台標籤類型選擇與啟用模型

在後台標籤頁面（`tags.index`、`tags.create`、`tags.edit`、`tags.keywords.index`）中，標籤類型下拉選項現在會依啟用模型過濾。

- 設定來源：`active_models`（系統設定 -> 顯示模型）
- 比對規則：以 tag meta 的 `model` 類別短名（basename）比對啟用的模型名稱
- 回退行為：若 `active_models` 為空，後台仍顯示所有已註冊的標籤類型

這樣可讓後台標籤操作與目前在後台導覽中啟用的模型保持一致。
