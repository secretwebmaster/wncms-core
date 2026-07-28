<?php

namespace Wncms\Mcp\Servers;

use Laravel\Mcp\Server;
use Wncms\Mcp\Tools\InspectLinkTool;
use Wncms\Mcp\Tools\ListLinksTool;

class WncmsServer extends Server
{
    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        ListLinksTool::class,
        InspectLinkTool::class,
    ];
}
