<?php

namespace Wncms\Api\V2\Risk;

use Illuminate\Database\Eloquent\Model;

final class WebsiteBindingResolver
{
    /**
     * Resolve one canonical website binding shared by planning and mutation.
     *
     * @param  array<string, mixed>  $input
     */
    public function resolve(array $input, string $modelClass, string $action, ?Model $existing = null): WebsiteBinding
    {
        $scoped = method_exists($modelClass, 'isWebsiteScopedModel') && $modelClass::isWebsiteScopedModel();
        $hasId = array_key_exists('website_id', $input);
        $hasIds = array_key_exists('website_ids', $input);
        $hasKey = array_key_exists('website_key', $input);
        $supplied = $hasId || $hasIds || $hasKey;

        if (! $scoped) {
            return new WebsiteBinding([], $supplied, false);
        }

        if (! $supplied) {
            if ($action === 'update' && $existing !== null && method_exists($existing, 'websites')) {
                return new WebsiteBinding($this->existingIds($existing), false, false);
            }

            throw new RiskContextException('validation.failed', 422);
        }

        $id = $hasId ? $this->scalarId($input['website_id']) : null;
        $keyId = $hasKey ? $this->keyId($input['website_key']) : null;
        $ids = $hasIds ? $this->listIds($input['website_ids']) : null;
        if (($hasId && $id === null) || ($hasKey && $keyId === null) || ($hasIds && $ids === [])) {
            throw new RiskContextException('validation.failed', 422);
        }
        if ($id !== null && $keyId !== null && $id !== $keyId) {
            throw new RiskContextException('validation.failed', 422);
        }
        $selected = $id ?? $keyId;
        if ($selected !== null && $ids !== null && ! in_array($selected, $ids, true)) {
            throw new RiskContextException('validation.failed', 422);
        }

        $canonical = $ids ?? ($selected === null ? [] : [$selected]);
        if ($canonical === []) {
            throw new RiskContextException('validation.failed', 422);
        }
        $mode = method_exists($modelClass, 'getWebsiteMode') ? $modelClass::getWebsiteMode() : 'global';
        if ($mode === 'single' && count($canonical) !== 1) {
            throw new RiskContextException('validation.failed', 422);
        }

        return new WebsiteBinding($canonical, true, in_array($action, ['store', 'update'], true));
    }

    /**
     * Return the current canonical website membership.
     *
     * @return array<int, int>
     */
    private function existingIds(Model $model): array
    {
        $relation = $model->websites();
        $ids = $relation->pluck($relation->getRelated()->qualifyColumn($relation->getRelated()->getKeyName()))
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }

    private function scalarId(mixed $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false
            ? (int) $value
            : null;
    }

    private function keyId(mixed $value): ?int
    {
        return preg_match('/^website:([1-9][0-9]*)$/D', (string) $value, $matches) === 1
            ? (int) $matches[1]
            : null;
    }

    /** @return array<int, int> */
    private function listIds(mixed $value): array
    {
        if ($value === null || $value === '' || ! is_array($value)) {
            return [];
        }
        $ids = [];
        foreach ($value as $item) {
            $id = $this->scalarId($item);
            if ($id === null) {
                throw new RiskContextException('validation.failed', 422);
            }
            $ids[] = $id;
        }
        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }
}
