# Links

This guide explains how non-technical staff can manage links from the backend UI.


## Where To Manage Links

In backend menu:

1. Go to **Links List**
2. Use top filters (`Search`, category filter, `Show Details`) when needed


## Common Daily Tasks

## 1) Create a Link

1. Open **Links List**
2. Click **Add Links**
3. Fill required fields:
- `Status`
- `Name`
- `URL`
4. Optional but recommended:
- `Link Category`
- `Link Tag`
- `Remark`
- `Link Icon` / `Link Thumbnail`
5. Click **Create**

Expected result:

- New link appears in **Links List**

## 2) Edit a Link

1. In **Links List**, click **Edit**
2. Update fields (for example `Name`, `URL`, tags, icon, recommendation/pinned switches)
3. Click **Edit** to save

Expected result:

- Updated values appear in list and frontend link pages

## 3) Bulk Update Status or Recommendation

1. Tick multiple links in **Links List**
2. Use one of these buttons:
- `Set Active`
- `Set Inactive`
- `Set as Pinned`
- `Unset Pinned`
- `Set as Recommended`
- `Unset Recommended`
3. Confirm action

Expected result:

- All selected links are updated in one action

## 4) Bulk Update Link Tags

1. Tick multiple links
2. Click **Handle Link Tags**
3. In popup:
- choose `Action` (`attach`, `detach`, or `sync`)
- set `Category`
- set `Tag`
4. Click **Submit**

Expected result:

- Selected links have updated categories/tags

## 5) Bulk Field Editing

1. Tick target rows
2. Click **Bulk Edit**
3. Fill the fields you want to update
4. Submit

Expected result:

- Selected links are updated for the chosen fields


## Troubleshooting

## Link is not visible on frontend

- Check `Status` is active/published state
- Check `Expired At` is empty or in the future
- Check category/tag filters on frontend page

## Icon/thumbnail not updated

- Re-upload image and save again
- Confirm file type is supported (`png`, `jpg`, `jpeg`, `gif`)
