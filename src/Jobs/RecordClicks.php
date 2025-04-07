<?php

namespace Wncms\Jobs;

use Wncms\Models\Click;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordClicks implements ShouldQueue
{
    use Queueable;

    protected $clickData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $clickData)
    {
        $this->clickData = $clickData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Click::create($this->clickData);
    }
}
