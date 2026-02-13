# Theme Menus

## Overview

WNCMS provides a powerful menu system that allows you to create navigation menus for your theme. Menus can be assigned to different locations (header, footer, sidebar) and support multi-level hierarchical structures with custom styling and behavior.

## Menu Structure

### Menu Model

Each menu consists of:

- **Menu**: The container (e.g., "Main Navigation", "Footer Links")
- **Menu Items**: Individual links within the menu
- **Hierarchy**: Parent-child relationships for submenus

### Database Structure

**Menus Table:**

```php
- id
- name (translatable)       // Menu name
- slug                      // Unique identifier
- status                    // active/inactive
- created_at
- updated_at
```

**Menu Items Table:**

```php
- id
- menu_id                   // Parent menu
- parent_id                 // Parent item (for submenus)
- name (translatable)       // Display text
- display_name (translatable)  // Alternative display name
- description (translatable)
- url                       // Link URL
- url_type                  // route/external/page/post/custom
- route_name                // Laravel route name
- page_id                   // Linked page ID
- post_id                   // Linked post ID
- is_new_window             // Open in new window
- css_class                 // Custom CSS classes
- icon                      // Icon class (e.g., FontAwesome)
- sort                      // Display order
- status                    // active/inactive
- created_at
- updated_at
```

## Creating Menus in Backend

### 1. Create a Menu

In the WNCMS backend:

1. Navigate to **Appearance → Menus**
2. Click **Create New Menu**
3. Enter menu details:
   - Name: "Main Navigation"
   - Slug: "main-nav"
4. Click **Save**

### 2. Add Menu Items

For each menu item:

1. Select the menu to edit
2. Click **Add Menu Item**
3. Configure item:
   - **Name**: Display text (e.g., "Home", "About")
   - **URL Type**: Choose link type
     - `route`: Laravel route name
     - `page`: Link to a page
     - `post`: Link to a post
     - `external`: External URL
     - `custom`: Custom URL
   - **URL/Route**: Depending on type
   - **Parent**: Select parent for submenu
   - **Order**: Display position
   - **New Window**: Open in new tab
   - **Icon**: Icon class (optional)
   - **CSS Class**: Custom styling (optional)

### 3. Organize Hierarchy

- Drag and drop items to reorder
- Nest items under parents for submenus
- Up to 3 levels deep recommended
- Parent items now include an in-row collapse toggle icon (`fa-caret-right` / `fa-caret-down`) for expand/collapse.
- Sub-level sorting now keeps nested structure stable after rerender by updating only the root Nestable list.
- Tag existence checks now fail-safe when API returns empty or unexpected `ids`, so menu UI controls remain usable.
- Menu item editor inputs (modal + inline row controls) now use explicit unique `id`/`name`, matching label `for` links, and `autocomplete` hints for better browser autofill/accessibility compatibility.

## Using Menus in Themes

### Theme Configuration

Define menu locations in `config.php`:

```php
return [
    'option_tabs' => [
        'layout' => [
            [
                'label' => 'Header Menu',
                'name' => 'header_menu',
                'type' => 'select',
                'options' => 'menus',
                'description' => 'Select menu for header navigation',
            ],
            [
                'label' => 'Footer Menu',
                'name' => 'footer_menu',
                'type' => 'select',
                'options' => 'menus',
                'description' => 'Select menu for footer links',
            ],
            [
                'label' => 'Mobile Menu',
                'name' => 'mobile_menu',
                'type' => 'select',
                'options' => 'menus',
                'description' => 'Select menu for mobile navigation',
            ],
        ],
    ],

    'default' => [
        'header_menu' => 1,    // Default menu ID
        'footer_menu' => 2,
    ],
];
```

### Basic Menu Display

**Simple Menu (No Submenus):**

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

### Menu with Submenus

**Two-Level Menu:**

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

                    {{-- Submenu --}}
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

### Multi-Level Menu (Recursive)

**For Deep Hierarchies:**

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

**components/menu-items.blade.php:**

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

## Menu Helper Functions

### Get Menu Items

```php
// Get top-level menu items only
$parentItems = wncms()->menu()->getMenuParentItems($menuId);

// Get all menu items (including children)
$menu = wncms()->menu()->get(['id' => $menuId]);
$allItems = $menu->menu_items;

// Get direct children of menu
$directItems = $menu->direct_menu_items;
```

### Get Menu Item URL

```php
$menuItemUrl = wncms()->menu()->getMenuItemUrl($menuItem);
```

This function handles different URL types:

- **route**: Generates URL from route name
- **page**: Links to page by ID
- **post**: Links to post by ID
- **external**: Returns external URL
- **custom**: Returns custom URL

### Check Active URL

```blade
@if(wncms()->isActiveUrl($menuItemUrl))
    {{-- Current page --}}
@endif
```

## Styling Menus

### Basic CSS

```css
/* Main menu container */
.main-menu {
  display: flex;
  align-items: center;
}

/* Menu list */
.menu-list {
  display: flex;
  list-style: none;
  margin: 0;
  padding: 0;
}

/* Menu items */
.menu-item {
  position: relative;
  margin: 0 15px;
}

/* Menu links */
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

/* Submenu */
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

### Mobile Menu

```css
/* Mobile menu toggle */
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

**Toggle Script:**

```javascript
// Mobile menu toggle
$('.menu-toggle').on('click', function () {
  $('.menu-list').toggleClass('active')
})

// Close menu when clicking outside
$(document).on('click', function (e) {
  if (!$(e.target).closest('.main-menu').length) {
    $('.menu-list').removeClass('active')
  }
})
```

## Advanced Features

### Menu with Icons

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

**CSS for Icon Menus:**

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

### Mega Menu

For complex multi-column menus:

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

**Mega Menu CSS:**

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

### Breadcrumb from Menu

```blade
@php
    $breadcrumbs = [];
    // Logic to build breadcrumb trail from current menu item
@endphp

@if(count($breadcrumbs) > 0)
    <nav class="breadcrumb">
        <a href="{{ route('frontend.pages.home') }}">Home</a>
        @foreach($breadcrumbs as $crumb)
            <span class="separator">/</span>
            <a href="{{ $crumb->url }}">{{ $crumb->name }}</a>
        @endforeach
    </nav>
@endif
```

## Best Practices

### 1. Menu Depth

Limit menu hierarchy to 2-3 levels for usability:

```blade
@if($level <= 3)
    {{-- Render submenu --}}
@else
    {{-- Don't render deeper levels --}}
@endif
```

### 2. Performance

Cache menu queries for better performance:

```php
$menuItems = Cache::remember("menu_{$menuId}_items", 3600, function() use ($menuId) {
    return wncms()->menu()->getMenuParentItems($menuId);
});
```

### 3. Accessibility

Add ARIA attributes for screen readers:

```blade
<nav class="main-menu" aria-label="Main Navigation">
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
                    <ul role="menu" aria-label="{{ $menuItem->name }} submenu">
                        {{-- Submenu items --}}
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
```

### 4. SEO-Friendly

Use descriptive link text and proper structure:

```blade
{{-- Good --}}
<a href="/services/web-development">Web Development Services</a>

{{-- Avoid --}}
<a href="/services/web-development">Click Here</a>
```

## Troubleshooting

### Menu Not Displaying

1. Check if menu ID is set in theme options
2. Verify menu has active items
3. Check if `gto('header_menu')` returns valid ID
4. Ensure menu items have `status = 'active'`

### Incorrect URLs

1. Verify `url_type` is set correctly
2. Check route names exist in `routes/web.php`
3. Ensure page/post IDs are valid
4. Test external URLs

### Submenu Not Showing

1. Check parent-child relationships in database
2. Verify CSS for submenu visibility
3. Test hover/click JavaScript events
4. Check z-index for overlapping elements

## See Also

- [Theme Structure](./theme-structure.md)
- [Theme Configuration](./config.md)
- [Frontend Controller](../controller/frontend-controller.md)
