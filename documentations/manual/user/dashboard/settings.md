# Settings

This guide explains how non-technical staff can change system settings from backend UI.


## Where To Manage Settings

In backend menu:

1. Open **System Settings**
2. Use left tab list to switch setting groups

Common tabs include:

- `Basic Settings`
- `Login Settings`
- `SMTP Settings`
- `Cloudflare Settings`
- `Cache Settings`
- `Social Login Settings`
- `Page Settings`
- `Collection Interface Settings`
- `Content Module Settings`
- `User Settings`
- `Admin Settings`
- `Analytics Settings`
- `Translation settings`
- `Multisite Settings` (if multi-site is enabled)
- `API Settings`
- `Developer Settings` (if developer mode is enabled)


## Common Daily Tasks

## 1) Change Basic Site Information

1. Open **System Settings**
2. Click `Basic Settings`
3. Update fields like site name/description/keywords
4. Click **Save All**

Expected result:

- Site-wide text/settings are updated

## 2) Enable/Disable a Setting Switch

1. Open the relevant tab (for example `User Settings`)
2. Turn target switch on/off
3. Click **Save All**

Expected result:

- Feature is enabled/disabled immediately after save

## 3) Configure SMTP Mail Sending

1. Open `SMTP Settings`
2. Fill SMTP fields (host, port, account, password, sender)
3. Click **Save All**

Expected result:

- Mail-related features use the updated SMTP configuration

## 4) Change Auto Logout Time

1. Open `Login Settings`
2. Set `session_lifetime`
3. Click **Save All**

Expected result:

- Backend session idle timeout uses the saved number of minutes
- This runtime setting overrides the host project's `SESSION_LIFETIME`

## 5) Set Multisite Model Mode (if available)

1. Open `Multisite Settings`
2. For each model, choose mode:
- `Global`
- `Single`
- `Multi`
3. Click **Save All**

Expected result:

- Model website scope updates based on selected mode

## 6) Configure Google Login

1. Open `Login Settings` and enable `allow_google_login`
2. Open `Social Login Settings`
3. Fill `google_client_id`, `google_client_secret`, and `google_redirect`
4. Set the Google OAuth callback URL to your site callback, for example:
   `https://your-domain.com/panel/login/google/callback`
5. Use the helper buttons under the Google settings form:
   `Open Google Setup Page`, `View Google Setup Guide`, and `Test Google Config`
6. Click **Save All**
7. Open `/panel/login` or `/panel/register` and confirm the Google button is visible

Expected result:

- The Google login button appears only when the switch is enabled and all Google settings are filled
- Clicking the button starts the Google OAuth flow
- Existing accounts are matched by email, and new accounts can be created from Google when registration is allowed

## 7) Check Core Updates

1. In settings left panel footer, click **Check for Updates**
2. Follow update page instructions

Expected result:

- You can review available core updates

## 8) Configure Media Upload Location

1. Open `Content Module Settings`
2. Set `media_disk` to:
- `public/media` (recommended for cloned projects)
- `storage/app/public (storage link)`
3. Click **Save All**

Expected result:

- New uploads are stored in the selected location
- If you choose `public/media`, you can run without `storage:link`


## Troubleshooting

## Changes do not apply

- Confirm you clicked **Save All**
- Reload page and verify value is still saved
- Clear cache if your deployment requires it

## Cannot find a settings tab

- Some tabs appear only when related feature is enabled
- `Developer Settings` appears only when developer mode is enabled

## Google login button does not appear

- Confirm `allow_google_login` is enabled
- Confirm `google_client_id`, `google_client_secret`, and `google_redirect` are all saved
- Confirm the callback URL configured in Google matches your WNCMS route exactly
