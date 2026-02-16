# 事件系統概覽

## 介紹

WNCMS 事件文件依領域分組維護。新增 core hook 時，請在對應分組文件中同步登記。

## 分組目錄

- [Users 事件](./users.md)
- [Posts 事件](./posts.md)
- [Settings 事件](./settings.md)
- [Themes 事件](./themes.md)

## 事件註冊

### 在 Service Provider 中註冊

```php
namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Event::listen('wncms.frontend.users.register.after', function ($user) {
            // Your logic here
        });
    }
}
```

### 使用監聽器類別

```php
namespace App\Listeners;

class SendWelcomeEmail
{
    public function handle($user)
    {
        // Send welcome email
    }
}
```

在 `EventServiceProvider` 中註冊：

```php
protected $listen = [
    'wncms.frontend.users.register.after' => [
        SendWelcomeEmail::class,
    ],
];
```

## 最佳實務

1. 需要可變參數時使用引用（`&$param`）。
2. Hook 命名遵循命名標準。
3. 非關鍵副作用建議加上錯誤保護。
4. 新增 hook 必須在本目錄同步文件。
5. 結構變更時同步 `zh-CN` 與 `zh-TW`。
