<?php

namespace Wncms\Models\Builders;

use Illuminate\Database\Eloquent\Builder;

class AppendOnlySecurityEventBuilder extends Builder
{
    /**
     * Reject mass updates to the append-only event store.
     *
     * @param  array  $values
     *
     * @return never
     */
    public function update(array $values): never
    {
        throw new \LogicException('Security events are append-only.');
    }

    /**
     * Reject mass deletes from the append-only event store.
     *
     * @return never
     */
    public function delete(): never
    {
        throw new \LogicException('Security events are append-only.');
    }

    /**
     * Reject mass upserts that could mutate existing events.
     *
     * @param  array  $values
     * @param  array|string  $uniqueBy
     * @param  array|null  $update
     *
     * @return never
     */
    public function upsert(array $values, $uniqueBy, $update = null): never
    {
        throw new \LogicException('Security events are append-only.');
    }

    /**
     * Reject counter increments that mutate existing events.
     *
     * @param  string  $column
     * @param  float|int  $amount
     * @param  array  $extra
     *
     * @return never
     */
    public function increment($column, $amount = 1, array $extra = []): never
    {
        throw new \LogicException('Security events are append-only.');
    }

    /**
     * Reject counter decrements that mutate existing events.
     *
     * @param  string  $column
     * @param  float|int  $amount
     * @param  array  $extra
     *
     * @return never
     */
    public function decrement($column, $amount = 1, array $extra = []): never
    {
        throw new \LogicException('Security events are append-only.');
    }
}
