<?php

namespace Wncms\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Wncms\Models\ApiSecurityEvent;

class ApiSecurityEventRecorded
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a redacted security-event notification.
     *
     * @param  \Wncms\Models\ApiSecurityEvent  $event
     *
     * @return void
     */
    public function __construct(public ApiSecurityEvent $event)
    {
    }
}
