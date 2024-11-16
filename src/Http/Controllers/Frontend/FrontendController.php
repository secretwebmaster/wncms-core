<?php

namespace Wncms\Http\Controllers\Frontend;

use Wncms\Http\Controllers\Controller;

class FrontendController extends Controller
{
    protected $website;
    protected $theme;

    public function __construct()
    {
        $this->website = wncms()->website()->get();

        // TODO: config destination in setting
        if (!$this->website) {
            redirect()->route('websites.create')->send();
        }

        $this->theme = $this->website->theme ?? 'default';
    }
}
