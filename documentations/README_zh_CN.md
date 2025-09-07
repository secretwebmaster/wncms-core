# Wncms 项目介绍

Demo: [demo.wncms.cc](https://demo.wncms.cc)

## 更新日志

### v5.5.5 2025-09-08

-   更新 AdvertisementManager，继承 ModelManager
-   移除重复的翻译 migration 文件
-   修复找不到 Advertisement 与网站的关联问题
-   在 PageController 加载前端主题 helpers
-   将 HasTags trait 移到基础 WncmsModel（所有模型都可以打标签）
-   移除多余的 OwnModelTrait 和 WnModelTrait
-   移除 ExtraAttribute，改用 laravel-optionable 包
-   后台新增工具页面
-   本地化 @simonwep/pickr 包
-   重构所有控制器中的 view()，改为使用 wncms()->view()
-   在 Advertisement 模型中新增 contact 字段
-   新增创建 Tag 模型的 API
-   新增 HasComments trait
-   在后台 Post 显示评论列表
-   重写前台 PostController 以符合最新格式
-   修复后台权限编辑页错误
-   预备后台 UI 以支持 API 使用日志

### v5.5.4 2025-08-18

-   修复标签保存时的翻译问题，更新依赖 **wncms-translatable**
-   修复 **UserController** 中多处验证与数据传递问题（`profile` 页面 `$user` 未传递、密码验证规则和信息错误、`withError()` 使用错误、`profile` 更新程序优化）
-   修复部分多站点模式的问题
-   更新 **Controller stub** 文件以符合新结构
-   修复后台控制器路由错误

### v5.5.3 2025-08-17

-   修复提交 Checkbox 会发送 NULL 值的问题
-   Beta 版本多模块多站点兼容适配
-   更新 Starter 表单项 Blade 中的 repeater 示例
-   将 WnContentModelTraits 重命名为 WnContentModelTrait
-   将 WnModelTraits 重命名为 WnModelTrait
-   Beta 版本新增 HasMultisite trait

### v5.5.2 2025-07-20

-   添加前台链接页面和链接控制器
-   更新依赖 wncms-tags > 1.6.1

### v5.5.0 2025-07-19

-   部分后台 Blade 视图中将 `getVersion()` 替换为 `addVersion()`
-   新增 `isActiveRoutes()` 辅助函数
-   修复安装程序在资料库迁移后中止的问题
-   避免在覆盖 `Wncms\Models` 为 `App\Models` 时侧边栏重複出现模型
-   修复 `PageController` 中自定义资源页面无法显示的问题
-   移除 `click.js`，改用 API 取代
-   安装后自动更新版本号
-   预设将伫列连线从 `redis` 改为 `sync`
-   删除动作将返回上一页而非索引页
-   重写 `PageManager`
-   重写 `UserManager`
-   仪表板在资料库较大时的记忆体需求已优化
-   清理后台 `PostController` 代码
-   支援在 WNCMS 预设视图之前使用自定义 app 视图
-   当输入栏位为 hidden 类型时，隐藏开发者提示
-   修复广告背景颜色未显示的问题
-   后台联络表单整合建立与编辑表单项目
-   移除 `BulkDeleteTrait`，整合进 `BackendController`
-   移除 `tags.index.type` 路由，整合进 `tags.index`
-   移除选单中的多站台选项
-   将产品栏位 `attribute` 改名为 `properties`
-   支援新增自定义搜寻关键字
-   移除 `traffic_log` 和 `click_log` 的 Blade 视图
-   所有模型关联改为使用 `wncms()->getModelClass('xxx')`，以支援模型覆写
-   `CreateModel` 路由更新为使用 `{id}` 取代 `{modelName}`
-   控制器样板加入 `order by id desc`
-   改进后台网站编辑页面，加入网域别名排序功能

### v5.4.3 2025-07-17

-   后台连结路由接受 id，不再自动解析模型，支援自定义 Link 模型
-   `BackendController` 现为抽象类别以维持标准
-   `ModelManager` 的 get() 函式新增 `$option['name']` 支援
-   非清单中的网域将共用回退网站的快取
-   自动在 select 中加入排序栏位避免 SQL 错误
-   在 `WebsiteManager` 中对回退网站预先载入媒体
-   重写 `TagManager` 的条件逻辑
-   更新更新日志 API 路由名称为 `api.v1.update.run`
-   将 Link 模型的描述栏位由输入框改为 textarea
-   清理后台路由代码，移除开头斜线
-   `BackendController` 新增抽象 `getModelClass()` 方法
-   移除 `AnalyticController`

### v5.4.0 2025-04-05

-   修正 Card 模型找不到状态的问题
-   当启用 `multi_website` 时，新增下拉选单可选择网站模型
-   支援模型使用常数 `NAME_KEY` 自定名称，避免如 `order` 混淆排序与订单
-   更新后台侧边栏选单中的模型图示
-   允许删除网站模型（但至少保留一个）
-   权限支援批次删除功能
-   新增 Channel、Click、Parameter 模型
-   暂时隐藏 Price 模型，每个价格应设定于对应的上层模型中
-   修正未定义的 `$display_name` 变数
-   移除未使用的 analytics API 路由
-   在大多数 migration 中新增资料表是否存在检查
-   LinkManager 预载 media 资料
-   重构 CacheManager：现代语法并统一支援 tag 的 get/put/forget
-   重构 ModelManager：建立抽象基底类别以重用逻辑
-   PostManager 与 LinkManager 改为继承 ModelManager，支援标籤与关键字筛选
-   支援 `wncms()->getModel('xxx')` 并可回退到 config/App/Models/Wncms/Models
-   增加 zh_TW 语系翻译键（如 `bulk_create`, `channel`, `parameters`, `clickable_type` 等）
-   CardController 改用 `wncms()->getModel('user')` 动态取得使用者模型
-   `gto()` 可在不传入 key 时回传所有选项
-   新增多语言翻译
-   修正错字：`fontaweseom` → `fontawesome`，`operater` → `operator`，`Starer` → `Starter`
-   Tag 模型新增 `$tag->url` 存取器
-   更新后台使用者搜寻逻辑
-   广告列表新增分页与样式优化
-   修正打包主题档案时未包含语系档案问题
-   新增 `wncms()->log($message, $level)` 可随处呼叫的除错函式
-   【重要】LinkManager 升级为继承 ModelManager
-   修正后台快速连结删除错误
-   `starter/form-items.blade.php` 新增 `repeater` 栏位
-   修正 `starter/form-items.blade.php` 中的 color 栏位错误
-   `form-items.blade.php` 新增开发者提示
-   修正广告排序未更新问题
-   使用者登入与管理员登入页面分离
-   更新 Post API

### v5.3.0 2025-03-30

-   后台顶部连结根据站群设定显示，站群显示网站列表，单站显示网站设定和主题设定
-   修复广告模型无法更新排序栏位的问题
-   允许更新网站模型的域名
-   新增超级管理员的系统设定，准备隐藏一些不常用的选项
-   更新后台侧边栏的主题列表图示
-   新增主题档案结构检查功能
-   自订的 ModelManager 将优先于套件内的 ModelManager 载入
-   TagManager 的 get() 函数允许传入 null，以避免从主题选项取得 null 时发生错误
-   更新后台列表页的状态与布林栏位样式，支援徽章或纯文字显示
-   将 getList() 函数的预设每页笔数设为 0，即预设停用分页功能
-   新增可包含于后台列表页的模型批次更新按钮 blade
-   更新 Link 模型的后台功能，允许批次更新状态与排序

### v5.2.0 2025-02-04

-   新增多语系翻译
-   程式码清理
-   前台注册用户预设指派为 member 角色，可由主题选项调整
-   当网站模型无法透过目前网域找到时，读取第一个网站的广告资料
-   预先载入连结的翻译资料
-   若依目前网域找不到网站模型，则读取第一个网站的文章，可选择随机排序结果
-   预先载入网站模型的翻译资料

### v5.1.3 2025-02-04

-   新增多语系翻译

### v5.1.2 2025-02-04

-   改善后台快捷连结储存逻辑，改为储存 URL 而非路由名称，以避免遗失参数
-   后台主题选项页标题新增网站 ID 显示

### v5.1.1 2025-01-28

-   将所有 gss 版本更新为 core_version

### v5.1.0 2025-01-28

-   修复更多选单项目资料无法储存的问题
-   从可为空的栏位中移除 required 类别
-   预加载 Website 模型及其翻译
-   新增 ThemeServiceProvider
-   使用当前网域来显示页面 URL，而非从 website 模型获取
-   预加载 Post 模型的翻译，允许 order 变数设为随机
-   使 Link 模型可翻译

### v5.0.0 2025-01-27

-   修复使用者页面路由顺序错误
-   在 Credit 模型中新增函式以新增或获取 Credit 额度
-   在 User 模型中新增函式以直接检查 Credit 额度
-   允许 TinyMCE 选择器为类别，将 GIF 新增至允许的图片格式
-   重新启用使用前台登入页面登入后台的功能
-   在运行时动态修改 CSRF 规则
-   更新 wncms-translatable 的相依性
-   修复更新后选单项目翻译遗失的问题
-   允许 gto() 配置当值为空时是否回退
-   新增前台文章的 CRUD（建立、读取、更新、删除）功能

### v4.5.0 2024-09-30

-   修复分页 URL 被快取时包含语系的问题
-   新增外挂系统
-   提升 PostHelper 的效能
-   更新测试
-   新增 `LOCALIZATION_HIDE_DEFAULT_LOCALE` 环境变数

### v4.4.0 2024-09-18

-   添加了后端的更新通知功能。
-   更新了模型创建函数，以确保与自定义模型和控制器的兼容性。
-   修正了模型分页，以准确显示最后一页的项目数量。
-   在设置中添加了禁用核心更新的选项。
-   在 `README.md` 中包含了演示 URL。
-   更新了错误页面。
-   添加了翻译。
-   引入了更新设置的命令。
-   添加了主题控制器。

### v4.3.0 2024-09-14

-   测试新的安装过程
-   停止追踪 vendor 目录
-   更新后台页脚年份为动态更新
-   在安装前将预设快取驱动程式更新为 null
-   更新 README.md
-   改进预设模板
-   更新安装程式图示。安装时自动添加强制 HTTPS 设定
-   向安装程式添加错误讯息
-   在安装过程中添加选择 Redis、Memcache、文件作为快取驱动程式的选项
-   将预设 DB_CONNECTION 设为内存中的假 SQLite。将预设 SESSION 驱动程式设为文件
-   禁用安装过程中的 trait 文件生成
-   在系统设定中添加强制 HTTPS 选项
-   添加预设 env 文件
-   更新 .gitignore
-   更新 composer 依赖
-   更新主题选项面板
-   改进首次网站创建流程
-   移除调试讯息
-   修正后台样式
-   重命名 Traits。修正安装错误。添加强制 HTTPS 相容性
-   在要求之前检查自定义语言文件是否存在

### v4.2.0 2024-09-13

-   移除 WnTagTraits.php
-   在生成示例文章内容时添加翻译
-   改进预设首页模板。添加文章存档 URL
-   添加更多翻译
-   修正添加第二个网站时的错误

### v4.1.0 2024-09-07

-   添加强制 HTTPS 选项
-   小幅修正
-   更新预设 env 文件以隐藏调试讯息
-   安装和网站创建后清除快取
-   添加 PHP 函数需求检查
-   将 database.sqlite 添加到 .gitignore
-   修正创建主题命令。根据 Laravel 11 结构更新 lang 目录
-   修正广告过期日期不能为空的错误
-   移除无用的 UpdateLog 模型
-   更新预设模型设定
-   更新安装程式
-   添加禁用用户注册功能
-   在网站编辑页面上将主题设置为 null 而非预设

### v4.0.0 2024-08-03

#### 新增

-   PHP 需求从 7.2+ 升级至 8.2+
-   Laravel 10 -> 11
-   jQuery 3.2.1 -> 3.7.1
-   更新所有 composer 依赖
-   添加数据库前缀以避免与 MySQL 保留关键字的表名衝突

#### 修复

-   修正当 CMS 未安装时导航 panel/xxxx 页面的错误
-   修正 php artisan migrate:fresh 不工作
-   修正当 $request-input 为 null 时页面控制器错误

#### 优化

-   合併旧迁移并重命名迁移文件
-   重命名资料库列 external_image -> external_thumbnail
-   更新预设 env 文件生成器
-   wncms_get_unique_slug() 将预设长度设为 8
-   所有 // 注释后应留有空格。例如 // Good 和 //Bad
-   重写 wncms.js 和 AuthenticatedSessionController 中的登入逻辑

#### 移除

-   移除 vendor 目录以缩小包大小。安装过程中将下载包
-   移除全域函数 wn()，全部替换为 wncms()
-   移除全域变数 $wn，全部替换为 $wncms
-   移除分析帮助程式以避免性能问题。用第三方分析工具替代
-   移除 wncms_get_theme_options() 帮助函数。在模型中使用 $website->get_options()

### v3.2.1 2024-05-19

#### 新增

-   新增数个旗次 SVG
-   增加更多翻译字段
-   后台各模组数据列表加入权限判断
-   后台新增菜单判断非管理员权限
-   预留 1 个代理 Dashboard，供日后开发使用

#### 修复

-   修复部分大小写错误

#### 优化

-   common.table_status.blade.php 可以判断更多状态，新增自定功能

### v3.2.0 2024-03-14

#### 修复

-   修复新版本安装时 site_name 为 null 报错

#### 优化

-   更新安装程序
-   使用 api_token 请求时，视爲登入

### v3.1.15 2024-03-12

#### 新增

-   新增 Page 模组的 API
-   新增菜单模组 API
-   PostHelper getList() 支持 $isRandom 参数，可以获取随机文章
-   Post 模组新增 getTags ，获取 Tag 列表，可传入 $type 参数
-   WebsiteHelpr 新增排序功能
-   优化主题设定判断可以分辨 0 和 未设定
-   新增一键複杂菜单功能

#### 修复

-   修复联络表单中 current_url 项被缓存问题
-   修复表单提交失败时按钮文字不显示

#### 优化

-   计划移除 Post 模组 getFirstCategory()，一律改为使用 getFirstTag('post_category')
-   优化手机版后台 UI
-   生成主题档案时，会自动替换 starter 字串为主题名称，无需再手动修改
-   主题名称按英文字母顺序排列
-   文章搜索前，先判断是否包括 keyword
-   优化主题设定判断，可以分辨 0 和 未设定
-   后台 Tag 显示相关模组数量
-   所有 Helper 预设改为不使用分页 $pageSize = 0

### v3.1.14 2023-12-17

#### 新增

-   批量複製文章功能

### v3.1.13 2023-12-12

#### 新增

-   模组列表的工具列，可以设定隐藏部分预设按钮，例如 'hideToolbarStatusFiller' => true 可以隐藏状态筛选下拉清单
-   联络表单增加提交后的 js 事件，可以针对成功及失败分别设定
-   后台联络表单增加筛选功能
-   后台联络表单增加汇出 XLXS/CSV 功能
-   增加翻译字段

#### 修复

-   修复假浏览量小数问题

#### 优化

-   调整部分后台 UI

### v3.1.12 2023-12-11

#### 新增

-   系统设定中，加入上传封面图片时，自动转换为 webp
-   模组加入额外参数功能 ExtraAttribute，使用 getExtraAttribute() 和 saveExtraAttribute()

#### 修复

-   修复主题设定中，开关的说明有时候会重覆显示

### v3.1.11 2023-12-08

#### 新增

-   更多翻译字段

#### 修复

-   修复主题设定图片若带括号()未能显示

#### 优化

-   utm 参数支持带到著陆以外的其他页面

### v3.1.10 2023-12-08

#### 新增

-   联络表单支持调整栏位顺序
-   新增数项系统设定，设定超级管理员收件帐号
-   新增 SMTP 测试电邮功能
-   更新部分翻译字段
-   联络表单现支持单选及多选
-   联络表单现支持检查是否所有必须填写项目都已填写

#### 修复

-   修复手机版主题设定因网站名称过长而跑版
-   修复部分模组未能批量删除
-   修复文章回收时，未有更新状态

#### 优化

-   更新预设 Email 模版，支持 html

### v3.1.9 2023-12-06

#### 新增

-   新增 Starter 主题的 style.css 和 main.js

#### 修复

-   修复部分主题页面的部件未能读取

#### 优化

-   获取文章时，如果指定了排除的分类 ID，也会包括没有分类的文章
-   更新表单系统，使用新的模版调用方法 $wncms->contact_form()->render()，直接生成表单结构
-   更新常见问题模组，增加更多栏位，可设定排序

### v3.1.8 2023-11-29

#### 新增

-   新增 json-id 结构

### v3.1.7 2023-11-21

#### 新增

-   优化后台手机版 UI
-   所有上传图片模组，支缓更多图片类型
-   新增一键汇入预设主题设定功能

#### 修复

-   修复文章上限少于分页数量时，可能不显示的问题

#### 优化

-   升级主题设定中的文本编辑器
-   更新翻译字段

### v3.1.6 2023-11-17

#### 新增

-   文章批量指派到网站功能
-   新增点击事件纪录，属性为[wncms-click]
-   新增显示网站浏览量 (有别于文章浏览量，网站浏览量为全局，查看多少文章都只计算 1 次)
-   更多翻译字段
-   系统设定新增浏览量，访问量冷却间隔，可以设定 60 分钟算一次，或 1440 分钟计算一次，自行输入分钟
-   广告位加入多筛选条件

### v3.1.5 2023-11-16

#### 新增

-   菜单项目可以新增描述
-   [开发者] 新增 Wiget 系统，用于建立 Page template，增加可重用性
-   [开发者] 初引入 Sortable JS 插件
-   [开发者] 后台 backend.blade.php 加入可堆叠 css 位置，使用@push('foot_css')
-   更多翻译字段
-   新增一键生成演示文章功能，方便开发
-   [开发者] 新增第 4 个常用简称涵数 gpto (getPageTemplateOption)，用于在 page 页面中，获取个别页面参数

#### 修复

-   修复 Ajax 联络表单返回讯息， 成功建立 改为 成功提交

#### 优化

-   页面樊组 option 栏位由 json 类型改为 text 类型，防止自动排序
-   修改单页加载顺序， template > plain > static ，都没有就跳转主页
-   修正以\#开头的菜单连结未能正常跳转锚点
-   修化手机版后台主题设定样式
-   [开发者] 大幅修改后台 input.blade.php 的判断条件，为可视化编辑器作准备

### v3.1.4 2023-11-04

#### 新增

-   [开发者] 模版中新增 $wncms 参数，等同于 $wn
-   [开发者] 新增 wncms()->paginateWithLimit() 方法，解决 paginate 不能与 limit 同时使用的问题
-   更多翻译字
-   缓存讚好数，每日使用排程任务更新到模组 like 栏位，可加速不需要实时讚好数的模组
-   更新 RecordViews 排程任务，可以纪录任何 collection，例如 visit, like, dislike
-   [开发者] 新增 $wn->addVersion()，不需要再于模版中加上 '?v=' . $wn->getVersion()，新功能输出时已包括 ?v=
-   引入 applicaion/ld+json 选项，可以 Push 到 `<head>` 中

#### 修复

-   修复 3.1.3 更新后，部分页面未能读取内容

#### 优化

-   预设主题 `<title>` 从「首页」改为网站名称
-   优化新增同名权限时的提示，不再是报错，而是提示已有重覆项目
-   [开发者] 重新命名前端视图中的统计部分 get_view -> get, record_like -> like, record_view -> record

### v3.1.3 2023-10-31

#### 新增

-   更新后台菜单模组图标
-   新增从文字中提取域名
-   广告更换为可以设定永久广告
-   [开发者] 新增页面模版功能，开发者可建立 Blade 档案，用于页面管理中，一键生成页面
-   修复颜色表选项不显示当前值
-   新增菜单 Tag 项目可以一键获取多国语言功能，获取后仍然可以自订各语言显示文字
-   新增更多预设广告位置
-   [开发者] 新增日期标示模块 resources/views/common/table_date.blade.php ，根据日期与当前时间的距离，以不同颜色标示
-   [开发者] 新判断某连结是否与当前页面连结相同，wncms()->isActiveUrl()
-   新增更多翻字段
-   新增文章排行榜功能 /psots/rank, /post/rank/{$period} ，可以按日週月年总排列
-   [开发者] 新增 WnMediaTraits，暂时只有 1 个功能，$this->addThumbnailFromRequest()，可以直接从 request 中上传图片
-   [开发者] WnTgTraits 新增 getFirstTag() 功能，以获取任何 Model 的第一个 Tag
-   [开发者] CacheHelper 新 get() 和 put() 功能
-   [开发者] MenuHelper 新增以输出 theme_page 连结，与 external_link 差不多
-   新增一键複製广告功

#### 修复

-   [开发者] 修复更新 Laravel 版本后，Stub 文件被覆盖，导致模组生成器模版还原为设设
-   修复会员后面版错误
-   修复开启缓存时，Tag 分页只显示第 1 页缓存问题
-   修复部分模组，管理员没有初始权限问题
-   修复菜单项目更新后，排序可能会乱掉问题

#### 优化

-   [开发者] 优代从字段中获取连结功能，可选择保留 www， wncms()->getDomainFromString($string, $includePort = true, $preserveWWW = false)

### v3.1.2 2023-10-28

#### 新增

-   使用新的 Redis 缓存库，可以清理个别标籤
-   新增后台一键清理缓存功能
-   [开发者] 新增从 String 获取域名+端口的方法，wncms()->getDomainFromString()

#### 优化

-   [开发者] 整理代码
-   [开发者] Theme helper 修改为不受缓存设定影响，即使关闭缓存，仍然会套用数据缓存的时间，预设为 3600 秒，以防闭闭缓存后，一个页面请求几十次数据库只为了读取主题设定
-   [开发者] 更新 CSRF 路径
-   修复主题设定中，Tinymce 编辑器无效
-   [开发者] TagHelper 中，getList() 新增可以只获取父级分 Tag
-   更新文章 AP
-   更新 WNCMS 安装程序，简化流程

### v3.1.1 2023-10-20

#### 新增

-   重製安装流程，更简单快速
-   [开发者] Starter 中的 tagify 栏位，改为可以输入开关字搜索
-   [开发者] 开发者可以自订管理员控制台项目
-   [开发者] 开发者可以自订会员主页项目
-   添加更多翻译字段

#### 修复

-   修复部分模组未能清除标籤

#### 优化

-   [开发者] 后台 Sidebar Model 部分重构，开发者可以自订后台 Model 菜单顺序，修改 Model 中的$menuPriority property
-   [开发者] 更新 controller.model.stub
-   重写注册会员逻辑

### v3.0.8 2023-10-18

#### 新增

-   [开发者] 更新 Wncms core class，可以任意配额参数 $wn->anything = 'something';
-   [开发者] 更新 Wncms core class，更有效重用 Helper Classes

### v3.0.7 2023-10-17

#### 新增

-   联络表单新增批量处理功能，可以批量设为已读，批量设为未读，批量设为已回覆
-   新增网站级的缓存
-   新增备用域名系统
-   部分后台页面开始增加数据数量资讯，例如总数，及当前正在显示第几条数据
-   新增广告模组

#### 优化

-   更新 Ajax 按钮发送数据模式
-   优化系统设定缓存
-   升级 tinymce 编辑器，使用 cloudflare CDN

### v3.0.6 2023-10-09

#### 新增

-   新增更多翻译字段
-   [开发者] 新增 table_url Blade 视图
-   [开发者] webphp 路游新增演示模组
-   [开发者] starter/index.bade.php 视图新增再製按钮
-   [开发者] Tag 模组新增 WnTagTraits 特徵，增 getAvailableTypes()，可以获取分类法列表 ['post_tag', 'video_category', etc]

### v3.0.5 2023-10-07

#### 新增

-   检查自定模组是否存在 (Custom{Model}Traits.php)

### v3.0.4 2023-10-07

#### 修复

-   修复部分模组小问题

#### 优化

-   优化后台 UI

### v3.0.3 2023-10-06

#### 优化

-   更新文章模组，修复会员权限问题
-   移动 Trait 目录到 Ap 目录下

### v3.0.2 2023-09-30

#### 新增

-   新增 Page 类型 (纯文字类型，由编辑器製作的模版，由主题提供的模版)

### 2.0.0 2021-07-28

-   全新版本发行! (相关更新纪录已隐藏)

### 1.0.0 2019-03-01

-   首版本发行 (相关更新纪录已隐藏)
