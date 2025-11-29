<?php

namespace Wncms\Services\Builder;

use Wncms\Services\Builder\Engines\DefaultBuilder;
use Wncms\Models\Page;
use Wncms\Models\PageBuilderContent;

class BuilderManager
{
    protected array $engines = [];

    public function __construct()
    {
        $this->register('default', DefaultBuilder::class);
    }

    /**
     * Resolve a builder engine by type.
     */
    public function engine(string $type = 'default')
    {
        $type = $type ?: 'default';

        $class = $this->engines[$type] ?? $this->engines['default'];

        return new $class;
    }

    /**
     * Load builder content for a page.
     */
    public function load(Page $page, string $type = 'default')
    {
        return $this->engine($type)->load($page);
    }

    /**
     * Save builder content and create version.
     */
    public function save(Page $page, array $payload, string $type = 'default')
    {
        return $this->engine($type)->save($page, $payload);
    }

    /**
     * Render page front-end from builder payload.
     */
    public function render(Page $page, string $type = 'default')
    {
        return $this->engine($type)->render($page);
    }

    /**
     * Allow future builders to be registered.
     */
    public function register(string $type, string $engineClass): void
    {
        $this->engines[$type] = $engineClass;
    }

    public function getEngines(): array
    {
        return $this->engines;
    }
}
