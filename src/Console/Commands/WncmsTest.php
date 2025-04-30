<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;

class WncmsTest extends Command
{
    protected $signature = 'wncms:test';
    protected $description = '測試 WNCMS 管理器功能';

    protected $postManager;

    public function handle()
    {
        $this->postManager = wncms()->post();

        $this->info("===============================");

        $this->info("🔍 開始測試 PostManager");
        $this->checkPostManager();
        $this->info("✅ PostManager 測試完成");
        $this->info("===============================");

        $this->info("🔍 開始測試 TagManager");
        $this->checkTagManager();
        $this->info("✅ TagManager 測試完成");
        $this->info("===============================");

        $this->info("🔍 開始測試 LinkManager");
        $this->checkLinkManager();
        $this->info("✅ LinkManager 測試完成");
        $this->info("===============================");
    }

    //! Common
    protected function check(bool $condition, string $message): void
    {
        if ($condition) {
            $this->info("【實際結果】✅ 成功：$message");
        } else {
            $this->error("【實際結果】❌ 失敗：$message");
        }
        $this->line("--------------------------------------------------");
    }

    //! PostManager
    protected function checkPostManager()
    {
        $this->testPostManagerGet();
        $this->testPostManagerGetBySlug();

        $this->testPostManagerGetList();
        $this->testPostManagerGetByTag();
        $this->testPostManagerGetRelated();
        $this->testPostManagerGetByTagWithoutTagType();
    }

    protected function testPostManagerGet()
    {
        $this->line("【測試項目】以 ID 取得文章");

        $postId = 1;
        $this->line("【期望結果】取得 ID 為 {$postId} 的文章");

        $options = ['id' => $postId, 'cache' => false];
        $post = $this->postManager->get($options);
        $this->line("【實際輸出】{$post?->id}");

        $this->check($post?->id === $postId, "能夠正確透過 ID 取得文章");
    }

    protected function testPostManagerGetBySlug()
    {
        $this->line("【測試項目】以 Slug 取得文章");

        $post = wncms()->getModel('post')::first();
        if (!$post) {
            $this->warn("⚠️ 無任何文章可供 slug 測試");
            return;
        }

        $slug = $post->slug;
        $this->line("【期望結果】取得 Slug 為 {$slug} 的文章");

        $result = $this->postManager->get(['slug' => $slug, 'cache' => false]);
        $this->line("【實際輸出】" . ($result?->slug ?? '❌ 未找到'));

        $this->check($result?->slug === $slug, "get(['slug' => ...]) 能正確取得文章");
    }

    protected function testPostManagerGetList()
    {
        $this->line("【測試項目】取得多篇文章列表");

        $count = 3;
        $this->line("【期望結果】取得 {$count} 筆文章");

        $results = $this->postManager->getList(['count' => $count, 'cache' => false]);
        $this->line("【實際輸出】{$results->count()} 筆");

        $this->check($results->count() === $count, "getList 能正確限制筆數為 {$count}");
    }

    protected function testPostManagerGetByKeyword()
    {
        $this->line("【測試項目】以關鍵字搜尋文章");

        $keywords = ['Laravel', 'CMS'];
        $this->line("【期望結果】取得包含關鍵字 " . implode(', ', $keywords) . " 的文章");

        $results = $this->postManager->getList([
            'keywords' => $keywords,
            'count' => 3,
            'cache' => false,
        ]);

        $this->line("【實際輸出】共 {$results->count()} 筆");
        $this->check($results->count() > 0, "getList 能正確搜尋關鍵字文章");
    }

    protected function testPostManagerGetByTag()
    {
        $this->line("【測試項目】以標籤取得文章");

        $tagName = '測試分類1';
        $tagType = 'post_category';
        $this->line("【期望結果】取得標籤名稱為 '{$tagName}'、類型為 '{$tagType}' 的文章");

        $results = $this->postManager->getList([
            'tags' => [$tagName],
            'tag_type' => $tagType,
            'cache' => false,
        ]);

        $this->line("【實際輸出】共取得 {$results->count()} 筆文章");
        $this->check($results->count() > 0, "getList 能正確取得含標籤文章");
    }

    protected function testPostManagerGetRelated()
    {
        $this->line("【測試項目】取得相關文章");

        $tagName = '測試分類1';
        $tagType = 'post_category';
        $posts = wncms()->getModel('post')::withAnyTags([$tagName], $tagType)->get();
        $expectedCount = $posts->count() - 1;
        $this->line("【期望結果】取得標籤名稱為 '{$tagName}'、類型為 '{$tagType}' 的文章，不包含自己，共 {$expectedCount} 筆");

        $results = $this->postManager->getRelated($posts->first(), [
            'cache' => false,
        ]);

        $count = $results->count();
        $this->line("【實際輸出】{$count} 筆");

        $this->check($count == $expectedCount, "getRelated 能正確取得相關文章");
        $this->line("--------------------------------------------------");
    }

    protected function testPostManagerGetByTagWithoutTagType()
    {
        $this->line("【測試項目】以標籤取得文章（未指定 tag_type）");

        $tagName = '測試分類1';
        $this->line("【期望結果】使用 PostManager 預設 tag_type 'post_category'，並成功取得標籤名稱為 '{$tagName}' 的文章");

        $results = $this->postManager->getList([
            'tags' => [$tagName],
            'cache' => false,
        ]);

        $this->line("【實際輸出】共取得 {$results->count()} 筆文章");
        $this->check($results->count() > 0, "getList 在未指定 tag_type 時，能使用預設值正確取得資料");
        $this->line("--------------------------------------------------");
    }

    //! TagManager
    protected function checkTagManager()
    {
        $this->testTagManagerGetByName();
        $this->testTagManagerGetList();
        $this->testTagManagerGetArray();
        $this->testTagManagerGetTypes();
        $this->testTagManagerGetTagKeywordList();
        $this->testTagManagerGetTagsToBind();
        $this->testTagManagerGetTagifyDropdownItems();
    }

    protected function testTagManagerGetByName()
    {
        $this->line("【測試項目】透過名稱取得標籤");

        $name = '測試分類1';
        $result = wncms()->tag()->getByName($name, 'post_category', [], null, false);

        $this->line("【期望結果】取得名稱為 '{$name}' 的標籤");
        $this->line("【實際輸出】" . ($result?->name ?? '❌ 無結果'));

        $this->check($result?->name === $name, "getByName() 能正確取得標籤");
    }

    protected function testTagManagerGetList()
    {
        $this->line("【測試項目】取得標籤列表");

        $tags = wncms()->tag()->getList([
            'tag_type' => 'post_category',
            'count' => 2,
            'cache' => false,
        ]);

        $this->line("【期望結果】取得 2 筆 post_category 標籤");
        $this->line("【實際輸出】取得 " . $tags->count() . " 筆");

        $this->check($tags->count() === 2, "getList() 能限制筆數為 2");
    }

    protected function testTagManagerGetArray()
    {
        $this->line("【測試項目】取得標籤陣列");

        $array = wncms()->tag()->getArray('post_category', 2);

        $this->line("【期望結果】陣列數量為 2");
        $this->line("【實際輸出】" . count($array) . " 筆");

        $this->check(count($array) === 2, "getArray() 能轉為陣列並限制筆數");
    }

    protected function testTagManagerGetTypes()
    {
        $this->line("【測試項目】取得所有標籤類型");

        $types = wncms()->tag()->getTypes();

        $this->line("【期望結果】至少包含 'post_category'");
        $this->line("【實際輸出】" . implode(', ', $types));

        $this->check(in_array('post_category', $types), "getTypes() 能取得標籤類型");
    }

    protected function testTagManagerGetTagKeywordList()
    {
        $this->line("【測試項目】取得標籤關鍵字列表");

        $data = wncms()->tag()->getTagKeywordList('post_category');

        $this->line("【期望結果】回傳陣列且格式正確");
        $this->line("【實際輸出】共 " . count($data) . " 筆");

        $this->check(is_array($data), "getTagKeywordList() 格式正確");
    }

    protected function testTagManagerGetTagsToBind()
    {
        $this->line("【測試項目】從內容偵測應綁定標籤");

        $contents = ['哈哈']; // 關鍵字需已存在於資料庫的某標籤內
        $results = wncms()->tag()->getTagsToBind('post_category', $contents);

        $this->line("【期望結果】至少回傳一個 tag id");
        $this->line("【實際輸出】" . implode(', ', $results));

        $this->check(count($results) > 0, "getTagsToBind() 能正確偵測標籤");
    }

    protected function testTagManagerGetTagifyDropdownItems()
    {
        $this->line("【測試項目】取得 tagify 格式下拉資料");

        $items = wncms()->tag()->getTagifyDropdownItems('post_category');

        $this->line("【期望結果】格式為 [{name:..., value:...}]");
        $this->line("【實際輸出】" . json_encode($items[0] ?? []));

        $first = $items[0] ?? [];
        $this->check(isset($first['name']) && isset($first['value']), "getTagifyDropdownItems() 回傳格式正確");
    }

    //! LinkManager
    protected function checkLinkManager()
    {
        $this->testLinkManagerGet();
        $this->testLinkManagerGetBySlug();
        $this->testLinkManagerGetList();
        $this->testLinkManagerGetByTag();
        $this->testLinkManagerExcludeByTagId();
    }

    protected function testLinkManagerGet()
    {
        $this->line("【測試項目】以 ID 取得連結");

        $link = wncms()->getModel('link')::inRandomOrder()->first();
        if (!$link) {
            $this->warn("⚠️ 無任何連結可供測試");
            return;
        }

        $linkId = $link->id;
        $this->line("【期望結果】取得 ID 為 {$linkId} 的連結");

        $result = wncms()->link()->get(['id' => $linkId, 'cache' => false]);
        $this->line("【實際輸出】" . ($result?->id ?? '❌ 無結果'));

        $this->check($result?->id === $linkId, "get() 能正確取得連結");
    }

    protected function testLinkManagerGetBySlug()
    {
        $this->line("【測試項目】以 Slug 取得連結");

        $link = wncms()->getModel('link')::first();
        if (!$link) {
            $this->warn("⚠️ 無任何連結可供 slug 測試");
            return;
        }

        $slug = $link->slug;
        $this->line("【期望結果】取得 Slug 為 {$slug} 的連結");

        $result = wncms()->link()->get(['slug' => $slug, 'cache' => false]);
        $this->line("【實際輸出】" . ($result?->slug ?? '❌ 未找到'));

        $this->check($result?->slug === $slug, "get(['slug' => ...]) 能正確取得連結");
    }

    protected function testLinkManagerGetList()
    {
        $this->line("【測試項目】取得連結列表");

        $count = 3;
        $this->line("【期望結果】取得 {$count} 筆連結");

        $results = wncms()->link()->getList(['count' => $count, 'cache' => false]);
        $this->line("【實際輸出】{$results->count()} 筆");

        $this->check($results->count() === $count, "getList() 能正確限制筆數為 {$count}");
    }

    protected function testLinkManagerGetByTag()
    {
        $this->line("【測試項目】以標籤取得連結");

        $tagName = '鏈接分類1';
        $tagType = 'link_category';

        $this->line("【期望結果】取得標籤名稱為 '{$tagName}' 的連結");

        $expectedCount = wncms()->getModel('link')::withAnyTags([$tagName], $tagType)->count();
        $results = wncms()->link()->getList([
            'tags' => [$tagName],
            'tag_type' => $tagType,
            'cache' => false,
        ]);
        $actualCount = $results->count();

        $this->line("【實際輸出】LinkManager: {$actualCount}，實際模型查詢: {$expectedCount}");

        $this->check($actualCount > 0 && ($actualCount === $expectedCount), "getList() 能正確取得含標籤連結，筆數一致");
    }

    protected function testLinkManagerExcludeByTagId()
    {
        $this->line("【測試項目】排除特定標籤 ID 的連結");
        $tagName = '鏈接分類1';
        $linkModel = wncms()->getModel('link');

        $tag = $linkModel::withAnyTags([$tagName], 'link_category')
            ->with('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->where('type', 'link_category')
            ->first();
        

        if (!$tag) {
            $this->warn("⚠️ 無可用的連結分類標籤可供測試");
            return;
        }

        $tagId = $tag->id;
        $this->line("【排除條件】Tag ID = {$tagId} (type = link_category)");

        // Count links with the tag
        $excludedCount = $linkModel::where('status', 'active')->withAnyTags([$tag->name], 'link_category')->count();

        // Count all links
        $totalCount = $linkModel::where('status', 'active')->count();

        // Use manager to fetch with exclusion
        $results = wncms()->link()->getList([
            'excluded_tag_ids' => [$tagId],
            'cache' => false,
        ]);
        $actualCount = $results->count();

        $this->line("【期望結果】筆數應為所有連結數 {$totalCount} 減去含該標籤的連結數 {$excludedCount}");
        $this->line("【實際輸出】LinkManager 筆數：{$actualCount}");

        $this->check($actualCount === ($totalCount - $excludedCount), "能正確排除指定 tag ID 的連結");
    }
}
