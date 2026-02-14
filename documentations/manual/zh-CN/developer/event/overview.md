# 事件系统概览

## 介绍

WNCMS 事件文档按领域分组维护。新增 core hook 时，请在对应分组文档中同步登记。

## 分组目录

- [Users 事件](./users.md)
- [Posts 事件](./posts.md)
- [Settings 事件](./settings.md)

## 事件注册

### 在 Service Provider 中注册

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

### 使用监听器类

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

在 `EventServiceProvider` 中注册：

```php
protected $listen = [
    'wncms.frontend.users.register.after' => [
        SendWelcomeEmail::class,
    ],
];
```

## 最佳实践

1. 需要可变参数时使用引用（`&$param`）。
2. Hook 命名遵循命名标准。
3. 非关键副作用建议加错误保护。
4. 新增 hook 必须在本目录同步文档。
5. 结构变更时同步 `zh-CN` 与 `zh-TW`。
