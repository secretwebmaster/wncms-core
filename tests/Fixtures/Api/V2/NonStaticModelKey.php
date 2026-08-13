<?php

namespace Wncms\Tests\Fixtures\Api\V2;

use Illuminate\Database\Eloquent\Model;

class NonStaticModelKey extends Model
{
    public $modelKey = 'non_static_model_key';
}
