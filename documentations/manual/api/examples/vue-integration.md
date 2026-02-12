# Example: Vue Integration

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

## Notes

- Prefer server-side proxy for sensitive tokens.
- Handle `status !== success` in UI.
