<?php

use Laravel\Mcp\Facades\Mcp;
use Wncms\Mcp\Servers\WncmsServer;

Mcp::local(config('wncms.mcp.server', 'wncms'), WncmsServer::class);
