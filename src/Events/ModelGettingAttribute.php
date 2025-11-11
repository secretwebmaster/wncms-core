<?php

namespace Wncms\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

class ModelGettingAttribute
{
    use SerializesModels;

    public Model $model;
    public string $key;
    public mixed $value;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed   $value
     */
    public function __construct(Model $model, string $key, mixed &$value)
    {
        $this->model = $model;
        $this->key   = $key;
        $this->value = $value;
    }
}
