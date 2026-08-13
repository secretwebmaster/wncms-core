<?php

namespace Wncms\Tests\Fixtures\Api\V2\Overrides;

use Wncms\Models\BaseModel;

class TrustedWidget extends BaseModel
{
    public static $modelKey = 'trusted_widget';

    protected $table = 'users';

    protected $guarded = [];
}
