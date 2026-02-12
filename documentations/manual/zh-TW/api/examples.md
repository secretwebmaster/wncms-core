# API 範例

本頁面提供 WNCMS API 常見用例的完整、即用型程式碼範例。

## JavaScript / Node.js

### 獲取並顯示文章

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

// 使用方式
fetchPosts().then((posts) => {
  console.log(`Fetched ${posts.length} posts`)
})
```

### 建立新文章

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

// 使用方式
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

## 相關文件

- [入門指南](./getting-started.md) - 設定和第一個 API 呼叫
- [核心概念](./core-concepts.md) - 了解回應和分頁
- [文章 API](./endpoints/posts.md) - 詳細端點文件
- [疑難排解](./troubleshooting.md) - 常見問題和解決方案
