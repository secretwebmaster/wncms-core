<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Model;
use Wncms\Tags\HasTags;
use Wncms\Traits\HasMultisite;

class BaseModel extends Model
{
    use HasMultisite;
    use HasTags;

    protected static function booted()
    {
        if (defined(static::class . '::TAG_TYPES')) {
            (new static())->addAllowedTagTypes(static::TAG_TYPES);
        }
    }

    /**
     * Return the parent model's class name (without namespace).
     */
    public function getParentModelName(): string
    {
        return class_basename($this);
    }

    // public function getAttribute($key)
    // {
    //     // Retrieve the value using the original method
    //     $value = parent::getAttribute($key);

    //     // Check if the key exists in the model's attributes
    //     if (array_key_exists($key, $this->attributes)) {
    //         // Fire the event, passing the model, the attribute key, and the value by reference
    //         event('model.getting.attribute', [$this, $key, &$value]);
    //     }

    //     // Return the value, but make sure it's properly cast to its original type
    //     return $this->hasCast($key) && is_string($value)
    //         ? $this->castAttribute($key, $value)
    //         : $value;
    // }

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (array_key_exists($key, $this->attributes)) {
            $event = new \Wncms\Events\ModelGettingAttribute($this, $key, $value);
            event($event);

            $value = $event->value;
        }

        if ($this->hasCast($key)) {
            // avoid double casting arrays/objects
            if (is_string($value) || is_null($value)) {
                return $this->castAttribute($key, $value);
            }

            return $value;
        }

        return $value;
    }

    /**
     * Return a localized or user-defined display name for the model.
     */
    public static function getModelName(?string $locale = null): string
    {
        $short = strtolower(class_basename(static::class));
        $packageId = static::getPackageId();

        // 1. user override (namespaced)
        if (function_exists('gss') && $packageId) {
            $custom = gss("{$packageId}::{$short}_model_name");
            if (!empty($custom)) {
                return $custom;
            }
        }

        // 2. translation lookup (namespaced)
        if ($packageId) {
            $translated = __("{$packageId}::word.{$short}", locale: $locale);
            if ($translated !== "{$packageId}::word.{$short}") {
                return $translated;
            }
        }

        // 3. fallback to core translation
        $coreTranslated = __("wncms::word.{$short}", locale: $locale);
        if ($coreTranslated !== "wncms::word.{$short}") {
            return $coreTranslated;
        }

        // 4. fallback to humanized class name
        return ucfirst(str_replace('_', ' ', $short));
    }

    /**
     * Return the package ID for namespacing model-related data.
     * Packages should override this method to return their own ID.
     */
    public static function getPackageId(): ?string
    {
        return property_exists(static::class, 'packageId')
            ? static::$packageId
            : null;
    }
}
