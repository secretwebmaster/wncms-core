<?php

namespace Wncms\Tests\Fixtures\Api\V2;

use Illuminate\Database\Eloquent\Model;

class MismatchedModelKey extends Model
{
    public static $modelKey = 'different_model_key';
}
