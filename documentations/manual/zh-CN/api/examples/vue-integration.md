# 范例：Vue 整合

```js
import axios from 'axios'

export async function getPosts() {
  const { data } = await axios.post('https://your-domain.com/api/v1/posts', {
    api_token: import.meta.env.VITE_WNCMS_API_TOKEN,
    page_size: 10,
  })

  return data
}
```

## 说明

- 建议透过服务端代理保护敏感 token。
- 前端需处理 `status !== success` 的情况。
