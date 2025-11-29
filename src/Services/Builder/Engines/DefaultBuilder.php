<?php

namespace Wncms\Services\Builder\Engines;

use Wncms\Models\Page;
use Wncms\Models\PageBuilderContent;

class DefaultBuilder
{
    /**
     * Load latest builder payload for the page.
     */
    public function load(Page $page)
    {
        $content = $page->latestBuilderContent('default');

        return $content?->payload ?? [
            'html' => '',
            'css' => '',
            'components' => [],
            'styles' => [],
            'assets' => [],
        ];
    }

    /**
     * Save payload into a new version row.
     */
    public function save(Page $page, array $payload)
    {
        // Get latest version number
        $latestVersion = PageBuilderContent::where('page_id', $page->id)
            ->where('builder_type', 'default')
            ->max('version');

        $nextVersion = ($latestVersion ?: 0) + 1;

        return PageBuilderContent::create([
            'page_id' => $page->id,
            'builder_type' => 'default',
            'version' => $nextVersion,
            'payload' => $payload,
        ]);
    }

    /**
     * Render frontend page from payload.
     */
    public function render(Page $page)
    {
        $content = $page->latestBuilderContent('default');

        if (! $content) {
            return ''; // no builder content yet
        }

        $payload = $content->payload;

        $html = $payload['html'] ?? '';
        $css  = $payload['css']  ?? '';

        return wncms()->view('frontend.pages.builder.render', [
            'html' => $html,
            'css'  => $css,
            'page' => $page,
        ])->render();
    }
}
