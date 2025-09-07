<?php

namespace Wncms\Http\Controllers\Frontend;

use Wncms\Http\Controllers\Controller;

class FrontendController extends Controller
{
    protected $website;
    protected $theme;

    protected string $modelClass;
    protected array $cacheTags;
    protected string $singular;
    protected string $plural;

    public function __construct()
    {
        $this->website = wncms()->website()->get();

        // TODO: config destination in setting
        if (!$this->website) {
            redirect()->route('websites.create')->send();
        }

        $this->theme = $this->website->theme ?? 'default';

        $projectHelper = resource_path("views/frontend/theme/{$this->theme}/system/helpers.php");
        $packageHelper = WNCMS_CORE_PATH. "resources/views/frontend/theme/{$this->theme}/system/helpers.php";   
        if (file_exists($projectHelper)) {
            require_once $projectHelper;
        } elseif (file_exists($packageHelper)) {
            require_once $packageHelper;
        }

        $this->modelClass = $this->getModelClass();
    }

    public function getModelClass(): string
    {
        $className = class_basename(static::class);
        $modelKey = str()->before($className, 'Controller');

        return wncms()->getModelClass(str()->snake($modelKey));
    }

    protected function getModelTable()
    {
        $modelClass = $this->getModelClass();
        return (new $modelClass)->getTable();
    }

    protected function getModelCacheTags(): array
    {
        return $this->cacheTags ?? [$this->getModelTable()];
    }

    protected function getModelSingular(): string
    {
        return $this->singular ?? str()->singular($this->getModelTable());
    }

    protected function getModelPlural(): string
    {
        return $this->plural ?? str()->plural($this->getModelSingular());
    }
}
