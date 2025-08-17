<?php

namespace Wncms\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Wncms\Traits\HasMultisite;

class WncmsModel extends Model
{
    use HasMultisite;

    public function getAttribute($key)
    {
        // Retrieve the value using the original method
        $value = parent::getAttribute($key);

        // Check if the key exists in the model's attributes
        if (array_key_exists($key, $this->attributes)) {
            // Fire the event, passing the model, the attribute key, and the value by reference
            event('model.getting.attribute', [$this, $key, &$value]);
        }

        // Return the value, but make sure it's properly cast to its original type
        return $this->hasCast($key) ? $this->castAttribute($key, $value) : $value;
    }
}