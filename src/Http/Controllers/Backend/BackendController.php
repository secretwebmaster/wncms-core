<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Facades\Wncms;
use Wncms\Http\Controllers\Controller;

abstract class BackendController extends Controller
{
    protected string $modelClass;

    public function __construct()
    {
        $this->modelClass = $this->getModelClass();
    }

    /**
     * Child controllers must implement this to return the associated model class.
     *
     * @return class-string
     */
    abstract protected function getModelClass(): string;

    /**
     * Flush the cache.
     * 
     * @param string|array|null $tag
     * @return bool
     */
    public function flush(string|array|null $tag = null)
    {
        return Wncms::cache()->flush($tag);
    }
}
