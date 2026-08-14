<?php

namespace Wncms\Auth\Api\V2;

use Illuminate\Validation\ValidationException;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\ModelPermissionResolver;
use Wncms\Models\User;

final class AbilityTemplateRegistry
{
    public const TEMPLATES = [
        'read_only',
        'content_editor',
        'site_manager',
        'full_admin',
    ];

    private const CONTENT_DOMAINS = [
        'attachments', 'comments', 'links', 'media', 'menus', 'pages', 'posts', 'tags', 'uploads',
    ];

    private const NON_DELEGABLE_PATTERNS = [
        'auth.', '.auth.', 'credential', 'password', 'profile.security', 'service_token', 'service-token',
        'session', 'step_up', 'step-up',
    ];

    private const EXECUTABLE_PATTERNS = [
        'artisan', 'code.', 'command', 'core.update', 'extension', 'package', 'plugin', 'shell', 'tool.',
    ];

    public function __construct(
        private ApiContractRegistry $contracts,
        private ModelPermissionResolver $modelPermissions,
    ) {}

    /**
     * Return the service-token choices the actor may safely delegate.
     *
     * @return array{templates: array<string, array<int, string>>, abilities: array<int, string>, websites: array<int, array<string, mixed>>, expiry_days: array<int, int|string>}
     */
    public function optionsFor(User $actor): array
    {
        $grantable = $this->grantableOperations($actor);
        $templates = [];
        foreach (self::TEMPLATES as $template) {
            $templates[$template] = $this->templateAbilities($grantable, $template);
        }

        $expiryDays = [30, 90, 365];
        if ($this->actorCan($actor, 'api_token_create_permanent')) {
            $expiryDays[] = 'permanent';
        }

        return [
            'templates' => $templates,
            'abilities' => $this->abilities($grantable),
            'websites' => $actor->websites()->get()->map(static fn ($website): array => array_filter([
                'id' => $website->getKey(),
                'name' => $website->name ?? null,
                'domain' => $website->domain ?? null,
            ], static fn (mixed $value): bool => $value !== null))->values()->all(),
            'expiry_days' => $expiryDays,
        ];
    }

    /**
     * Resolve a normalized grant bounded by the formal registry and actor permissions.
     *
     * @param  array<int, mixed>  $additions
     * @param  array<int, mixed>  $removals
     * @return array<int, string>
     */
    public function resolveGrant(User $actor, string $template, array $additions, array $removals): array
    {
        if (! in_array($template, self::TEMPLATES, true)) {
            throw ValidationException::withMessages(['template' => ['Unknown service-token template.']]);
        }

        $grantable = $this->grantableOperations($actor);
        $known = $this->abilities($grantable);
        $additions = $this->normalize($additions);
        $removals = $this->normalize($removals);
        $unknown = array_values(array_diff(array_unique([...$additions, ...$removals]), $known));
        if ($unknown !== []) {
            throw ValidationException::withMessages(['abilities' => ['Unknown or non-delegable abilities: '.implode(', ', $unknown)]]);
        }

        $abilities = array_values(array_unique([
            ...$this->templateAbilities($grantable, $template),
            ...$additions,
        ]));
        $abilities = array_values(array_diff($abilities, $removals));
        sort($abilities);

        return $abilities;
    }

    /** @return array<int, \Wncms\Api\V2\Data\ApiOperationContract> */
    private function grantableOperations(User $actor): array
    {
        return array_values(array_filter(
            $this->contracts->operations(),
            fn (ApiOperationContract $operation): bool => $this->isGrantable($actor, $operation),
        ));
    }

    private function isGrantable(User $actor, ApiOperationContract $operation): bool
    {
        if ($operation->ability === null || trim($operation->ability) === ''
            || ! in_array(ApiCredential::TYPE_SERVICE_TOKEN, $operation->acceptedCredentialTypes, true)
            || $operation->requiresStepUp) {
            return false;
        }

        $identity = strtolower($operation->id.' '.$operation->domain.' '.$operation->ability);
        foreach (self::NON_DELEGABLE_PATTERNS as $pattern) {
            if (str_contains($identity, $pattern)) {
                return false;
            }
        }

        return match ($operation->permissionMode) {
            'static' => $operation->permission === null || trim($operation->permission) === ''
                || $this->actorCan($actor, $operation->permission),
            'model_template' => $operation->permission !== null
                && $this->modelPermissions->actorCanAny($actor, $operation->permission),
            default => false,
        };
    }

    /**
     * @param  array<int, \Wncms\Api\V2\Data\ApiOperationContract>  $operations
     * @return array<int, string>
     */
    private function templateAbilities(array $operations, string $template): array
    {
        $abilities = [];
        foreach ($operations as $operation) {
            if ($this->includedInTemplate($operation, $template)) {
                $abilities[] = (string) $operation->ability;
            }
        }

        $abilities = array_values(array_unique($abilities));
        sort($abilities);

        return $abilities;
    }

    private function includedInTemplate(ApiOperationContract $operation, string $template): bool
    {
        $identity = strtolower($operation->id.' '.$operation->domain.' '.$operation->ability);
        if ($template !== 'full_admin' && (str_contains($identity, 'security_event') || str_contains($identity, 'security-event'))) {
            return false;
        }

        $read = in_array($operation->method, ['GET', 'HEAD'], true) && $operation->sideEffectKind === 'read';
        if ($template === 'read_only') {
            return $read;
        }

        $domain = strtolower($operation->domain);
        if ($template === 'content_editor') {
            return $read || ($operation->websiteScoped && in_array($domain, self::CONTENT_DOMAINS, true));
        }

        if ($template === 'site_manager') {
            foreach (self::EXECUTABLE_PATTERNS as $pattern) {
                if (str_contains($identity, $pattern)) {
                    return false;
                }
            }

            return $read || $operation->websiteScoped;
        }

        return $template === 'full_admin';
    }

    /**
     * @param  array<int, \Wncms\Api\V2\Data\ApiOperationContract>  $operations
     * @return array<int, string>
     */
    private function abilities(array $operations): array
    {
        $abilities = array_values(array_unique(array_map(
            static fn (ApiOperationContract $operation): string => (string) $operation->ability,
            $operations,
        )));
        sort($abilities);

        return $abilities;
    }

    /** @param array<int, mixed> $abilities @return array<int, string> */
    private function normalize(array $abilities): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $ability): string => trim((string) $ability),
            $abilities,
        ), static fn (string $ability): bool => $ability !== '')));
    }

    private function actorCan(User $actor, string $permission): bool
    {
        return method_exists($actor, 'checkPermissionTo') && $actor->checkPermissionTo($permission);
    }
}
