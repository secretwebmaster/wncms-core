<?php

namespace Wncms\Interfaces;

interface ApiModelInterface
{
    /**
     * Whether this model exposes API capabilities.
     */
    public static function hasApi(): bool;

    /**
     * Return the list of API routes this model supports.
     *
     * Each route item should follow:
     * [
     *     'name' => 'api.v1.posts.index',
     *     'key' => 'wncms_api_post_index',
     *     'action' => 'index',
     * ]
     */
    public static function getApiRoutes(): array;

    /**
     * Add one API route definition into the model.
     */
    public static function addApiRoute(array $route): void;

    /**
     * Remove one API route definition by its unique 'key'.
     */
    public static function removeApiRoute(string $key): void;
}
