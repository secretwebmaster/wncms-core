# Error Reference

Complete guide to WNCMS API error codes and how to handle them.

## HTTP Status Codes

| Code | Status                | Description                                     |
| ---- | --------------------- | ----------------------------------------------- |
| 200  | OK                    | Request successful                              |
| 400  | Bad Request           | Invalid request parameters                      |
| 401  | Unauthorized          | Authentication required or failed               |
| 403  | Forbidden             | API access disabled or insufficient permissions |
| 404  | Not Found             | Resource not found                              |
| 422  | Unprocessable Entity  | Validation failed                               |
| 500  | Internal Server Error | Server-side error occurred                      |

## Common Error Messages

### Authentication Errors

#### Invalid Token

```json
{
  "status": "fail",
  "message": "Invalid token"
}
```

**Causes:**

- API token is incorrect
- API token has been revoked
- User account is disabled

**Solutions:**

1. Verify your API token is correct
2. Regenerate token from admin panel
3. Check user account status

---

#### API Access Disabled

```json
{
  "status": 403,
  "message": "API access is disabled"
}
```

**Causes:**

- Global API is disabled
- Specific endpoint is disabled via feature toggle

**Solutions:**

1. Enable API in WNCMS settings
2. Check endpoint-specific settings (e.g., `wncms_api_posts_index`)
3. Contact system administrator

---

#### Admin Access Required

```json
{
  "status": "fail",
  "message": "Admin access required"
}
```

**Causes:**

- Endpoint requires admin role
- Current user is not an admin

**Solutions:**

1. Use admin user API token
2. Request admin privileges
3. Use alternative non-admin endpoint

---

### Validation Errors

#### Required Field Missing

```json
{
  "code": 422,
  "status": "fail",
  "message": "Validation failed",
  "data": {
    "errors": {
      "title": ["The title field is required."],
      "content": ["The content field is required."]
    }
  }
}
```

**Causes:**

- Required fields not provided
- Empty values for required fields

**Solutions:**

1. Check API documentation for required fields
2. Ensure all required fields have values
3. Validate data before sending

**Example Fix:**

```javascript
// Before
{
  api_token: 'token',
  title: ''  // Empty!
}

// After
{
  api_token: 'token',
  title: 'Valid Title',
  content: 'Valid Content'
}
```

---

#### Invalid Field Type

```json
{
  "code": 422,
  "status": "fail",
  "message": "Validation failed",
  "data": {
    "errors": {
      "page_size": ["The page_size must be an integer."],
      "tags": ["The tags must be an array."]
    }
  }
}
```

**Causes:**

- Wrong data type provided
- String instead of integer
- Non-array where array expected

**Solutions:**

1. Convert values to correct types
2. Check API documentation for field types

**Example Fix:**

```javascript
// Before
{
  page_size: '10',  // String
  tags: 1           // Not array
}

// After
{
  page_size: 10,      // Integer
  tags: [1]           // Array
}
```

---

#### Field Too Long

```json
{
  "code": 422,
  "status": "fail",
  "message": "Validation failed",
  "data": {
    "errors": {
      "name": ["The name may not be greater than 255 characters."]
    }
  }
}
```

**Causes:**

- Input exceeds maximum length
- Name field > 255 characters
- Type field > 50 characters

**Solutions:**

1. Trim input to allowed length
2. Use abbreviations or shorter names
3. Store full text in description field

---

#### Invalid Foreign Key

```json
{
  "code": 422,
  "status": "fail",
  "message": "Validation failed",
  "data": {
    "errors": {
      "parent_id": ["The selected parent_id is invalid."]
    }
  }
}
```

**Causes:**

- Referenced ID doesn't exist
- Parent tag/category not found
- Invalid page_id in menu

**Solutions:**

1. Verify the ID exists before referencing
2. Use the exist endpoint to check
3. Create parent record first

**Example:**

```javascript
// Check if parent exists
const result = await fetch('/api/v1/tags/exist', {
  method: 'POST',
  body: JSON.stringify({
    api_token: 'token',
    tagIds: [parentId],
  }),
})

if (result.ids.includes(parentId)) {
  // Safe to create child
}
```

---

### Resource Errors

#### Not Found

```json
{
  "code": 404,
  "status": "fail",
  "message": "Post not found"
}
```

**Causes:**

- Post/resource doesn't exist
- Incorrect slug or ID
- Resource was deleted

**Solutions:**

1. Verify the slug/ID is correct
2. Check if resource still exists
3. Use list endpoint to find correct identifier

---

#### Website Not Found

```json
{
  "code": 404,
  "status": "fail",
  "message": "Website not found"
}
```

**Causes:**

- Invalid website_id
- Incorrect domain
- Website doesn't exist

**Solutions:**

1. Verify website ID in database
2. Check domain spelling
3. Ensure website is active

---

### Operation Errors

#### Update In Progress

```json
{
  "status": "fail",
  "message": "Core update in progress"
}
```

**Causes:**

- Another update is running
- Update lock is active
- Previous update didn't complete

**Solutions:**

1. Wait for current update to finish (check progress endpoint)
2. Wait 3 minutes for lock to expire
3. Check logs for stuck updates

---

#### Update Disabled

```json
{
  "status": "fail",
  "message": "Core update disabled"
}
```

**Causes:**

- Updates disabled in settings
- `disable_core_update` is true

**Solutions:**

1. Enable updates in WNCMS settings
2. Contact system administrator
3. Update manually via Composer

---

#### Invalid Update Request

```json
{
  "status": "fail",
  "message": "Invalid update request"
}
```

**Causes:**

- Missing package parameter
- Malformed request

**Solutions:**

1. Ensure `package` field is provided
2. Check request format
3. Review API documentation

---

### Server Errors

#### Internal Server Error

```json
{
  "code": 500,
  "status": "fail",
  "message": "Server Error: Database connection failed"
}
```

**Causes:**

- Database connection issues
- Unhandled exceptions
- Server configuration problems
- File permission issues

**Solutions:**

1. Check server logs: `storage/logs/laravel.log`
2. Verify database connection
3. Check file permissions
4. Contact server administrator

---

#### Duplicate Entry

```json
{
  "status": "success",
  "message": "Skipped. Duplicated tag found",
  "data": {...}
}
```

**Note:** This is actually a success response, not an error.

**Causes:**

- Tag with same name and type exists
- `update_when_duplicated` is false

**Solutions:**

1. Use different name or type
2. Set `update_when_duplicated: true` to update existing
3. Accept the existing tag

---

## Error Handling Patterns

### Basic Error Handler

```javascript
async function apiCall(url, payload) {
  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })

    const result = await response.json()

    if (result.status === 'success') {
      return result.data
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    console.error('API Error:', error.message)
    throw error
  }
}
```

### Advanced Error Handler with Retry

```javascript
async function apiCallWithRetry(url, payload, maxRetries = 3) {
  let lastError

  for (let attempt = 0; attempt < maxRetries; attempt++) {
    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      })

      const result = await response.json()

      if (result.status === 'success') {
        return result.data
      }

      // Don't retry validation errors (422) or forbidden (403)
      if (result.code === 422 || result.code === 403) {
        throw new Error(result.message)
      }

      lastError = new Error(result.message)

      // Wait before retry (exponential backoff)
      await new Promise((resolve) => setTimeout(resolve, Math.pow(2, attempt) * 1000))
    } catch (error) {
      lastError = error

      // Don't retry on validation errors
      if (error.message.includes('Validation failed')) {
        throw error
      }

      // Last attempt?
      if (attempt === maxRetries - 1) {
        throw error
      }
    }
  }

  throw lastError
}
```

### Validation Error Handler

```javascript
function handleValidationError(errorData) {
  const errors = errorData.data?.errors || {}
  const messages = []

  for (const [field, fieldErrors] of Object.entries(errors)) {
    messages.push(`${field}: ${fieldErrors.join(', ')}`)
  }

  return messages.join('\n')
}

// Usage
try {
  await createPost(postData)
} catch (error) {
  if (error.code === 422) {
    const validationMessages = handleValidationError(error)
    alert('Validation failed:\n' + validationMessages)
  } else {
    alert('Error: ' + error.message)
  }
}
```

### Centralized Error Handler

```javascript
class WncmsApiError extends Error {
  constructor(message, code, data) {
    super(message)
    this.code = code
    this.data = data
    this.name = 'WncmsApiError'
  }

  isValidationError() {
    return this.code === 422
  }

  isForbidden() {
    return this.code === 403
  }

  isNotFound() {
    return this.code === 404
  }

  isServerError() {
    return this.code >= 500
  }

  getValidationErrors() {
    return this.data?.errors || {}
  }
}

async function wncmsApiCall(url, payload) {
  const response = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })

  const result = await response.json()

  if (result.status === 'success') {
    return result.data
  }

  throw new WncmsApiError(result.message, result.code, result.data)
}

// Usage
try {
  const posts = await wncmsApiCall('/api/v1/posts', { api_token: 'token' })
} catch (error) {
  if (error instanceof WncmsApiError) {
    if (error.isValidationError()) {
      console.log('Validation errors:', error.getValidationErrors())
    } else if (error.isForbidden()) {
      console.log('Access denied')
    } else if (error.isServerError()) {
      console.log('Server error - please try again')
    }
  } else {
    console.log('Network error:', error)
  }
}
```

## Debugging Tips

### 1. Check Response Status

Always check the `status` field:

```javascript
const result = await apiCall()
console.log('Status:', result.status) // 'success' or 'fail'
```

### 2. Log Full Error Response

```javascript
try {
  await apiCall()
} catch (error) {
  console.error('Full error:', JSON.stringify(error, null, 2))
}
```

### 3. Inspect HTTP Status Code

```javascript
const response = await fetch(url, options)
console.log('HTTP Status:', response.status)

const result = await response.json()
console.log('API Status:', result.status)
```

### 4. Check Server Logs

Look in `storage/logs/laravel.log` for detailed error traces:

```bash
tail -f storage/logs/laravel.log
```

### 5. Test with cURL

```bash
curl -X POST "https://your-domain.com/api/v1/posts" \
  -H "Content-Type: application/json" \
  -d '{"api_token": "your-token"}' \
  -v  # Verbose output
```

### 6. Validate JSON Syntax

```javascript
// Check if JSON is valid
try {
  JSON.parse(yourJsonString)
  console.log('Valid JSON')
} catch (e) {
  console.error('Invalid JSON:', e)
}
```

## Common Scenarios

### Scenario 1: Token Authentication Failed

**Problem:** Getting "Invalid token" even with correct token

**Checklist:**

- [ ] Token copied correctly (no extra spaces)
- [ ] User account is active
- [ ] Token field name is `api_token`
- [ ] Token in request body, not header
- [ ] HTTPS being used

**Solution:**

```javascript
// Regenerate token
// 1. Go to admin panel → Profile
// 2. Click "Regenerate API Token"
// 3. Copy new token
// 4. Update your code
```

### Scenario 2: Validation Fails Unexpectedly

**Problem:** Validation errors even though fields seem correct

**Checklist:**

- [ ] Check field data types (string vs integer)
- [ ] Verify array format for tags/categories
- [ ] Check max length constraints
- [ ] Ensure required fields are present
- [ ] Foreign key references exist

**Solution:**

```javascript
// Log request payload before sending
console.log('Payload:', JSON.stringify(payload, null, 2))

// Check API docs for exact requirements
// Validate locally before sending
```

### Scenario 3: 500 Server Error

**Problem:** Getting 500 errors intermittently

**Checklist:**

- [ ] Check server logs
- [ ] Verify database connection
- [ ] Check file permissions
- [ ] Ensure adequate server resources
- [ ] Look for PHP errors

**Solution:**

```bash
# Check logs
tail -100 storage/logs/laravel.log

# Check PHP errors
tail -100 /var/log/php-fpm/error.log

# Check permissions
ls -la storage/
```

## Prevention Strategies

### 1. Validate Before Sending

```javascript
function validatePostData(data) {
  const errors = []

  if (!data.title) errors.push('Title is required')
  if (!data.content) errors.push('Content is required')
  if (data.title && data.title.length > 255) {
    errors.push('Title too long (max 255)')
  }

  return errors
}

const errors = validatePostData(postData)
if (errors.length > 0) {
  console.error('Validation errors:', errors)
  return
}

// Safe to send
await createPost(postData)
```

### 2. Use TypeScript/JSDoc

```typescript
interface PostData {
  title: string
  content: string
  excerpt?: string
  tags?: number[]
  categories?: number[]
}

async function createPost(data: PostData): Promise<Post> {
  // TypeScript ensures correct types
}
```

### 3. Implement Rate Limiting

```javascript
class RateLimiter {
  constructor(maxRequests, timeWindow) {
    this.maxRequests = maxRequests
    this.timeWindow = timeWindow
    this.requests = []
  }

  async throttle() {
    const now = Date.now()
    this.requests = this.requests.filter((time) => now - time < this.timeWindow)

    if (this.requests.length >= this.maxRequests) {
      const wait = this.timeWindow - (now - this.requests[0])
      await new Promise((resolve) => setTimeout(resolve, wait))
    }

    this.requests.push(now)
  }
}

const limiter = new RateLimiter(60, 60000) // 60 req/min

await limiter.throttle()
await apiCall()
```

## Related Documentation

- [Core Concepts](./core-concepts.md) - Response format and error handling
- [Troubleshooting](./troubleshooting.md) - Common issues and solutions
- [Examples](./examples.md) - Code examples with error handling

## Getting Help

If you encounter an error not covered here:

1. Check the [Troubleshooting Guide](./troubleshooting.md)
2. Review server logs: `storage/logs/laravel.log`
3. Search GitHub issues
4. Contact WNCMS support through admin panel
