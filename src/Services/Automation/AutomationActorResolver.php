<?php

namespace Wncms\Services\Automation;

use Illuminate\Database\Eloquent\Model;

class AutomationActorResolver
{
    /**
     * Resolve the actor for an automation mutation.
     *
     * @param array $options
     * @param bool $writeMode
     * @return array
     */
    public function resolve(array $options = [], bool $writeMode = false): array
    {
        $actorUserId = $this->actorUserId($options);

        if ($actorUserId === null) {
            if ($writeMode) {
                return $this->failure(401, 'Automation write mode requires an actor user.', [
                    'actor' => ['required'],
                ]);
            }

            return [
                'code' => 200,
                'status' => 'pass',
                'message' => 'Actor is optional for dry-run mode.',
                'source' => 'none',
                'actor' => null,
                'errors' => [],
            ];
        }

        $user = $this->findUser($actorUserId);
        if (!$user) {
            return $this->failure(404, 'Automation actor user was not found.', [
                'actor_user_id' => [$actorUserId],
            ]);
        }

        return [
            'code' => 200,
            'status' => 'pass',
            'message' => 'Automation actor resolved.',
            'source' => $this->hasExplicitActor($options) ? 'explicit' : 'system',
            'actor' => $this->actorSummary($user),
            'model' => $user,
            'errors' => [],
        ];
    }

    /**
     * Resolve explicit or configured actor user id.
     *
     * @param array $options
     * @return int|null
     */
    protected function actorUserId(array $options): ?int
    {
        foreach (['actor_user_id', 'actor_user', 'user_id'] as $key) {
            if (!$this->hasValue($options[$key] ?? null)) {
                continue;
            }

            return (int) $options[$key];
        }

        $systemActorUserId = config('wncms.automation.system_actor_user_id');

        return $this->hasValue($systemActorUserId) ? (int) $systemActorUserId : null;
    }

    /**
     * Determine whether the caller provided an explicit actor.
     *
     * @param array $options
     * @return bool
     */
    protected function hasExplicitActor(array $options): bool
    {
        foreach (['actor_user_id', 'actor_user', 'user_id'] as $key) {
            if ($this->hasValue($options[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find a user model by id.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function findUser(int $userId): ?Model
    {
        $userClass = wncms()->getModelClass('user');

        return $userClass::query()->find($userId);
    }

    /**
     * Build a compact actor summary for automation metadata.
     *
     * @param \Illuminate\Database\Eloquent\Model $user
     * @return array
     */
    protected function actorSummary(Model $user): array
    {
        $roles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->values()->all()
            : [];

        return [
            'type' => 'user',
            'id' => (int) $user->getKey(),
            'model' => get_class($user),
            'roles' => $roles,
        ];
    }

    /**
     * Build a failed actor resolution result.
     *
     * @param int $code
     * @param string $message
     * @param array $errors
     * @return array
     */
    protected function failure(int $code, string $message, array $errors): array
    {
        return [
            'code' => $code,
            'status' => 'fail',
            'message' => $message,
            'source' => 'none',
            'actor' => null,
            'errors' => $errors,
        ];
    }

    /**
     * Check whether a value should be considered present.
     *
     * @param mixed $value
     * @return bool
     */
    protected function hasValue(mixed $value): bool
    {
        return $value !== null && $value !== '' && $value !== [];
    }
}
