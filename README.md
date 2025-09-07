# Wncms 項目介紹

Demo: [demo.wncms.cc](https://demo.wncms.cc)

## 更新日誌

### v5.5.5 2025-09-08

-   更新 AdvertisementManager，繼承 ModelManager
-   移除重複的翻譯 migration 檔案
-   修正找不到 Advertisement 與網站的關聯問題
-   在 PageController 載入前端主題 helpers
-   將 HasTags trait 移至基礎 WncmsModel（所有模型皆可被標籤化）
-   移除多餘的 OwnModelTrait 與 WnModelTrait
-   移除 ExtraAttribute，改用 laravel-optionable 套件
-   後台新增工具頁面
-   在 @simonwep/pickr 套件加入多語系化
-   重構所有控制器中的 view()，改為使用 wncms()->view()
-   在 Advertisement 模型中新增 contact 欄位
-   新增建立 Tag 模型的 API
-   新增 HasComments trait
-   在後台 Post 顯示評論清單
-   重寫前台 PostController 以符合最新格式
-   修復後台權限編輯頁錯誤
-   預備後台 UI 以支援 API 使用日誌

### v5.5.4 2025-08-18

-   修復標籤保存時的翻譯問題，更新依賴 **wncms-translatable**
-   修復 **UserController** 中多處驗證與資料傳遞問題（`profile` 頁面 `$user` 未傳遞、密碼驗證規則與訊息錯誤、`withError()` 使用錯誤、`profile` 更新程式優化）
-   修復部分站群模式的問題
-   更新 **Controller stub** 檔案以符合新結構
-   修復後台控制器路由錯誤

### v5.5.3 2025-08-17

-   修復提交 Checkbox 會送出 NULL 值的問題
-   Beta 版本多模組站群相容性適配
-   更新 Starter 表單項 Blade 中的 repeater 範例
-   將 WnContentModelTraits 更名為 WnContentModelTrait
-   將 WnModelTraits 更名為 WnModelTrait
-   Beta 版本新增 HasMultisite trait

### v5.5.2 2025-07-20

-   添加前台鏈接頁面，鏈接控制器
-   更新依賴 wncms-tags > 1.6.1

### v5.5.0 2025-07-19

-   部分後台 Blade 視圖中將 `getVersion()` 替換為 `addVersion()`
-   新增 `isActiveRoutes()` 輔助函數
-   修復安裝程序在資料庫遷移後中止的問題
-   避免在覆蓋 `Wncms\Models` 為 `App\Models` 時側邊欄重複出現模型
-   修復 `PageController` 中自定義資源頁面無法顯示的問題
-   移除 `click.js`，改用 API 取代
-   安裝後自動更新版本號
-   預設將佇列連線從 `redis` 改為 `sync`
-   刪除動作將返回上一頁而非索引頁
-   重寫 `PageManager`
-   重寫 `UserManager`
-   儀表板在資料庫較大時的記憶體需求已優化
-   清理後台 `PostController` 代碼
-   支援在 WNCMS 預設視圖之前使用自定義 app 視圖
-   當輸入欄位為 hidden 類型時，隱藏開發者提示
-   修復廣告背景顏色未顯示的問題
-   後台聯絡表單整合建立與編輯表單項目
-   移除 `BulkDeleteTrait`，整合進 `BackendController`
-   移除 `tags.index.type` 路由，整合進 `tags.index`
-   移除選單中的多站台選項
-   將產品欄位 `attribute` 改名為 `properties`
-   支援新增自定義搜尋關鍵字
-   移除 `traffic_log` 和 `click_log` 的 Blade 視圖
-   所有模型關聯改為使用 `wncms()->getModelClass('xxx')`，以支援模型覆寫
-   `CreateModel` 路由更新為使用 `{id}` 取代 `{modelName}`
-   控制器樣板加入 `order by id desc`
-   改進後台網站編輯頁面，加入網域別名排序功能

### v5.4.3 2025-07-17

-   後台連結路由接受 id，不再自動解析模型，支援自定義 Link 模型
-   `BackendController` 現為抽象類別以維持標準
-   `ModelManager` 的 get() 函式新增 `$option['name']` 支援
-   非清單中的網域將共用回退網站的快取
-   自動在 select 中加入排序欄位避免 SQL 錯誤
-   在 `WebsiteManager` 中對回退網站預先載入媒體
-   重寫 `TagManager` 的條件邏輯
-   更新更新日誌 API 路由名稱為 `api.v1.update.run`
-   將 Link 模型的描述欄位由輸入框改為 textarea
-   清理後台路由代碼，移除開頭斜線
-   `BackendController` 新增抽象 `getModelClass()` 方法
-   移除 `AnalyticController`

### v5.4.2 2025-05-04

-   `links` 資料表中的 `description` 欄位由 `string` 改為 `text`，支援更長內容
-   將 SQL 安裝檔移至 `wncms-core` 套件內的 `resources/installer/wncms.sql`，統一安裝來源
-   安裝流程優化，如有 `wncms.sql` 檔，將優先匯入資料庫結構與資料，否則使用 Artisan 指令安裝
-   修復在未建立首個網站且站群模式關閉時，頂部的網站設定按鈕出現錯誤的問題

### v5.4.1 2025-05-03

-   安裝流程優化，檢測是否有 `storage/app/installer/wncms.sql` ，加快首次安裝速度，避免安裝過程 Cloudflare 超時問題

### v5.4.0 2025-04-05

-   修正 Card 模型找不到狀態的問題
-   當啟用 `multi_website` 時，新增下拉選單可選擇網站模型
-   支援模型使用常數 `NAME_KEY` 自定名稱，避免如 `order` 混淆排序與訂單
-   更新後台側邊欄選單中的模型圖示
-   允許刪除網站模型（但至少保留一個）
-   權限支援批次刪除功能
-   新增 Channel、Click、Parameter 模型
-   暫時隱藏 Price 模型，每個價格應設定於對應的上層模型中
-   修正未定義的 `$display_name` 變數
-   移除未使用的 analytics API 路由
-   在大多數 migration 中新增資料表是否存在檢查
-   LinkManager 預載 media 資料
-   重構 CacheManager：現代語法並統一支援 tag 的 get/put/forget
-   重構 ModelManager：建立抽象基底類別以重用邏輯
-   PostManager 與 LinkManager 改為繼承 ModelManager，支援標籤與關鍵字篩選
-   支援 `wncms()->getModel('xxx')` 並可回退到 config/App/Models/Wncms/Models
-   增加 zh_TW 語系翻譯鍵（如 `bulk_create`, `channel`, `parameters`, `clickable_type` 等）
-   CardController 改用 `wncms()->getModel('user')` 動態取得使用者模型
-   `gto()` 可在不傳入 key 時回傳所有選項
-   新增多語言翻譯
-   修正錯字：`fontaweseom` → `fontawesome`，`operater` → `operator`，`Starer` → `Starter`
-   Tag 模型新增 `$tag->url` 存取器
-   更新後台使用者搜尋邏輯
-   廣告列表新增分頁與樣式優化
-   修正打包主題檔案時未包含語系檔案問題
-   新增 `wncms()->log($message, $level)` 可隨處呼叫的除錯函式
-   【重要】LinkManager 升級為繼承 ModelManager
-   修正後台快速連結刪除錯誤
-   `starter/form-items.blade.php` 新增 `repeater` 欄位
-   修正 `starter/form-items.blade.php` 中的 color 欄位錯誤
-   `form-items.blade.php` 新增開發者提示
-   修正廣告排序未更新問題
-   使用者登入與管理員登入頁面分離
-   更新 Post API

### v5.3.0 2025-03-30

-   後台頂部連結根據站群設定顯示，站群顯示網站列表，單站顯示網站設定和主題設定
-   修復廣告模型無法更新排序欄位的問題
-   允許更新網站模型的域名
-   新增超級管理員的系統設定，準備隱藏一些不常用的選項
-   更新後台側邊欄的主題列表圖示
-   新增主題檔案結構檢查功能
-   自訂的 ModelManager 將優先於套件內的 ModelManager 載入
-   TagManager 的 get() 函數允許傳入 null，以避免從主題選項取得 null 時發生錯誤
-   更新後台列表頁的狀態與布林欄位樣式，支援徽章或純文字顯示
-   將 getList() 函數的預設每頁筆數設為 0，即預設停用分頁功能
-   新增可包含於後台列表頁的模型批次更新按鈕 blade
-   更新 Link 模型的後台功能，允許批次更新狀態與排序

### v5.2.0 2025-02-04

-   新增多語系翻譯
-   程式碼清理
-   前台註冊用戶預設指派為 member 角色，可由主題選項調整
-   當網站模型無法透過目前網域找到時，讀取第一個網站的廣告資料
-   預先載入連結的翻譯資料
-   若依目前網域找不到網站模型，則讀取第一個網站的文章，可選擇隨機排序結果
-   預先載入網站模型的翻譯資料

### v5.1.3 2025-02-04

-   新增多語系翻譯

### v5.1.2 2025-02-04

-   改善後台快捷連結儲存邏輯，改為儲存 URL 而非路由名稱，以避免遺失參數
-   後台主題選項頁標題新增網站 ID 顯示

### v5.1.1 2025-01-28

-   將所有 gss 版本更新為 core_version

### v5.1.0 2025-01-28

-   修復更多選單項目資料無法儲存的問題
-   從可為空的欄位中移除 required 類別
-   預加載 Website 模型及其翻譯
-   新增 ThemeServiceProvider
-   使用當前網域來顯示頁面 URL，而非從 website 模型獲取
-   預加載 Post 模型的翻譯，允許 order 變數設為隨機
-   使 Link 模型可翻譯

### v5.0.0 2025-01-27

-   修復使用者頁面路由順序錯誤
-   在 Credit 模型中新增函式以新增或獲取 Credit 額度
-   在 User 模型中新增函式以直接檢查 Credit 額度
-   允許 TinyMCE 選擇器為類別，將 GIF 新增至允許的圖片格式
-   重新啟用使用前台登入頁面登入後台的功能
-   在運行時動態修改 CSRF 規則
-   更新 wncms-translatable 的相依性
-   修復更新後選單項目翻譯遺失的問題
-   允許 gto() 配置當值為空時是否回退
-   新增前台文章的 CRUD（建立、讀取、更新、刪除）功能

### v4.5.0 2024-09-30

-   修復分頁 URL 被快取時包含語系的問題
-   新增外掛系統
-   提升 PostHelper 的效能
-   更新測試
-   新增 `LOCALIZATION_HIDE_DEFAULT_LOCALE` 環境變數

### v4.4.0 2024-09-18

-   添加了後端的更新通知功能。
-   更新了模型創建函數，以確保與自定義模型和控制器的兼容性。
-   修正了模型分頁，以準確顯示最後一頁的項目數量。
-   在設置中添加了禁用核心更新的選項。
-   在 `README.md` 中包含了演示 URL。
-   更新了錯誤頁面。
-   添加了翻譯。
-   引入了更新設置的命令。
-   添加了主題控制器。

### v4.3.0 2024-09-14

-   測試新的安裝過程
-   停止追蹤 vendor 目錄
-   更新後台頁腳年份為動態更新
-   在安裝前將預設快取驅動程式更新為 null
-   更新 README.md
-   改進預設模板
-   更新安裝程式圖示。安裝時自動添加強制 HTTPS 設定
-   向安裝程式添加錯誤訊息
-   在安裝過程中添加選擇 Redis、Memcache、文件作為快取驅動程式的選項
-   將預設 DB_CONNECTION 設為內存中的假 SQLite。將預設 SESSION 驅動程式設為文件
-   禁用安裝過程中的 trait 文件生成
-   在系統設定中添加強制 HTTPS 選項
-   添加預設 env 文件
-   更新 .gitignore
-   更新 composer 依賴
-   更新主題選項面板
-   改進首次網站創建流程
-   移除調試訊息
-   修正後台樣式
-   重命名 Traits。修正安裝錯誤。添加強制 HTTPS 相容性
-   在要求之前檢查自定義語言文件是否存在

### v4.2.0 2024-09-13

-   移除 WnTagTraits.php
-   在生成示例文章內容時添加翻譯
-   改進預設首頁模板。添加文章存檔 URL
-   添加更多翻譯
-   修正添加第二個網站時的錯誤

### v4.1.0 2024-09-07

-   添加強制 HTTPS 選項
-   小幅修正
-   更新預設 env 文件以隱藏調試訊息
-   安裝和網站創建後清除快取
-   添加 PHP 函數需求檢查
-   將 database.sqlite 添加到 .gitignore
-   修正創建主題命令。根據 Laravel 11 結構更新 lang 目錄
-   修正廣告過期日期不能為空的錯誤
-   移除無用的 UpdateLog 模型
-   更新預設模型設定
-   更新安裝程式
-   添加禁用用戶註冊功能
-   在網站編輯頁面上將主題設置為 null 而非預設

### v4.0.0 2024-08-03

#### 新增

-   PHP 需求從 7.2+ 升級至 8.2+
-   Laravel 10 -> 11
-   jQuery 3.2.1 -> 3.7.1
-   更新所有 composer 依賴
-   添加數據庫前綴以避免與 MySQL 保留關鍵字的表名衝突

#### 修復

-   修正當 CMS 未安裝時導航 panel/xxxx 頁面的錯誤
-   修正 php artisan migrate:fresh 不工作
-   修正當 $request-input 為 null 時頁面控制器錯誤

#### 優化

-   合併舊遷移並重命名遷移文件
-   重命名資料庫列 external_image -> external_thumbnail
-   更新預設 env 文件生成器
-   wncms_get_unique_slug() 將預設長度設為 8
-   所有 // 註釋後應留有空格。例如 // Good 和 //Bad
-   重寫 wncms.js 和 AuthenticatedSessionController 中的登入邏輯

#### 移除

-   移除 vendor 目錄以縮小包大小。安裝過程中將下載包
-   移除全域函數 wn()，全部替換為 wncms()
-   移除全域變數 $wn，全部替換為 $wncms
-   移除分析幫助程式以避免性能問題。用第三方分析工具替代
-   移除 wncms_get_theme_options() 幫助函數。在模型中使用 $website->get_options()

### v3.2.1 2024-05-19

#### 新增

-   新增數個旗次 SVG
-   增加更多翻譯字段
-   後台各模組數據列表加入權限判斷
-   後台新增菜單判斷非管理員權限
-   預留 1 個代理 Dashboard，供日後開發使用

#### 修復

-   修復部分大小寫錯誤

#### 優化

-   common.table_status.blade.php 可以判斷更多狀態，新增自定功能

### v3.2.0 2024-03-14

#### 修復

-   修復新版本安裝時 site_name 為 null 報錯

#### 優化

-   更新安裝程序
-   使用 api_token 請求時，視爲登入

### v3.1.15 2024-03-12

#### 新增

-   新增 Page 模組的 API
-   新增菜單模組 API
-   PostHelper getList() 支持 $isRandom 參數，可以獲取隨機文章
-   Post 模組新增 getTags ，獲取 Tag 列表，可傳入 $type 參數
-   WebsiteHelpr 新增排序功能
-   優化主題設定判斷可以分辨 0 和 未設定
-   新增一鍵複雜菜單功能

#### 修復

-   修復聯絡表單中 current_url 項被緩存問題
-   修復表單提交失敗時按鈕文字不顯示

#### 優化

-   計劃移除 Post 模組 getFirstCategory()，一律改為使用 getFirstTag('post_category')
-   優化手機版後台 UI
-   生成主題檔案時，會自動替換 starter 字串為主題名稱，無需再手動修改
-   主題名稱按英文字母順序排列
-   文章搜索前，先判斷是否包括 keyword
-   優化主題設定判斷，可以分辨 0 和 未設定
-   後台 Tag 顯示相關模組數量
-   所有 Helper 預設改為不使用分頁 $pageSize = 0

### v3.1.14 2023-12-17

#### 新增

-   批量複製文章功能

### v3.1.13 2023-12-12

#### 新增

-   模組列表的工具列，可以設定隱藏部分預設按鈕，例如 'hideToolbarStatusFiller' => true 可以隱藏狀態篩選下拉清單
-   聯絡表單增加提交後的 js 事件，可以針對成功及失敗分別設定
-   後台聯絡表單增加篩選功能
-   後台聯絡表單增加匯出 XLXS/CSV 功能
-   增加翻譯字段

#### 修復

-   修復假瀏覽量小數問題

#### 優化

-   調整部分後台 UI

### v3.1.12 2023-12-11

#### 新增

-   系統設定中，加入上傳封面圖片時，自動轉換為 webp
-   模組加入額外參數功能 ExtraAttribute，使用 getExtraAttribute() 和 saveExtraAttribute()

#### 修復

-   修復主題設定中，開關的說明有時候會重覆顯示

### v3.1.11 2023-12-08

#### 新增

-   更多翻譯字段

#### 修復

-   修復主題設定圖片若帶括號()未能顯示

#### 優化

-   utm 參數支持帶到著陸以外的其他頁面

### v3.1.10 2023-12-08

#### 新增

-   聯絡表單支持調整欄位順序
-   新增數項系統設定，設定超級管理員收件帳號
-   新增 SMTP 測試電郵功能
-   更新部分翻譯字段
-   聯絡表單現支持單選及多選
-   聯絡表單現支持檢查是否所有必須填寫項目都已填寫

#### 修復

-   修復手機版主題設定因網站名稱過長而跑版
-   修復部分模組未能批量刪除
-   修復文章回收時，未有更新狀態

#### 優化

-   更新預設 Email 模版，支持 html

### v3.1.9 2023-12-06

#### 新增

-   新增 Starter 主題的 style.css 和 main.js

#### 修復

-   修復部分主題頁面的部件未能讀取

#### 優化

-   獲取文章時，如果指定了排除的分類 ID，也會包括沒有分類的文章
-   更新表單系統，使用新的模版調用方法 $wncms->contact_form()->render()，直接生成表單結構
-   更新常見問題模組，增加更多欄位，可設定排序

### v3.1.8 2023-11-29

#### 新增

-   新增 json-id 結構

### v3.1.7 2023-11-21

#### 新增

-   優化後台手機版 UI
-   所有上傳圖片模組，支緩更多圖片類型
-   新增一鍵匯入預設主題設定功能

#### 修復

-   修復文章上限少於分頁數量時，可能不顯示的問題

#### 優化

-   升級主題設定中的文本編輯器
-   更新翻譯字段

### v3.1.6 2023-11-17

#### 新增

-   文章批量指派到網站功能
-   新增點擊事件紀錄，屬性為[wncms-click]
-   新增顯示網站瀏覽量 (有別於文章瀏覽量，網站瀏覽量為全局，查看多少文章都只計算 1 次)
-   更多翻譯字段
-   系統設定新增瀏覽量，訪問量冷卻間隔，可以設定 60 分鐘算一次，或 1440 分鐘計算一次，自行輸入分鐘
-   廣告位加入多篩選條件

### v3.1.5 2023-11-16

#### 新增

-   菜單項目可以新增描述
-   [開發者] 新增 Wiget 系統，用於建立 Page template，增加可重用性
-   [開發者] 初引入 Sortable JS 插件
-   [開發者] 後台 backend.blade.php 加入可堆疊 css 位置，使用@push('foot_css')
-   更多翻譯字段
-   新增一鍵生成演示文章功能，方便開發
-   [開發者] 新增第 4 個常用簡稱涵數 gpto (getPageTemplateOption)，用於在 page 頁面中，獲取個別頁面參數

#### 修復

-   修復 Ajax 聯絡表單返回訊息， 成功建立 改為 成功提交

#### 優化

-   頁面樊組 option 欄位由 json 類型改為 text 類型，防止自動排序
-   修改單頁加載順序， template > plain > static ，都沒有就跳轉主頁
-   修正以\#開頭的菜單連結未能正常跳轉錨點
-   修化手機版後台主題設定樣式
-   [開發者] 大幅修改後台 input.blade.php 的判斷條件，為可視化編輯器作準備

### v3.1.4 2023-11-04

#### 新增

-   [開發者] 模版中新增 $wncms 參數，等同於 $wn
-   [開發者] 新增 wncms()->paginateWithLimit() 方法，解決 paginate 不能與 limit 同時使用的問題
-   更多翻譯字
-   緩存讚好數，每日使用排程任務更新到模組 like 欄位，可加速不需要實時讚好數的模組
-   更新 RecordViews 排程任務，可以紀錄任何 collection，例如 visit, like, dislike
-   [開發者] 新增 $wn->addVersion()，不需要再於模版中加上 '?v=' . $wn->getVersion()，新功能輸出時已包括 ?v=
-   引入 applicaion/ld+json 選項，可以 Push 到 `<head>` 中

#### 修復

-   修復 3.1.3 更新後，部分頁面未能讀取內容

#### 優化

-   預設主題 `<title>` 從「首頁」改為網站名稱
-   優化新增同名權限時的提示，不再是報錯，而是提示已有重覆項目
-   [開發者] 重新命名前端視圖中的統計部分 get_view -> get, record_like -> like, record_view -> record

### v3.1.3 2023-10-31

#### 新增

-   更新後台菜單模組圖標
-   新增從文字中提取域名
-   廣告更換為可以設定永久廣告
-   [開發者] 新增頁面模版功能，開發者可建立 Blade 檔案，用於頁面管理中，一鍵生成頁面
-   修復顏色表選項不顯示當前值
-   新增菜單 Tag 項目可以一鍵獲取多國語言功能，獲取後仍然可以自訂各語言顯示文字
-   新增更多預設廣告位置
-   [開發者] 新增日期標示模塊 resources/views/common/table_date.blade.php ，根據日期與當前時間的距離，以不同顏色標示
-   [開發者] 新判斷某連結是否與當前頁面連結相同，wncms()->isActiveUrl()
-   新增更多翻字段
-   新增文章排行榜功能 /psots/rank, /post/rank/{$period} ，可以按日週月年總排列
-   [開發者] 新增 WnMediaTraits，暫時只有 1 個功能，$this->addThumbnailFromRequest()，可以直接從 request 中上傳圖片
-   [開發者] WnTgTraits 新增 getFirstTag() 功能，以獲取任何 Model 的第一個 Tag
-   [開發者] CacheHelper 新 get() 和 put() 功能
-   [開發者] MenuHelper 新增以輸出 theme_page 連結，與 external_link 差不多
-   新增一鍵複製廣告功

#### 修復

-   [開發者] 修復更新 Laravel 版本後，Stub 文件被覆蓋，導致模組生成器模版還原為設設
-   修復會員後面版錯誤
-   修復開啟緩存時，Tag 分頁只顯示第 1 頁緩存問題
-   修復部分模組，管理員沒有初始權限問題
-   修復菜單項目更新後，排序可能會亂掉問題

#### 優化

-   [開發者] 優代從字段中獲取連結功能，可選擇保留 www， wncms()->getDomainFromString($string, $includePort = true, $preserveWWW = false)

### v3.1.2 2023-10-28

#### 新增

-   使用新的 Redis 緩存庫，可以清理個別標籤
-   新增後台一鍵清理緩存功能
-   [開發者] 新增從 String 獲取域名+端口的方法，wncms()->getDomainFromString()

#### 優化

-   [開發者] 整理代碼
-   [開發者] Theme helper 修改為不受緩存設定影響，即使關閉緩存，仍然會套用數據緩存的時間，預設為 3600 秒，以防閉閉緩存後，一個頁面請求幾十次數據庫只為了讀取主題設定
-   [開發者] 更新 CSRF 路徑
-   修復主題設定中，Tinymce 編輯器無效
-   [開發者] TagHelper 中，getList() 新增可以只獲取父級分 Tag
-   更新文章 AP
-   更新 WNCMS 安裝程序，簡化流程

### v3.1.1 2023-10-20

#### 新增

-   重製安裝流程，更簡單快速
-   [開發者] Starter 中的 tagify 欄位，改為可以輸入開關字搜索
-   [開發者] 開發者可以自訂管理員控制台項目
-   [開發者] 開發者可以自訂會員主頁項目
-   添加更多翻譯字段

#### 修復

-   修復部分模組未能清除標籤

#### 優化

-   [開發者] 後台 Sidebar Model 部分重構，開發者可以自訂後台 Model 菜單順序，修改 Model 中的$menuPriority property
-   [開發者] 更新 controller.model.stub
-   重寫註冊會員邏輯

### v3.0.8 2023-10-18

#### 新增

-   [開發者] 更新 Wncms core class，可以任意配額參數 $wn->anything = 'something';
-   [開發者] 更新 Wncms core class，更有效重用 Helper Classes

### v3.0.7 2023-10-17

#### 新增

-   聯絡表單新增批量處理功能，可以批量設為已讀，批量設為未讀，批量設為已回覆
-   新增網站級的緩存
-   新增備用域名系統
-   部分後台頁面開始增加數據數量資訊，例如總數，及當前正在顯示第幾條數據
-   新增廣告模組

#### 優化

-   更新 Ajax 按鈕發送數據模式
-   優化系統設定緩存
-   升級 tinymce 編輯器，使用 cloudflare CDN

### v3.0.6 2023-10-09

#### 新增

-   新增更多翻譯字段
-   [開發者] 新增 table_url Blade 視圖
-   [開發者] webphp 路遊新增演示模組
-   [開發者] starter/index.bade.php 視圖新增再製按鈕
-   [開發者] Tag 模組新增 WnTagTraits 特徵，增 getAvailableTypes()，可以獲取分類法列表 ['post_tag', 'video_category', etc]

### v3.0.5 2023-10-07

#### 新增

-   檢查自定模組是否存在 (Custom{Model}Traits.php)

### v3.0.4 2023-10-07

#### 修復

-   修復部分模組小問題

#### 優化

-   優化後台 UI

### v3.0.3 2023-10-06

#### 優化

-   更新文章模組，修復會員權限問題
-   移動 Trait 目錄到 Ap 目錄下

### v3.0.2 2023-09-30

#### 新增

-   新增 Page 類型 (純文字類型，由編輯器製作的模版，由主題提供的模版)

### 2.0.0 2021-07-28

-   全新版本發行! (相關更新紀錄已隱藏)

### 1.0.0 2019-03-01

-   首版本發行 (相關更新紀錄已隱藏)
