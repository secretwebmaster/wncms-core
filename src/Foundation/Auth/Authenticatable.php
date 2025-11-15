<?php

namespace Wncms\Foundation\Auth;

use Wncms\Models\BaseModel;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Foundation\Auth\Access\Authorizable as AuthorizableTrait;

/**
 * Custom WNCMS Authenticatable
 *
 * Laravel’s default Authenticatable extends Eloquent\Model, which prevents the User model
 * from extending WNCMS BaseModel.
 *
 * This class replaces Laravel’s version so User can:
 *   - extend BaseModel (required by WNCMS)
 *   - still use Laravel authentication (AuthenticatableTrait)
 *   - still use authorization & Spatie Permission (AuthorizableTrait)
 *
 * In short: this class exists so WNCMS User can be a real BaseModel AND a valid Laravel user.
 */

abstract class Authenticatable extends BaseModel implements
    AuthenticatableContract,
    AuthorizableContract
{
    use AuthenticatableTrait;
    use AuthorizableTrait;
}
