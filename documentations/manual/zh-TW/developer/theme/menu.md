# 主題選單

## 概述

WNCMS 提供強大的選單系統，讓您可以為主題建立導航選單。選單可以分配到不同位置（頁首、頁尾、側邊欄），並支援多層級階層結構及自訂樣式和行為。

## 選單結構

### Menu 模型

每個選單包含：

- **Menu**：容器（例如「主導航」、「頁尾連結」）
- **Menu Items**：選單內的個別連結
- **Hierarchy**：子選單的父子關係

### 資料庫結構

**Menus 表：**

```php
- id
- name (可翻譯)           // 選單名稱
- slug                     // 唯一識別碼
- status                   // active/inactive
- created_at
- updated_at
```

**Menu Items 表：**

```php
- id
- menu_id                  // 父選單
- parent_id                // 父項目（用於子選單）
- name (可翻譯)            // 顯示文字
- display_name (可翻譯)    // 替代顯示名稱
- description (可翻譯)
- url                      // 連結網址
- url_type                 // route/external/page/post/custom
- route_name               // Laravel 路由名稱
- page_id                  // 連結頁面 ID
- post_id                  // 連結文章 ID
- is_new_window            // 在新視窗開啟
- css_class                // 自訂 CSS 類別
- icon                     // 圖示類別（例如 FontAwesome）
- sort                     // 顯示順序
- status                   // active/inactive
- created_at
- updated_at
```

## 在後台建立選單

### 1. 建立選單

在 WNCMS 後台：

1. 導航至**外觀 → 選單**
2. 點擊**建立新選單**
3. 輸入選單詳細資訊：
   - 名稱：「主導航」
   - 別名：「main-nav」
4. 點擊**儲存**

### 2. 新增選單項目

對於每個選單項目：

1. 選擇要編輯的選單
2. 點擊**新增選單項目**
3. 配置項目：
   - **名稱**：顯示文字（例如「首頁」、「關於」）
   - **URL 類型**：選擇連結類型
     - `route`：Laravel 路由名稱
     - `page`：連結到頁面
     - `post`：連結到文章
     - `external`：外部網址
     - `custom`：自訂網址
   - **URL/路由**：根據類型而定
   - **父項目**：選擇父項目以建立子選單
   - **順序**：顯示位置
   - **新視窗**：在新分頁開啟
   - **圖示**：圖示類別（選填）
   - **CSS 類別**：自訂樣式（選填）

### 3. 組織階層

- 拖放項目以重新排序
- 將項目巢狀至父項目下以建立子選單
- 建議最多 3 層深度
- 父層項目會在項目列內顯示摺疊切換圖示（`fa-caret-right` / `fa-caret-down`）用於展開與收合。
- 子層級排序在重新渲染後可保持穩定結構，僅更新 Nestable 根層列表。
- 當標籤存在性 API 回傳空值或非預期 `ids` 時，檢查會安全降級，不會阻斷選單編輯器 UI 控件。
- 選單項目編輯輸入（彈窗與列表行內控件）現使用明確且唯一的 `id`/`name`，並確保 `label for` 對應正確，同時補齊 `autocomplete` 提示，以降低瀏覽器自動填寫與無障礙警告。

## 在主題中使用選單

### 主題配置

在 `config.php` 中定義選單位置：

```php
return [
    'option_tabs' => [
        'layout' => [
            [
                'label' => '頁首選單',
                'name' => 'header_menu',
                'type' => 'select',
                'options' => 'menus',
                'description' => '選擇頁首導航選單',
            ],
            [
                'label' => '頁尾選單',
                'name' => 'footer_menu',
                'type' => 'select',
                'options' => 'menus',
                'description' => '選擇頁尾連結選單',
            ],
            [
                'label' => '行動選單',
                'name' => 'mobile_menu',
                'type' => 'select',
                'options' => 'menus',
                'description' => '選擇行動導航選單',
            ],
        ],
    ],

    'default' => [
        'header_menu' => 1,    // 預設選單 ID
        'footer_menu' => 2,
    ],
];
```

### 基本選單顯示

**簡單選單（無子選單）：**

```blade
@if(gto('header_menu'))
    <nav class="main-menu">
        <ul>
            @foreach(wncms()->menu()->getMenuParentItems(gto('header_menu')) as $menuItem)
                @php
                    $menuItemUrl = wncms()->menu()->getMenuItemUrl($menuItem);
                @endphp

                <li class="{{ $menuItem->css_class }}">
                    <a href="{{ $menuItemUrl }}"
                       @if($menuItem->is_new_window) target="_blank" @endif
                       class="@if(wncms()->isActiveUrl($menuItemUrl)) active @endif">

                        @if($menuItem->icon)
                            <i class="{{ $menuItem->icon }}"></i>
                        @endif

                        {{ $menuItem->name }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
```

### 帶子選單的選單

**兩層選單：**

```blade
@if(gto('header_menu'))
    <nav class="main-menu">
        <ul class="menu-list">
            @foreach(wncms()->menu()->getMenuParentItems(gto('header_menu')) as $menuItem)
                @php $menuItemUrl = wncms()->menu()->getMenuItemUrl($menuItem); @endphp

                <li class="menu-item {{ $menuItem->css_class }}
                           @if($menuItem->children->count()) has-submenu @endif">

                    <a href="{{ $menuItemUrl }}"
                       class="menu-link @if(wncms()->isActiveUrl($menuItemUrl)) active @endif"
                       @if($menuItem->is_new_window) target="_blank" @endif>

                        @if($menuItem->icon)
                            <i class="{{ $menuItem->icon }}"></i>
                        @endif

                        {{ $menuItem->name }}

                        @if($menuItem->children->count())
                            <i class="fas fa-chevron-down"></i>
                        @endif
                    </a>

                    {{-- 子選單 --}}
                    @if($menuItem->children->count())
                        <ul class="submenu">
                            @foreach($menuItem->children as $subMenuItem)
                                @php $subMenuItemUrl = wncms()->menu()->getMenuItemUrl($subMenuItem); @endphp

                                <li class="submenu-item {{ $subMenuItem->css_class }}">
                                    <a href="{{ $subMenuItemUrl }}"
                                       class="submenu-link @if(wncms()->isActiveUrl($subMenuItemUrl)) active @endif"
                                       @if($subMenuItem->is_new_window) target="_blank" @endif>

                                        @if($subMenuItem->icon)
                                            <i class="{{ $subMenuItem->icon }}"></i>
                                        @endif

                                        {{ $subMenuItem->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
@endif
```

### 多層選單（遞迴）

**用於深層階層：**

```blade
@if(gto('header_menu'))
    @php
        $menuItems = wncms()->menu()->getMenuParentItems(gto('header_menu'));
    @endphp

    <nav class="main-menu">
        @include("$themeId::components.menu-items", ['items' => $menuItems, 'level' => 1])
    </nav>
@endif
```

**components/menu-items.blade.php：**

```blade
<ul class="menu-level-{{ $level }}">
    @foreach($items as $menuItem)
        @php
            $menuItemUrl = wncms()->menu()->getMenuItemUrl($menuItem);
            $hasChildren = $menuItem->children->count() > 0;
        @endphp

        <li class="menu-item {{ $menuItem->css_class }} @if($hasChildren) has-children @endif">
            <a href="{{ $menuItemUrl }}"
               class="menu-link @if(wncms()->isActiveUrl($menuItemUrl)) active @endif"
               @if($menuItem->is_new_window) target="_blank" @endif>

                @if($menuItem->icon)
                    <i class="{{ $menuItem->icon }}"></i>
                @endif

                {{ $menuItem->name }}

                @if($hasChildren)
                    <i class="fas fa-angle-right"></i>
                @endif
            </a>

            @if($hasChildren)
                @include("$themeId::components.menu-items", [
                    'items' => $menuItem->children,
                    'level' => $level + 1
                ])
            @endif
        </li>
    @endforeach
</ul>
```

## 選單輔助函數

### 取得選單項目

```php
// 僅取得頂層選單項目
$parentItems = wncms()->menu()->getMenuParentItems($menuId);

// 取得所有選單項目（包括子項目）
$menu = wncms()->menu()->get(['id' => $menuId]);
$allItems = $menu->menu_items;

// 取得選單的直接子項目
$directItems = $menu->direct_menu_items;
```

### 取得選單項目 URL

```php
$menuItemUrl = wncms()->menu()->getMenuItemUrl($menuItem);
```

此函數處理不同的 URL 類型：

- **route**：從路由名稱生成 URL
- **page**：透過 ID 連結到頁面
- **post**：透過 ID 連結到文章
- **external**：回傳外部 URL
- **custom**：回傳自訂 URL

### 檢查啟用 URL

```blade
@if(wncms()->isActiveUrl($menuItemUrl))
    {{-- 當前頁面 --}}
@endif
```

## 選單樣式

### 基本 CSS

```css
/* 主選單容器 */
.main-menu {
  display: flex;
  align-items: center;
}

/* 選單列表 */
.menu-list {
  display: flex;
  list-style: none;
  margin: 0;
  padding: 0;
}

/* 選單項目 */
.menu-item {
  position: relative;
  margin: 0 15px;
}

/* 選單連結 */
.menu-link {
  display: block;
  padding: 10px 15px;
  text-decoration: none;
  color: #333;
  transition: all 0.3s;
}

.menu-link:hover,
.menu-link.active {
  color: #007bff;
}

/* 子選單 */
.submenu {
  position: absolute;
  top: 100%;
  left: 0;
  display: none;
  background: #fff;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  list-style: none;
  margin: 0;
  padding: 10px 0;
  min-width: 200px;
}

.menu-item:hover > .submenu {
  display: block;
}

.submenu-item {
  margin: 0;
}

.submenu-link {
  display: block;
  padding: 10px 20px;
  color: #333;
  text-decoration: none;
}

.submenu-link:hover {
  background: #f5f5f5;
  color: #007bff;
}
```

### 行動選單

```css
/* 行動選單切換 */
.menu-toggle {
  display: none;
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
}

@media (max-width: 768px) {
  .menu-toggle {
    display: block;
  }

  .menu-list {
    display: none;
    flex-direction: column;
    width: 100%;
    position: absolute;
    top: 100%;
    left: 0;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }

  .menu-list.active {
    display: flex;
  }

  .menu-item {
    margin: 0;
    border-bottom: 1px solid #eee;
  }

  .submenu {
    position: static;
    box-shadow: none;
    background: #f5f5f5;
  }
}
```

**切換腳本：**

```javascript
// 行動選單切換
$('.menu-toggle').on('click', function () {
  $('.menu-list').toggleClass('active')
})

// 點擊外部時關閉選單
$(document).on('click', function (e) {
  if (!$(e.target).closest('.main-menu').length) {
    $('.menu-list').removeClass('active')
  }
})
```

## 進階功能

### 帶圖示的選單

```blade
@foreach($menuItems as $menuItem)
    <li class="menu-item">
        <a href="{{ wncms()->menu()->getMenuItemUrl($menuItem) }}">
            @if($menuItem->icon)
                <i class="{{ $menuItem->icon }}"></i>
            @endif
            <span>{{ $menuItem->name }}</span>
        </a>
    </li>
@endforeach
```

**圖示選單的 CSS：**

```css
.menu-link {
  display: flex;
  align-items: center;
  gap: 8px;
}

.menu-link i {
  font-size: 18px;
}
```

### 超級選單

用於複雜的多欄選單：

```blade
@foreach($menuItems as $menuItem)
    @if($menuItem->children->count())
        <li class="menu-item has-megamenu">
            <a href="{{ wncms()->menu()->getMenuItemUrl($menuItem) }}">
                {{ $menuItem->name }}
            </a>

            <div class="megamenu">
                <div class="megamenu-grid">
                    @foreach($menuItem->children as $subMenuItem)
                        <div class="megamenu-column">
                            <h4>{{ $subMenuItem->name }}</h4>

                            @if($subMenuItem->children->count())
                                <ul>
                                    @foreach($subMenuItem->children as $grandchild)
                                        <li>
                                            <a href="{{ wncms()->menu()->getMenuItemUrl($grandchild) }}">
                                                {{ $grandchild->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </li>
    @endif
@endforeach
```

**超級選單 CSS：**

```css
.megamenu {
  position: absolute;
  top: 100%;
  left: 0;
  display: none;
  background: #fff;
  box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
  padding: 30px;
  min-width: 600px;
}

.has-megamenu:hover .megamenu {
  display: block;
}

.megamenu-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 30px;
}

.megamenu-column h4 {
  margin: 0 0 15px;
  font-size: 16px;
  font-weight: 600;
}

.megamenu-column ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

.megamenu-column li {
  margin: 5px 0;
}
```

### 從選單產生麵包屑

```blade
@php
    $breadcrumbs = [];
    // 從當前選單項目建立麵包屑軌跡的邏輯
@endphp

@if(count($breadcrumbs) > 0)
    <nav class="breadcrumb">
        <a href="{{ route('frontend.pages.home') }}">首頁</a>
        @foreach($breadcrumbs as $crumb)
            <span class="separator">/</span>
            <a href="{{ $crumb->url }}">{{ $crumb->name }}</a>
        @endforeach
    </nav>
@endif
```

## 最佳實踐

### 1. 選單深度

為了可用性，限制選單階層為 2-3 層：

```blade
@if($level <= 3)
    {{-- 渲染子選單 --}}
@else
    {{-- 不渲染更深層級 --}}
@endif
```

### 2. 效能

快取選單查詢以獲得更好的效能：

```php
$menuItems = Cache::remember("menu_{$menuId}_items", 3600, function() use ($menuId) {
    return wncms()->menu()->getMenuParentItems($menuId);
});
```

### 3. 無障礙性

為螢幕閱讀器新增 ARIA 屬性：

```blade
<nav class="main-menu" aria-label="主導航">
    <ul role="menubar">
        @foreach($menuItems as $menuItem)
            <li role="none">
                <a href="{{ wncms()->menu()->getMenuItemUrl($menuItem) }}"
                   role="menuitem"
                   @if($menuItem->children->count())
                       aria-haspopup="true"
                       aria-expanded="false"
                   @endif>
                    {{ $menuItem->name }}
                </a>

                @if($menuItem->children->count())
                    <ul role="menu" aria-label="{{ $menuItem->name }} 子選單">
                        {{-- 子選單項目 --}}
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
```

### 4. SEO 友善

使用描述性連結文字和適當結構：

```blade
{{-- 好 --}}
<a href="/services/web-development">網頁開發服務</a>

{{-- 避免 --}}
<a href="/services/web-development">點擊這裡</a>
```

## 疑難排解

### 選單未顯示

1. 檢查選單 ID 是否在主題選項中設定
2. 驗證選單有啟用的項目
3. 檢查 `gto('header_menu')` 是否回傳有效的 ID
4. 確保選單項目的 `status = 'active'`

### 網址不正確

1. 驗證 `url_type` 設定正確
2. 檢查路由名稱是否存在於 `routes/web.php`
3. 確保頁面/文章 ID 有效
4. 測試外部網址

### 子選單未顯示

1. 檢查資料庫中的父子關係
2. 驗證子選單可見性的 CSS
3. 測試滑鼠懸停/點擊 JavaScript 事件
4. 檢查重疊元素的 z-index

## 另請參閱

- [主題結構](./theme-structure.md)
- [主題配置](./config.md)
- [Frontend Controller](../controller/frontend-controller.md)
