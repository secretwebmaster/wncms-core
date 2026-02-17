# Pages

This guide explains how non-technical staff can manage pages from the backend UI.


## Where To Manage Pages

In backend menu:

1. Go to **Pages List**
2. Use top filters (`Search`, `Show Details`, `Show Thumbnail`) when needed


## Common Daily Tasks

## 1) Create a New Page

1. Open **Pages List**
2. Click **Add Pages**
3. In `Basic` tab, fill:
- `Title`
- `Slug (Show in URL)`
- `Type` (`plain`, `template`, or other available type)
4. If type is `template`, choose a template in `Available Page Templates`
5. In right panel (`Publish Related`), set:
- `Status`
- `Visibility`
6. Click the main submit button (`Publish`)

Expected result:

- New page appears in **Pages List**
- Preview button becomes available after save

## 2) Edit Page Content

1. In **Pages List**, click **Edit**
2. Update `Title`, `Content`, `Remark`, or template options
3. Click submit button (`Update`)

Expected result:

- Page updates are saved
- Preview opens latest content

## 3) Configure Page Attributes

1. Open page edit screen
2. In right panel, find **Page Attribute**
3. Toggle options such as `hide_title`
4. Click submit button

Expected result:

- Selected display options apply on frontend page rendering

## 4) Bulk Delete Pages

1. Tick target rows in **Pages List**
2. Click **Bulk Delete**
3. Confirm dialog

Expected result:

- Selected pages are removed


## Troubleshooting

## Page URL does not open

- Check `Slug` is not empty and has no conflict
- Check `Status` and `Visibility`
- Use `Preview` button to verify backend save first

## Template options tab is missing

- `Template Options` tab appears only when `Type` is `template`
- Save once after switching type, then reopen edit if needed
