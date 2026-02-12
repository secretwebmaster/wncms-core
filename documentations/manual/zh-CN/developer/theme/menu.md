# 主题选单

## 概述

WNCMS 提供强大的选单系统，让您可以为主题建立导航选单。选单可以分配到不同位置（页首、页尾、侧边栏），并支援多层级阶层结构及自订样式和行为。

## 选单结构

### Menu 模型

每个选单包含：

- **Menu**：容器（例如「主导航」、「页尾连结」）
- **Menu Items**：选单内的个别连结
- **Hierarchy**：子选单的父子关系

### 资料库结构

**Menus 表：**

```php
- id
- name (可翻译)           // 选单名称
- slug                     // 唯一识别码
- status                   // active/inactive
- created_at
- updated_at
```

**Menu Items 表：**

```php
- id
- menu_id                  // 父选单
- parent_id                // 父项目（用于子选单）
- name (可翻译)            // 显示文字
- display_name (可翻译)    // 替代显示名称
- description (可翻译)
- url                      // 连结网址
- url_type                 // route/external/page/post/custom
- route_name               // Laravel 路由名称
- page_id                  // 连结页面 ID
- post_id                  // 连结文章 ID
- is_new_window            // 在新视窗开启
- css_class                // 自订 CSS 类别
- icon                     // 图示类别（例如 FontAwesome）
- sort                     // 显示顺序
- status                   // active/inactive
- created_at
- updated_at
```

## 在后台建立选单

### 1. 建立选单

在 WNCMS 后台：

1. 导航至**外观 → 选单**
2. 点击**建立新选单**
3. 输入选单详细资讯：
   - 名称：「主导航」
   - 别名：「main-nav」
4. 点击**储存**

### 2. 新增选单项目

对于每个选单项目：

1. 选择要编辑的选单
2. 点击**新增选单项目**
3. 配置项目：
   - **名称**：显示文字（例如「首页」、「关于」）
   - **URL 类型**：选择连结类型
     - `route`：Laravel 路由名称
     - `page`：连结到页面
     - `post`：连结到文章
     - `external`：外部网址
     - `custom`：自订网址
   - **URL/路由**：根据类型而定
   - **父项目**：选择父项目以建立子选单
   - **顺序**：显示位置
   - **新视窗**：在新分页开启
   - **图示**：图示类别（选填）
   - **CSS 类别**：自订样式（选填）

### 3. 组织阶层

- 拖放项目以重新排序
- 将项目巢状至父项目下以建立子选单
- 建议最多 3 层深度

## 在主题中使用选单

### 主题配置

在 `config.php` 中定义选单位置：

```php
return [
    'option_tabs' => [
        'layout' => [
            [
                'label' => '页首选单',
                'name' => 'header_menu',
                'type' => 'select',
                'options' => 'menus',
                'description' => '选择页首导航选单',
            ],
            [
                'label' => '页尾选单',
                'name' => 'footer_menu',
                'type' => 'select',
                'options' => 'menus',
                'description' => '选择页尾连结选单',
            ],
            [
                'label' => '行动选单',
                'name' => 'mobile_menu',
                'type' => 'select',
                'options' => 'menus',
                'description' => '选择行动导航选单',
            ],
        ],
    ],

    'default' => [
        'header_menu' => 1,    // 预设选单 ID
        'footer_menu' => 2,
    ],
];
```

### 基本选单显示

**简单选单（无子选单）：**

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

### 带子选单的选单

**两层选单：**

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

                    {{-- 子选单 --}}
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

### 多层选单（递回）

**用于深层阶层：**

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

## 选单辅助函数

### 取得选单项目

```php
// 仅取得顶层选单项目
$parentItems = wncms()->menu()->getMenuParentItems($menuId);

// 取得所有选单项目（包括子项目）
$menu = wncms()->menu()->get(['id' => $menuId]);
$allItems = $menu->menu_items;

// 取得选单的直接子项目
$directItems = $menu->direct_menu_items;
```

### 取得选单项目 URL

```php
$menuItemUrl = wncms()->menu()->getMenuItemUrl($menuItem);
```

此函数处理不同的 URL 类型：

- **route**：从路由名称生成 URL
- **page**：透过 ID 连结到页面
- **post**：透过 ID 连结到文章
- **external**：回传外部 URL
- **custom**：回传自订 URL

### 检查启用 URL

```blade
@if(wncms()->isActiveUrl($menuItemUrl))
    {{-- 当前页面 --}}
@endif
```

## 选单样式

### 基本 CSS

```css
/* 主选单容器 */
.main-menu {
  display: flex;
  align-items: center;
}

/* 选单列表 */
.menu-list {
  display: flex;
  list-style: none;
  margin: 0;
  padding: 0;
}

/* 选单项目 */
.menu-item {
  position: relative;
  margin: 0 15px;
}

/* 选单连结 */
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

/* 子选单 */
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

### 行动选单

```css
/* 行动选单切换 */
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

**切换脚本：**

```javascript
// 行动选单切换
$('.menu-toggle').on('click', function () {
  $('.menu-list').toggleClass('active')
})

// 点击外部时关闭选单
$(document).on('click', function (e) {
  if (!$(e.target).closest('.main-menu').length) {
    $('.menu-list').removeClass('active')
  }
})
```

## 进阶功能

### 带图示的选单

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

**图示选单的 CSS：**

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

### 超级选单

用于复杂的多栏选单：

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

**超级选单 CSS：**

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

### 从选单产生面包屑

```blade
@php
    $breadcrumbs = [];
    // 从当前选单项目建立面包屑轨迹的逻辑
@endphp

@if(count($breadcrumbs) > 0)
    <nav class="breadcrumb">
        <a href="{{ route('frontend.pages.home') }}">首页</a>
        @foreach($breadcrumbs as $crumb)
            <span class="separator">/</span>
            <a href="{{ $crumb->url }}">{{ $crumb->name }}</a>
        @endforeach
    </nav>
@endif
```

## 最佳实践

### 1. 选单深度

为了可用性，限制选单阶层为 2-3 层：

```blade
@if($level <= 3)
    {{-- 渲染子选单 --}}
@else
    {{-- 不渲染更深层级 --}}
@endif
```

### 2. 效能

快取选单查询以获得更好的效能：

```php
$menuItems = Cache::remember("menu_{$menuId}_items", 3600, function() use ($menuId) {
    return wncms()->menu()->getMenuParentItems($menuId);
});
```

### 3. 无障碍性

为萤幕阅读器新增 ARIA 属性：

```blade
<nav class="main-menu" aria-label="主导航">
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
                    <ul role="menu" aria-label="{{ $menuItem->name }} 子选单">
                        {{-- 子选单项目 --}}
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
```

### 4. SEO 友善

使用描述性连结文字和适当结构：

```blade
{{-- 好 --}}
<a href="/services/web-development">网页开发服务</a>

{{-- 避免 --}}
<a href="/services/web-development">点击这里</a>
```

## 疑难排解

### 选单未显示

1. 检查选单 ID 是否在主题选项中设定
2. 验证选单有启用的项目
3. 检查 `gto('header_menu')` 是否回传有效的 ID
4. 确保选单项目的 `status = 'active'`

### 网址不正确

1. 验证 `url_type` 设定正确
2. 检查路由名称是否存在于 `routes/web.php`
3. 确保页面/文章 ID 有效
4. 测试外部网址

### 子选单未显示

1. 检查资料库中的父子关系
2. 验证子选单可见性的 CSS
3. 测试滑鼠悬停/点击 JavaScript 事件
4. 检查重叠元素的 z-index

## 另请参阅

- [主题结构](./theme-structure.md)
- [主题配置](./config.md)
- [Frontend Controller](../controller/frontend-controller.md)
