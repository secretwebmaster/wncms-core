# API Examples

This page provides complete, ready-to-use code examples for common WNCMS API use cases.

## JavaScript / Node.js

### Fetch and Display Posts

```javascript
async function fetchPosts() {
  try {
    const response = await fetch('https://your-domain.com/api/v1/posts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        api_token: 'your-api-token-here',
        page_size: 10,
        sort: 'created_at',
        direction: 'desc',
      }),
    })

    const result = await response.json()

    if (result.status === 'success') {
      const posts = result.data.data
      posts.forEach((post) => {
        console.log(`${post.title} - ${post.created_at}`)
      })

      return posts
    } else {
      console.error('API Error:', result.message)
      return []
    }
  } catch (error) {
    console.error('Network Error:', error)
    return []
  }
}

// Usage
fetchPosts().then((posts) => {
  console.log(`Fetched ${posts.length} posts`)
})
```

### Create a New Post

```javascript
async function createPost(postData) {
  const payload = {
    api_token: 'your-api-token-here',
    title: postData.title,
    content: postData.content,
    excerpt: postData.excerpt,
    status: 'published',
    tags: postData.tags || [],
    categories: postData.categories || [],
  }

  try {
    const response = await fetch('https://your-domain.com/api/v1/posts/store', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    })

    const result = await response.json()

    if (result.status === 'success') {
      console.log(`Post created: ${result.data.id}`)
      return result.data
    } else {
      throw new Error(result.message)
    }
  } catch (error) {
    console.error('Failed to create post:', error)
    throw error
  }
}

// Usage
createPost({
  title: 'My New Article',
  content: '<p>This is the article content.</p>',
  excerpt: 'A brief summary',
  tags: [1, 2],
  categories: [5],
})
  .then((post) => {
    console.log('Created post:', post)
  })
  .catch((error) => {
    console.error('Error:', error)
  })
```

### Search Posts with Filters

```javascript
async function searchPosts(keyword, tags = []) {
  const payload = {
    api_token: 'your-api-token-here',
    keywords: keyword,
    tags: tags,
    page_size: 20,
    sort: 'relevance',
  }

  const response = await fetch('https://your-domain.com/api/v1/posts', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  })

  const result = await response.json()
  return result.status === 'success' ? result.data.data : []
}

// Usage
searchPosts('Laravel', [1, 2, 3]).then((posts) => console.log('Search results:', posts))
```

### Pagination Handler

```javascript
class PostPaginator {
  constructor(apiToken) {
    this.apiToken = apiToken
    this.currentPage = 1
    this.pageSize = 15
  }

  async fetchPage(page = this.currentPage) {
    const response = await fetch('https://your-domain.com/api/v1/posts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        api_token: this.apiToken,
        page: page,
        page_size: this.pageSize,
      }),
    })

    const result = await response.json()

    if (result.status === 'success') {
      this.currentPage = result.data.pagination.current_page
      return {
        posts: result.data.data,
        pagination: result.data.pagination,
      }
    }

    return null
  }

  async nextPage() {
    return await this.fetchPage(this.currentPage + 1)
  }

  async prevPage() {
    if (this.currentPage > 1) {
      return await this.fetchPage(this.currentPage - 1)
    }
    return null
  }
}

// Usage
const paginator = new PostPaginator('your-api-token')

// First page
paginator.fetchPage(1).then((result) => {
  console.log('Posts:', result.posts)
  console.log('Total pages:', result.pagination.last_page)
})

// Next page
paginator.nextPage().then((result) => {
  console.log('Next page posts:', result.posts)
})
```

## React Integration

### Custom Hook for Posts

```javascript
import { useState, useEffect } from 'react'

function useWncmsPosts(filters = {}) {
  const [posts, setPosts] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [pagination, setPagination] = useState(null)

  useEffect(() => {
    async function fetchPosts() {
      try {
        setLoading(true)
        const response = await fetch('https://your-domain.com/api/v1/posts', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            api_token: process.env.REACT_APP_API_TOKEN,
            ...filters,
          }),
        })

        const result = await response.json()

        if (result.status === 'success') {
          setPosts(result.data.data)
          setPagination(result.data.pagination)
        } else {
          setError(result.message)
        }
      } catch (err) {
        setError(err.message)
      } finally {
        setLoading(false)
      }
    }

    fetchPosts()
  }, [JSON.stringify(filters)])

  return { posts, loading, error, pagination }
}

// Usage in component
function BlogList() {
  const { posts, loading, error, pagination } = useWncmsPosts({
    page_size: 10,
    sort: 'created_at',
    direction: 'desc',
  })

  if (loading) return <div>Loading...</div>
  if (error) return <div>Error: {error}</div>

  return (
    <div>
      {posts.map((post) => (
        <article key={post.id}>
          <h2>{post.title}</h2>
          <div dangerouslySetInnerHTML={{ __html: post.excerpt }} />
        </article>
      ))}

      {pagination && (
        <div>
          Page {pagination.current_page} of {pagination.last_page}
        </div>
      )}
    </div>
  )
}
```

### Post Creation Form

```javascript
import { useState } from 'react'

function CreatePostForm() {
  const [formData, setFormData] = useState({
    title: '',
    content: '',
    excerpt: '',
  })
  const [submitting, setSubmitting] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSubmitting(true)

    try {
      const response = await fetch('https://your-domain.com/api/v1/posts/store', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          api_token: process.env.REACT_APP_API_TOKEN,
          ...formData,
          status: 'published',
        }),
      })

      const result = await response.json()

      if (result.status === 'success') {
        alert(`Post created: ${result.data.id}`)
        setFormData({ title: '', content: '', excerpt: '' })
      } else {
        alert(`Error: ${result.message}`)
      }
    } catch (error) {
      alert(`Network error: ${error.message}`)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <form onSubmit={handleSubmit}>
      <input
        type="text"
        placeholder="Title"
        value={formData.title}
        onChange={(e) => setFormData({ ...formData, title: e.target.value })}
        required
      />

      <textarea
        placeholder="Content"
        value={formData.content}
        onChange={(e) => setFormData({ ...formData, content: e.target.value })}
        required
      />

      <textarea
        placeholder="Excerpt"
        value={formData.excerpt}
        onChange={(e) => setFormData({ ...formData, excerpt: e.target.value })}
      />

      <button type="submit" disabled={submitting}>
        {submitting ? 'Creating...' : 'Create Post'}
      </button>
    </form>
  )
}
```

## PHP Examples

### Fetch Posts with cURL

```php
<?php

function fetchWncmsPosts($apiToken, $filters = []) {
    $payload = array_merge([
        'api_token' => $apiToken,
        'page_size' => 15
    ], $filters);

    $ch = curl_init('https://your-domain.com/api/v1/posts');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        return $result['status'] === 'success' ? $result['data']['data'] : [];
    }

    return [];
}

// Usage
$posts = fetchWncmsPosts('your-api-token', [
    'keywords' => 'Laravel',
    'tags' => [1, 2, 3],
    'sort' => 'created_at',
    'direction' => 'desc'
]);

foreach ($posts as $post) {
    echo "<h2>{$post['title']}</h2>";
    echo "<p>{$post['excerpt']}</p>";
}
```

### Create Post with Laravel HTTP Client

```php
<?php

use Illuminate\Support\Facades\Http;

class WncmsApiClient
{
    protected $baseUrl;
    protected $apiToken;

    public function __construct($baseUrl, $apiToken)
    {
        $this->baseUrl = $baseUrl;
        $this->apiToken = $apiToken;
    }

    public function createPost(array $data)
    {
        $response = Http::post("{$this->baseUrl}/api/v1/posts/store", array_merge([
            'api_token' => $this->apiToken
        ], $data));

        $result = $response->json();

        if ($result['status'] === 'success') {
            return $result['data'];
        }

        throw new Exception($result['message']);
    }

    public function updatePost($slug, array $data)
    {
        $response = Http::post("{$this->baseUrl}/api/v1/posts/update/{$slug}", array_merge([
            'api_token' => $this->apiToken
        ], $data));

        return $response->json();
    }

    public function deletePost($slug)
    {
        $response = Http::post("{$this->baseUrl}/api/v1/posts/delete/{$slug}", [
            'api_token' => $this->apiToken
        ]);

        return $response->json();
    }
}

// Usage
$client = new WncmsApiClient('https://your-domain.com', 'your-api-token');

try {
    $post = $client->createPost([
        'title' => 'New Article',
        'content' => '<p>Article content</p>',
        'status' => 'published',
        'tags' => [1, 2]
    ]);

    echo "Created post: {$post['id']}";
} catch (Exception $e) {
    echo "Error: {$e->getMessage()}";
}
```

## Python Examples

### Fetch Posts

```python
import requests

class WncmsClient:
    def __init__(self, base_url, api_token):
        self.base_url = base_url
        self.api_token = api_token

    def fetch_posts(self, **filters):
        payload = {
            'api_token': self.api_token,
            **filters
        }

        response = requests.post(
            f'{self.base_url}/api/v1/posts',
            json=payload,
            headers={'Content-Type': 'application/json'}
        )

        result = response.json()

        if result.get('status') == 'success':
            return result['data']['data']
        else:
            raise Exception(result.get('message', 'Unknown error'))

    def create_post(self, title, content, **kwargs):
        payload = {
            'api_token': self.api_token,
            'title': title,
            'content': content,
            **kwargs
        }

        response = requests.post(
            f'{self.base_url}/api/v1/posts/store',
            json=payload
        )

        result = response.json()

        if result.get('status') == 'success':
            return result['data']
        else:
            raise Exception(result.get('message'))

# Usage
client = WncmsClient('https://your-domain.com', 'your-api-token')

# Fetch posts
posts = client.fetch_posts(
    keywords='Python',
    page_size=20,
    sort='created_at'
)

for post in posts:
    print(f"{post['title']} - {post['created_at']}")

# Create post
new_post = client.create_post(
    title='Python Tutorial',
    content='<p>Learn Python with WNCMS</p>',
    status='published',
    tags=[1, 2, 3]
)

print(f"Created post ID: {new_post['id']}")
```

## Vue.js Integration

### Composable for API Calls

```javascript
// composables/useWncmsApi.js
import { ref } from 'vue'

export function useWncmsApi(baseUrl, apiToken) {
  const loading = ref(false)
  const error = ref(null)

  async function fetchPosts(filters = {}) {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${baseUrl}/api/v1/posts`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          api_token: apiToken,
          ...filters,
        }),
      })

      const result = await response.json()

      if (result.status === 'success') {
        return result.data
      } else {
        throw new Error(result.message)
      }
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function createPost(postData) {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${baseUrl}/api/v1/posts/store`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          api_token: apiToken,
          ...postData,
        }),
      })

      const result = await response.json()

      if (result.status === 'success') {
        return result.data
      } else {
        throw new Error(result.message)
      }
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    fetchPosts,
    createPost,
  }
}

// Component usage
import { ref, onMounted } from 'vue'
import { useWncmsApi } from '@/composables/useWncmsApi'

export default {
  setup() {
    const posts = ref([])
    const { loading, error, fetchPosts } = useWncmsApi('https://your-domain.com', 'your-api-token')

    onMounted(async () => {
      const result = await fetchPosts({ page_size: 10 })
      posts.value = result.data
    })

    return {
      posts,
      loading,
      error,
    }
  },
}
```

## Advanced Use Cases

### Batch Import Posts

```javascript
async function batchImportPosts(postsArray, apiToken) {
  const results = {
    success: [],
    failed: [],
  }

  for (const postData of postsArray) {
    try {
      const response = await fetch('https://your-domain.com/api/v1/posts/store', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          api_token: apiToken,
          ...postData,
        }),
      })

      const result = await response.json()

      if (result.status === 'success') {
        results.success.push({
          title: postData.title,
          id: result.data.id,
        })
      } else {
        results.failed.push({
          title: postData.title,
          error: result.message,
        })
      }

      // Rate limiting - wait 100ms between requests
      await new Promise((resolve) => setTimeout(resolve, 100))
    } catch (error) {
      results.failed.push({
        title: postData.title,
        error: error.message,
      })
    }
  }

  return results
}

// Usage
const posts = [
  { title: 'Post 1', content: 'Content 1' },
  { title: 'Post 2', content: 'Content 2' },
  // ... more posts
]

batchImportPosts(posts, 'your-api-token').then((results) => {
  console.log(`Imported: ${results.success.length}`)
  console.log(`Failed: ${results.failed.length}`)
})
```

### Menu Synchronization

```javascript
async function syncWebsiteMenu(menuItems, websiteId, apiToken) {
  const response = await fetch('https://your-domain.com/api/v1/menus/sync', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      api_token: apiToken,
      website_id: websiteId,
      menu_items: menuItems,
    }),
  })

  const result = await response.json()

  if (result.status === 'success') {
    console.log(`Synced ${result.data.items_created} menu items`)
    return true
  } else {
    throw new Error(result.message)
  }
}

// Usage
const menuStructure = [
  { order: 1, name: 'Home', type: 'page', page_id: 1 },
  { order: 2, name: 'About', type: 'page', page_id: 2 },
  { order: 3, name: 'Blog', type: 'link', url: '/blog' },
]

syncWebsiteMenu(menuStructure, 1, 'admin-api-token')
```

## Error Handling

### Comprehensive Error Handler

```javascript
class WncmsApiError extends Error {
  constructor(message, code, data) {
    super(message)
    this.code = code
    this.data = data
    this.name = 'WncmsApiError'
  }
}

async function safeApiCall(url, payload) {
  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    })

    const result = await response.json()

    if (result.status === 'success') {
      return result.data
    } else {
      throw new WncmsApiError(result.message, result.code, result.data)
    }
  } catch (error) {
    if (error instanceof WncmsApiError) {
      // API error - handle gracefully
      console.error('API Error:', error.message)

      if (error.code === 422) {
        // Validation error
        console.error('Validation errors:', error.data.errors)
      }
    } else {
      // Network error
      console.error('Network Error:', error)
    }

    throw error
  }
}

// Usage with error handling
try {
  const posts = await safeApiCall('https://your-domain.com/api/v1/posts', {
    api_token: 'token',
  })
  console.log('Posts:', posts)
} catch (error) {
  // Error already logged and handled
  // Show user-friendly message
  alert('Failed to fetch posts. Please try again later.')
}
```

## Related Documentation

- [Getting Started](./getting-started.md) - Setup and first API call
- [Core Concepts](./core-concepts.md) - Understanding responses and pagination
- [Posts API](./endpoints/posts.md) - Detailed endpoint documentation
- [Troubleshooting](./troubleshooting.md) - Common issues and solutions
