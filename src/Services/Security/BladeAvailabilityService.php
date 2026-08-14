<?php

namespace Wncms\Services\Security;

use Illuminate\Support\Facades\Log;

final class BladeAvailabilityService
{
    public function __construct(private SecurityEventService $events) {}

    public function state(): BladeAvailabilityState
    {
        $installed = function_exists('wncms_is_installed') && wncms_is_installed();
        if (! $installed) {
            return new BladeAvailabilityState('missing', true, false);
        }

        try {
            $modelClass = wncms()->getModelClass('setting');
            $setting = $modelClass::query()->where('key', 'blade_enabled')->first();
            if ($setting === null) {
                return new BladeAvailabilityState('missing', true, true);
            }

            $enabled = $this->parseBoolean($setting->value);
            if ($enabled === null) {
                return new BladeAvailabilityState('invalid', false, true, $setting->value);
            }

            return new BladeAvailabilityState('found', $enabled, true, $setting->value);
        } catch (\Throwable $exception) {
            Log::warning('WNCMS Blade availability policy could not be read.', ['exception' => $exception::class]);
            try {
                $this->events->record('security.blade.policy_unavailable', 'error', 'failed', [
                    'surface' => app()->runningInConsole() ? 'cli' : 'web',
                    'context' => ['reason' => 'blade_policy_unavailable'],
                ]);
            } catch (\Throwable) {
                // The policy and audit stores may share the same outage.
            }

            return new BladeAvailabilityState('unavailable', false, true, null, ['policy_unavailable']);
        }
    }

    public function enable(string $surface): BladeAvailabilityState
    {
        $warnings = [];
        try {
            $this->mutate(true, $surface, $warnings);
        } catch (\Throwable $exception) {
            // Enabling is the emergency recovery path: availability wins over audit failure.
            $this->persist(true, $warnings);
            $warnings[] = 'audit_unavailable';
            Log::critical('WNCMS Blade was enabled without a security event.', ['surface' => $surface, 'exception' => $exception::class]);
        }

        return $this->withWarnings($this->state(), $warnings);
    }

    public function disable(string $surface): BladeAvailabilityState
    {
        $warnings = [];
        $this->mutate(false, $surface, $warnings);

        return $this->withWarnings($this->state(), $warnings);
    }

    /** @param array<int, string> $warnings */
    private function mutate(bool $enabled, string $surface, array &$warnings): void
    {
        $this->events->withinTransaction(function () use ($enabled, &$warnings): void {
            $this->persist($enabled, $warnings);
        }, [
            'type' => $enabled ? 'security.blade.enabled' : 'security.blade.disabled',
            'severity' => 'warning',
            'outcome' => 'succeeded',
            'context' => ['surface' => $surface, 'context' => ['policy_state' => $enabled ? 'enabled' : 'disabled']],
        ], null, $this->events->modelConnectionNames(['setting']));
    }

    /** @param array<int, string> $warnings */
    private function persist(bool $enabled, array &$warnings): void
    {
        $modelClass = wncms()->getModelClass('setting');
        $modelClass::query()->updateOrCreate(['key' => 'blade_enabled'], ['value' => $enabled ? '1' : '0']);
        try {
            wncms()->cache()->flush(['settings']);
        } catch (\Throwable $exception) {
            $warnings[] = 'cache_unavailable';
            Log::warning('WNCMS settings cache could not be flushed after Blade policy update.', ['exception' => $exception::class]);
        }
    }

    private function parseBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) return $value;
        if ($value === 1 || $value === '1') return true;
        if ($value === 0 || $value === '0') return false;
        if (is_string($value)) {
            $value = strtolower(trim($value));
            if (in_array($value, ['true', 'on', 'yes'], true)) return true;
            if (in_array($value, ['false', 'off', 'no'], true)) return false;
        }
        return null;
    }

    /** @param array<int, string> $warnings */
    private function withWarnings(BladeAvailabilityState $state, array $warnings): BladeAvailabilityState
    {
        return new BladeAvailabilityState($state->status, $state->enabled, $state->installed, $state->value, array_values(array_unique(array_merge($state->warnings, $warnings))));
    }
}
