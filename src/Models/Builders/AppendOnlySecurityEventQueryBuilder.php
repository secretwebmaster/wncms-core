<?php

namespace Wncms\Models\Builders;

use Illuminate\Database\Query\Builder;

class AppendOnlySecurityEventQueryBuilder extends Builder
{
    /**
     * Reject insert-or-ignore writes because they can be used to bypass event creation policy.
     *
     * @param  array  $values
     *
     * @return never
     */
    public function insertOrIgnore(array $values): never
    {
        $this->rejectMutation();
    }

    /**
     * Reject returning insert-or-ignore writes because they can bypass event creation policy.
     *
     * @param  array  $values
     * @param  array  $returning
     * @param  array|string|null  $uniqueBy
     *
     * @return never
     */
    public function insertOrIgnoreReturning(array $values, array $returning = ['*'], array|string|null $uniqueBy = null): never
    {
        $this->rejectMutation();
    }

    /**
     * Reject insert-select writes because they can bypass event creation policy.
     *
     * @param  array  $columns
     * @param  mixed  $query
     *
     * @return never
     */
    public function insertUsing(array $columns, $query): never
    {
        $this->rejectMutation();
    }

    /**
     * Reject insert-select-ignore writes because they can bypass event creation policy.
     *
     * @param  array  $columns
     * @param  mixed  $query
     *
     * @return never
     */
    public function insertOrIgnoreUsing(array $columns, $query): never
    {
        $this->rejectMutation();
    }

    /**
     * Reject mass updates to the append-only event store.
     *
     * @param  array  $values
     *
     * @return never
     */
    public function update(array $values): never
    {
        $this->rejectMutation();
    }

    /**
     * Reject PostgreSQL update-from writes to the append-only event store.
     *
     * @param  array  $values
     *
     * @return never
     */
    public function updateFrom(array $values): never
    {
        $this->rejectMutation();
    }

    /**
     * Reject update-or-insert writes to the append-only event store.
     *
     * @param  array  $attributes
     * @param  array|callable  $values
     *
     * @return never
     */
    public function updateOrInsert(array $attributes, array|callable $values = []): never
    {
        $this->rejectMutation();
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
    public function upsert(array $values, array|string $uniqueBy, ?array $update = null): never
    {
        $this->rejectMutation();
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
        $this->rejectMutation();
    }

    /**
     * Reject multi-column counter increments that mutate existing events.
     *
     * @param  array  $columns
     * @param  array  $extra
     *
     * @return never
     */
    public function incrementEach(array $columns, array $extra = []): never
    {
        $this->rejectMutation();
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
        $this->rejectMutation();
    }

    /**
     * Reject multi-column counter decrements that mutate existing events.
     *
     * @param  array  $columns
     * @param  array  $extra
     *
     * @return never
     */
    public function decrementEach(array $columns, array $extra = []): never
    {
        $this->rejectMutation();
    }

    /**
     * Reject deletes from the append-only event store.
     *
     * @param  mixed  $id
     *
     * @return never
     */
    public function delete($id = null): never
    {
        $this->rejectMutation();
    }

    /**
     * Reject table truncation from the append-only event store.
     *
     * @return never
     */
    public function truncate(): never
    {
        $this->rejectMutation();
    }

    /**
     * Throw the shared append-only violation.
     *
     * @return never
     */
    protected function rejectMutation(): never
    {
        throw new \LogicException('Security events are append-only.');
    }
}
