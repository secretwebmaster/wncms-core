# Posts

This guide explains how non-technical staff can manage posts from the backend UI.


## Where To Manage Posts

In backend menu:

1. Go to **Posts List**
2. Use top filters (`Search`, `Select Category`, `Show Details`, `Show Trash`, `Show Thumbnail`) when needed


## Common Daily Tasks

## 1) Create and Publish a Post

1. Open **Posts List**
2. Click **Add Posts**
3. Fill required fields:
- `Title`
- `Category`
- `Tag`
- `Slug (Show in URL)`
4. In right panel, set:
- `Status` (usually `Published`)
- `Visibility`
- `Published At`
5. Click the main submit button (`Publish`)

Expected result:

- Post appears in **Posts List**
- Post can be opened by frontend URL or preview

## 2) Edit an Existing Post

1. In **Posts List**, click **Edit** on target row
2. Update title/content/category/tag or publish settings
3. Click **Save**

Expected result:

- Changes are saved immediately
- Updated post details are visible in list and frontend preview

## 3) Bulk Delete or Permanent Delete

1. In **Posts List**, tick target rows
2. Click **Bulk Delete** (moves to trash) or **Bulk Permanent Delete** (irreversible)
3. Confirm in popup dialog

Expected result:

- Selected posts are removed according to action type

## 4) Bulk Assign/Remove Categories and Tags

1. Tick target rows in **Posts List**
2. Click **Bulk Bind Categories/Tags**
3. In popup:
- choose `Action` (`attach`, `detach`, or `sync`)
- set `Category`
- set `Tag`
4. Click **Submit**

Expected result:

- Selected posts receive updated category/tag bindings


## Troubleshooting

## I cannot find a post

- Clear filters (`Search`, category, status)
- Enable/disable `Show Trash` depending on where the post is
- Check pagination at bottom

## Publish button does not work

- Confirm required fields are filled (`Title`, `Category`, `Tag`, `Status`, `Visibility`)
- Confirm date/time format in `Published At` is valid
