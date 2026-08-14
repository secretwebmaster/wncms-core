<?php

namespace Wncms\Auth\Api\V2;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Wncms\Models\User;

final class ActorWebsiteAccess
{
    /**
     * Determine whether an actor receives the Blade administrator website scope.
     *
     * @param  \Wncms\Models\User  $actor
     *
     * @return bool
     */
    public function isAdministrator(User $actor): bool
    {
        return method_exists($actor, 'hasRole') && $actor->hasRole(['admin', 'superadmin']);
    }

    /**
     * Return every website currently accessible to an actor in stable order.
     *
     * Administrators inherit all existing websites, matching the Blade backend.
     * Other actors remain limited to their explicit website relationship.
     *
     * @param  \Wncms\Models\User  $actor
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    public function websites(User $actor): Collection
    {
        $websiteClass = wncms()->getModelClass('website');
        $website = new $websiteClass;
        $qualifiedKey = $website->qualifyColumn($website->getKeyName());
        $query = $this->isAdministrator($actor) ? $websiteClass::query() : $actor->websites();

        return $query->orderBy($qualifiedKey)->get();
    }

    /**
     * Return stable website IDs currently accessible to an actor.
     *
     * @param  \Wncms\Models\User  $actor
     *
     * @return array<int, int>
     */
    public function websiteIds(User $actor): array
    {
        return $this->websites($actor)
            ->map(static fn (Model $website): int => (int) $website->getKey())
            ->all();
    }

    /**
     * Return requested website IDs that remain inside the actor boundary.
     *
     * @param  \Wncms\Models\User  $actor
     * @param  array<int, int>  $requestedIds
     *
     * @return array<int, int>
     */
    public function matchingWebsiteIds(User $actor, array $requestedIds): array
    {
        $allowedIds = $this->websiteIds($actor);

        return array_values(array_intersect($requestedIds, $allowedIds));
    }

    /**
     * Determine whether an actor currently owns, belongs to, or administrates a website.
     *
     * @param  \Wncms\Models\User  $actor
     * @param  \Illuminate\Database\Eloquent\Model  $website
     *
     * @return bool
     */
    public function canAccess(User $actor, Model $website): bool
    {
        if ($this->isAdministrator($actor)) {
            return true;
        }

        if ((string) $website->user_id === (string) $actor->getKey()) {
            return true;
        }

        return $actor->websites()->whereKey($website->getKey())->exists();
    }
}
