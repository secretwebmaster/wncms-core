# Updates API

The Updates API allows you to trigger and monitor WNCMS core system updates.

## Endpoints Overview

| Method | Endpoint                  | Description             |
| ------ | ------------------------- | ----------------------- |
| POST   | `/api/v1/update`          | Trigger a system update |
| POST   | `/api/v1/update/progress` | Check update progress   |

## Trigger Update

Initiate a WNCMS core or package update.

:::warning Administrative Operation
This is a critical system operation that requires admin access and should be used with caution.
:::

### Endpoint

```
POST /api/v1/update
```

### Authentication

Required: Yes (via `api_token`)

### Feature Toggle

Can be disabled via `disable_core_update` setting.

### Request Parameters

| Parameter   | Type   | Required | Description                                  |
| ----------- | ------ | -------- | -------------------------------------------- |
| `api_token` | string | Yes      | Admin user API token                         |
| `package`   | string | Yes      | Package name to update (e.g., "wncms/wncms") |
| `version`   | string | No       | Specific version (defaults to latest)        |

### Request Example

```bash
curl -X POST "https://your-domain.com/api/v1/update" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "admin-api-token-here",
    "package": "wncms/wncms",
    "version": "6.1.0"
  }'
```

### Request Example - Latest Version

```bash
curl -X POST "https://your-domain.com/api/v1/update" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "admin-api-token-here",
    "package": "wncms/wncms"
  }'
```

### Response Example - Success

```json
{
  "status": "success",
  "message": "Successfully updated",
  "version": "6.1.0"
}
```

### Response Example - Already Updating

```json
{
  "status": "fail",
  "message": "Core update in progress"
}
```

### Response Example - Disabled

```json
{
  "status": "fail",
  "message": "Core update disabled"
}
```

### Response Example - Invalid Request

```json
{
  "status": "fail",
  "message": "Invalid update request"
}
```

### Response Example - Update Failed

```json
{
  "status": "fail",
  "message": "Update failed: Composer dependency conflict"
}
```

## How Updates Work

1. **Lock Check**: System checks if an update is already in progress
   - Lock expires after 3 minutes of inactivity
2. **Set Update Status**: Marks system as "updating"

   - Sets `updating_core` flag
   - Records `update_lock` timestamp

3. **Execute Update**: Calls `wncms:update-package` Artisan command

   - Runs Composer update
   - Applies database migrations
   - Publishes assets
   - Clears caches

4. **Release Lock**: Clears update status regardless of success/failure

### Update Lock Mechanism

The API implements a lock system to prevent concurrent updates:

```php
if (updating_core && update_lock < 3 minutes ago) {
  return "Update in progress";
}
```

This ensures:

- Only one update runs at a time
- Stalled updates don't block future updates
- System remains accessible if update hangs

## Check Update Progress

Monitor the status of an ongoing update.

### Endpoint

```
POST /api/v1/update/progress
```

### Authentication

Required: Configurable via settings

### Request Parameters

| Parameter   | Type   | Required | Description                                 |
| ----------- | ------ | -------- | ------------------------------------------- |
| `api_token` | string | Yes\*    | User API token                              |
| `itemId`    | string | Yes      | Item to check (use "core" for core updates) |

\*Required if authentication is enabled

### Request Example

```bash
curl -X POST "https://your-domain.com/api/v1/update/progress" \
  -H "Content-Type: application/json" \
  -d '{
    "api_token": "your-api-token-here",
    "itemId": "core"
  }'
```

### Response Example - Update In Progress

```json
{
  "status": "success",
  "message": "Successfully fetched updating progress",
  "progress": 1
}
```

### Response Example - No Update Running

```json
{
  "status": "success",
  "message": "Successfully fetched updating progress",
  "progress": 0
}
```

### Progress Values

| Value | Status   | Description                 |
| ----- | -------- | --------------------------- |
| `0`   | Idle     | No update in progress       |
| `1`   | Updating | Update is currently running |

## Update Lifecycle

### Typical Update Flow

```javascript
// 1. Check if system is currently updating
const progress = await checkProgress()

if (progress.progress === 1) {
  console.log('Update already in progress')
  return
}

// 2. Trigger update
const updateResult = await triggerUpdate({
  package: 'wncms/wncms',
  version: '6.1.0',
})

if (updateResult.status === 'fail') {
  console.error('Update failed:', updateResult.message)
  return
}

// 3. Poll for completion
const pollInterval = setInterval(async () => {
  const status = await checkProgress()

  if (status.progress === 0) {
    clearInterval(pollInterval)
    console.log('Update completed')
  }
}, 5000) // Check every 5 seconds
```

## Safety Features

### 1. Update Lock

Prevents multiple simultaneous updates:

```
Update Lock Timeline:
0:00 - Update starts, lock set
0:01 - Another request arrives, blocked by lock
0:03 - Lock expires (3 minutes)
0:04 - New update can start if first one stalled
```

### 2. Feature Toggle

Updates can be completely disabled:

```php
// In WNCMS settings
'disable_core_update' => true
```

### 3. Automatic Lock Release

Lock is always released after update attempt:

```php
try {
  // Attempt update
} catch (Exception $e) {
  // Log error
} finally {
  // Always release lock
  release_update_lock();
}
```

## Best Practices

### 1. Backup Before Updating

Always backup your database and files before triggering updates:

```bash
# Before API call
php artisan backup:run
```

### 2. Check Progress Regularly

Don't assume updates complete instantly:

```javascript
async function waitForUpdate() {
  let attempts = 0
  const maxAttempts = 60 // 5 minutes max

  while (attempts < maxAttempts) {
    const progress = await checkProgress()
    if (progress.progress === 0) {
      return true
    }

    await sleep(5000)
    attempts++
  }

  throw new Error('Update timeout')
}
```

### 3. Handle Failures Gracefully

```javascript
try {
  const result = await triggerUpdate(params)

  if (result.status === 'fail') {
    // Log error, notify admin
    await notifyAdmin(result.message)
  }
} catch (error) {
  // Handle network errors
  await rollbackUpdate()
}
```

### 4. Use Specific Versions

Prefer specific versions over "latest":

```json
{
  "package": "wncms/wncms",
  "version": "6.1.0" // Specific version
}
```

### 5. Maintenance Mode

Put site in maintenance mode during updates:

```bash
# Before update
php artisan down

# Trigger update via API
# ...

# After update
php artisan up
```

## Error Responses

### 403 - Updates Disabled

```json
{
  "status": "fail",
  "message": "Core update disabled"
}
```

### 409 - Update In Progress

```json
{
  "status": "fail",
  "message": "Core update in progress"
}
```

### 400 - Invalid Request

```json
{
  "status": "fail",
  "message": "Invalid update request"
}
```

### 500 - Update Failed

```json
{
  "status": "fail",
  "message": "Update failed: Specific error details here"
}
```

## Logging

All update operations are extensively logged:

```php
// Update start
info('[UpdateController] Incoming request', $request);

// Lock status
info('[UpdateController] Setting update lock + status');

// Artisan command
info('[UpdateController] Calling wncms:update-package');

// Update result
info('[UpdateController] Core version updated to {version}');

// Completion
info('[UpdateController] Update process finished, lock released');
```

Check logs at:

```
storage/logs/laravel.log
```

## Use Cases

### Automated Update System

```javascript
async function automatedUpdate() {
  // 1. Check for new version
  const latestVersion = await checkLatestVersion()

  // 2. Backup system
  await backupSystem()

  // 3. Enable maintenance mode
  await enableMaintenanceMode()

  // 4. Trigger update
  const result = await triggerUpdate({
    package: 'wncms/wncms',
    version: latestVersion,
  })

  // 5. Wait for completion
  await waitForUpdate()

  // 6. Run post-update checks
  await verifyUpdate()

  // 7. Disable maintenance mode
  await disableMaintenanceMode()

  // 8. Notify admin
  await notifyAdmin('Update completed successfully')
}
```

### Update Monitoring Dashboard

```javascript
async function monitorUpdate() {
  const status = await checkProgress()

  return {
    isUpdating: status.progress === 1,
    startTime: localStorage.getItem('update_start'),
    duration: calculateDuration(),
    message: status.message,
  }
}

// Update UI every 2 seconds
setInterval(async () => {
  const monitor = await monitorUpdate()
  updateDashboard(monitor)
}, 2000)
```

## Related Operations

- Check current version: `php artisan wncms:version`
- Manual update: `composer update wncms/wncms`
- Database migration: `php artisan migrate`
- Clear cache: `php artisan cache:clear`

## Troubleshooting

**Update gets stuck at "in progress"?**

- Wait 3 minutes for lock to expire
- Check logs for errors: `storage/logs/laravel.log`
- Manually clear lock if needed

**Update fails with "Composer error"?**

- Check Composer version requirements
- Run `composer diagnose`
- Verify server has sufficient memory

**API returns "Invalid token"?**

- Ensure user has admin privileges
- Verify token is valid and not expired

For more help, see the [Troubleshooting Guide](../troubleshooting.md).
