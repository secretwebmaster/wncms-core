<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{

    protected $table = 'payment_gateways';

    protected $guarded = [];

    protected $casts =[
        'attributes' => 'array',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-hand-holding-dollar'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public const STATUSES = [
        'active',
        'inactive',
    ];

    /**
     * Get the display name of the payment gateway.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->name;
    }

    /**
     * Get a specific parameter value.
     *
     * @param string $key
     * @return mixed|null
     */
    public function getParameter(string $key)
    {
        return $this->parameters[$key] ?? null;
    }

    /**
     * Instantiate the payment gateway processor class
     */
    public function processor()
    {
        $class = 'Wncms\\PaymentGateways\\' . ucfirst($this->slug);
        if (class_exists($class)) {
            return new $class($this);
        }

        $class = 'App\\PaymentGateways\\' . ucfirst($this->type);
        if (class_exists($class)) {
            return new $class($this);
        }

        return null;
    }
}
