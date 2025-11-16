<?php

namespace Wncms\Interfaces;

interface BaseModelInterface
{
    /**
     * Short model key used in tag types.
     * Example: post, product, video, link
     */
    public static function getModelKey(): string;

    /**
     * Tag configuration for this model.
     *
     * Example:
     * [
     *     'category' => [
     *         'full'  => 'product_category',
     *         'route' => 'frontend.products.tag',
     *     ],
     *     'tag' => [
     *         'route' => 'frontend.products.tag', // full auto = product_tag
     *     ],
     * ]
     */
    public static function getTagMeta(): array;
}
