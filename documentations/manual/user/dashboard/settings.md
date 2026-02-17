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

## 4) Set Multisite Model Mode (if available)

1. Open `Multisite Settings`
2. For each model, choose mode:
- `Global`
- `Single`
- `Multi`
3. Click **Save All**

Expected result:

- Model website scope updates based on selected mode

## 5) Check Core Updates

1. In settings left panel footer, click **Check for Updates**
2. Follow update page instructions

Expected result:

- You can review available core updates


## Troubleshooting

## Changes do not apply

- Confirm you clicked **Save All**
- Reload page and verify value is still saved
- Clear cache if your deployment requires it

## Cannot find a settings tab

- Some tabs appear only when related feature is enabled
- `Developer Settings` appears only when developer mode is enabled
