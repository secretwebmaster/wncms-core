# Tags (Categories and Labels)

This guide is for backend staff who maintain website content.

Use Tags to:

- Group articles/pages/products into categories
- Add searchable labels
- Keep content organized for visitors


## Where To Manage Tags

In backend menu:

1. Go to **Tag** in sidebar
2. Use the **Category Type** filter at top (for example: post category, product category, FAQ category)

If your site uses plugins (for example ecommerce or FAQs), you will also see those plugin tag types in the list.


## Common Daily Tasks

## 1) Create a New Tag

1. Open the **Tag** page
2. Select the correct **Tag Type**
3. Click **Create Current Tag Type**
4. Fill:
- `Name`: the display name
- `Slug`: URL-friendly name (optional, can auto-use name)
- `Parent Tag`: choose parent if this is a child category
- `Order`: larger number usually appears first
5. Save

Expected result:

- New tag appears in tag list
- Child tag appears under its parent

## 2) Edit a Tag

1. In tag list, click **Edit**
2. Update name/slug/description/order/icon/image if needed
3. Save

Expected result:

- Updated values appear immediately in tag list and related forms

## 3) Add Child Tags (Sub-categories)

1. In tag list, find the parent tag
2. Click **Add Subtag**
3. Fill and save

Expected result:

- Child tag appears indented under parent

## 4) Bulk Create Tags

1. Click **Bulk Create Category**
2. Follow the input format shown on page
3. Submit

Expected result:

- Multiple tags are created in one action

## 5) Move Tags To Another Parent (Bulk)

1. In tag list, tick multiple tags
2. Click **Bulk Assign Parent Category**
3. Select target parent
4. Submit

Expected result:

- Selected tags are moved under the new parent


## Keyword Binding (Auto Match)

Use this only when you need automatic tag matching by text content.

1. Click **Bind Keywords**
2. Choose a tag type
3. Click **Edit Keyword** on a tag
4. Set:
- `Field`: which content field to match (`title`, `content`, or `*` for all)
- `Keywords`: words that should trigger this tag
5. Save

Expected result:

- Keyword rules are saved for that tag
- Auto-tag flow can use these keywords later


## Practical Rules For Staff

- Use one naming style for each tag type (consistent language/case)
- Avoid duplicate tags with same meaning
- Keep category trees simple (2-3 levels is usually enough)
- Use slug in lowercase with hyphens (example: `company-news`)
- Check tag type before creating (do not mix post/product/faq types)


## Troubleshooting

## I cannot find the tag type I need

- Confirm you selected the correct type filter in toolbar
- Confirm the related model/plugin is enabled by admin
- Refresh page after admin activates plugin/models

## Parent tag cannot be selected

- Parent must be the same tag type
- A tag cannot be parent of itself or its own child

## Tag created but not visible in expected place

- Check current type filter
- Check parent/child structure
- Check order value and sorting
