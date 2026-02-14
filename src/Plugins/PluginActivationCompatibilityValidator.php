<?php

namespace Wncms\Plugins;

use Wncms\Models\Plugin;

class PluginActivationCompatibilityValidator
{
    public function __construct(protected PluginManifestManager $manifestManager)
    {
    }

    public function validate(Plugin $plugin): array
    {
        $manifestPath = $this->resolvePluginManifestPath($plugin);
        if (!$manifestPath) {
            return $this->fail(
                'wncms::word.plugin_activation_blocked_path_empty',
                [],
                'plugin path is empty'
            );
        }

        $read = $this->manifestManager->readAndValidateManifestPath($manifestPath);
        if (!$read['passed']) {
            return $this->fail(
                'wncms::word.plugin_activation_blocked_manifest_invalid',
                ['reason' => (string) $read['message']],
                (string) $read['message']
            );
        }

        $manifest = $read['manifest'];
        if ((string) ($manifest['id'] ?? '') !== (string) $plugin->plugin_id) {
            return $this->fail(
                'wncms::word.plugin_activation_blocked_manifest_id_mismatch',
                [],
                'plugin.json id does not match plugin_id'
            );
        }

        $dependencyValidation = $this->validateDependencies($manifest);
        if (!$dependencyValidation['passed']) {
            return $dependencyValidation;
        }

        return [
            'passed' => true,
            'message' => '',
        ];
    }

    protected function resolvePluginManifestPath(Plugin $plugin): ?string
    {
        $pluginsRoot = config('filesystems.disks.plugins.root', public_path('plugins'));
        $pluginPath = trim((string) $plugin->path, '/\\');

        if ($pluginPath === '') {
            return null;
        }

        return rtrim($pluginsRoot, '/\\') . DIRECTORY_SEPARATOR . $pluginPath . DIRECTORY_SEPARATOR . 'plugin.json';
    }

    protected function validateDependencies(array $manifest): array
    {
        $dependencies = $this->normalizeDependencyRules($manifest['dependencies'] ?? []);
        if (!$dependencies['passed']) {
            return $this->fail(
                'wncms::word.plugin_activation_blocked_dependency_invalid',
                ['reason' => (string) $dependencies['message']],
                (string) $dependencies['message']
            );
        }

        foreach ($dependencies['rules'] as $dependencyRule) {
            $dependencyId = $dependencyRule['id'];
            $constraint = $dependencyRule['version'];
            $dependency = Plugin::where('plugin_id', $dependencyId)->first();

            if (!$dependency) {
                return $this->fail(
                    'wncms::word.plugin_activation_blocked_dependency_missing',
                    ['dependency' => $dependencyId],
                    "missing required dependency plugin: {$dependencyId}"
                );
            }

            if ((string) $dependency->status !== 'active') {
                $dependencyLabel = $this->formatPluginLabel($dependency);
                return $this->fail(
                    'wncms::word.plugin_activation_blocked_dependency_inactive',
                    ['dependency' => $dependencyLabel],
                    "dependency plugin [{$dependencyLabel}] is not active"
                );
            }

            if ($constraint !== '' && !$this->isVersionConstraintSatisfied((string) $dependency->version, $constraint)) {
                $dependencyLabel = $this->formatPluginLabel($dependency);
                return $this->fail(
                    'wncms::word.plugin_activation_blocked_dependency_version_mismatch',
                    [
                        'dependency' => $dependencyLabel,
                        'version' => (string) $dependency->version,
                        'constraint' => $constraint,
                    ],
                    "dependency plugin [{$dependencyLabel}] version [{$dependency->version}] does not satisfy [{$constraint}]"
                );
            }
        }

        return [
            'passed' => true,
            'message' => '',
        ];
    }

    protected function normalizeDependencyRules($dependencies): array
    {
        if (!is_array($dependencies)) {
            return [
                'passed' => false,
                'message' => 'plugin dependencies must be an array',
                'rules' => [],
            ];
        }

        $rules = [];
        foreach ($dependencies as $key => $value) {
            if (is_int($key)) {
                if (is_string($value)) {
                    $dependencyId = trim($value);
                    if ($dependencyId === '') {
                        continue;
                    }

                    $rules[] = [
                        'id' => $dependencyId,
                        'version' => '',
                    ];
                    continue;
                }

                if (is_array($value)) {
                    $dependencyId = trim((string) ($value['id'] ?? ''));
                    if ($dependencyId === '') {
                        return [
                            'passed' => false,
                            'message' => 'dependency object requires id',
                            'rules' => [],
                        ];
                    }

                    $rules[] = [
                        'id' => $dependencyId,
                        'version' => trim((string) ($value['version'] ?? '')),
                    ];
                    continue;
                }

                return [
                    'passed' => false,
                    'message' => 'dependency entries must be string or object',
                    'rules' => [],
                ];
            }

            $dependencyId = trim((string) $key);
            if ($dependencyId === '') {
                return [
                    'passed' => false,
                    'message' => 'dependency id cannot be empty',
                    'rules' => [],
                ];
            }

            if (!is_string($value) && !is_numeric($value)) {
                return [
                    'passed' => false,
                    'message' => "dependency version constraint for [{$dependencyId}] must be a string",
                    'rules' => [],
                ];
            }

            $rules[] = [
                'id' => $dependencyId,
                'version' => trim((string) $value),
            ];
        }

        return [
            'passed' => true,
            'message' => '',
            'rules' => $rules,
        ];
    }

    protected function isVersionConstraintSatisfied(string $version, string $constraint): bool
    {
        $version = ltrim(trim($version), 'vV');
        $constraint = trim($constraint);

        if ($constraint === '' || $constraint === '*') {
            return true;
        }

        $orGroups = preg_split('/\s*\|\|\s*/', $constraint) ?: [];
        foreach ($orGroups as $orGroup) {
            $tokens = preg_split('/\s*,\s*|\s+/', trim($orGroup)) ?: [];
            $tokens = array_values(array_filter($tokens, fn ($token) => trim((string) $token) !== ''));

            if (empty($tokens)) {
                continue;
            }

            $groupPassed = true;
            foreach ($tokens as $token) {
                if (!$this->matchesConstraintToken($version, (string) $token)) {
                    $groupPassed = false;
                    break;
                }
            }

            if ($groupPassed) {
                return true;
            }
        }

        return false;
    }

    protected function matchesConstraintToken(string $version, string $token): bool
    {
        $token = trim($token);
        if ($token === '' || $token === '*') {
            return true;
        }

        if (str_starts_with($token, '^')) {
            return $this->matchesCaretConstraint($version, substr($token, 1));
        }

        if (str_starts_with($token, '~')) {
            return $this->matchesTildeConstraint($version, substr($token, 1));
        }

        if (preg_match('/^(>=|<=|>|<|==|=|!=)\s*(.+)$/', $token, $matches)) {
            $operator = $matches[1] === '==' ? '=' : $matches[1];
            $targetVersion = ltrim(trim($matches[2]), 'vV');
            return version_compare($version, $targetVersion, $operator);
        }

        $targetVersion = ltrim($token, 'vV');
        return version_compare($version, $targetVersion, '=');
    }

    protected function matchesCaretConstraint(string $version, string $constraint): bool
    {
        $constraint = ltrim(trim($constraint), 'vV');
        if ($constraint === '') {
            return false;
        }

        $parts = $this->normalizeNumericVersionParts($constraint);
        if ($parts === []) {
            return version_compare($version, $constraint, '>=');
        }

        $major = $parts[0];
        $minor = $parts[1] ?? 0;
        $patch = $parts[2] ?? 0;

        $lowerBound = $major . '.' . $minor . '.' . $patch;

        if ($major > 0) {
            $upperBound = ($major + 1) . '.0.0';
        } elseif ($minor > 0) {
            $upperBound = '0.' . ($minor + 1) . '.0';
        } else {
            $upperBound = '0.0.' . ($patch + 1);
        }

        return version_compare($version, $lowerBound, '>=')
            && version_compare($version, $upperBound, '<');
    }

    protected function matchesTildeConstraint(string $version, string $constraint): bool
    {
        $constraint = ltrim(trim($constraint), 'vV');
        if ($constraint === '') {
            return false;
        }

        $parts = $this->normalizeNumericVersionParts($constraint);
        if ($parts === []) {
            return version_compare($version, $constraint, '>=');
        }

        $major = $parts[0];
        $minor = $parts[1] ?? 0;
        $patch = $parts[2] ?? 0;
        $partCount = count($parts);

        $lowerBound = $major . '.' . $minor . '.' . $patch;

        if ($partCount >= 3) {
            $upperBound = $major . '.' . ($minor + 1) . '.0';
        } else {
            $upperBound = ($major + 1) . '.0.0';
        }

        return version_compare($version, $lowerBound, '>=')
            && version_compare($version, $upperBound, '<');
    }

    protected function normalizeNumericVersionParts(string $version): array
    {
        if (!preg_match('/^\d+(?:\.\d+){0,2}/', $version, $matches)) {
            return [];
        }

        return array_map('intval', explode('.', $matches[0]));
    }

    protected function fail(string $messageKey, array $messageParams, string $fallbackMessage): array
    {
        return [
            'passed' => false,
            'message_key' => $messageKey,
            'message_params' => $messageParams,
            'message' => $this->translateOrFallback($messageKey, $messageParams, $fallbackMessage),
        ];
    }

    protected function translateOrFallback(string $messageKey, array $messageParams, string $fallbackMessage): string
    {
        $translated = __($messageKey, $messageParams);
        return $translated === $messageKey ? $fallbackMessage : $translated;
    }

    protected function formatPluginLabel(Plugin $plugin): string
    {
        $pluginId = (string) $plugin->plugin_id;
        $pluginName = trim((string) $plugin->name);

        if ($pluginName !== '' && $pluginName !== $pluginId) {
            return $pluginId . ' (' . $pluginName . ')';
        }

        return $pluginId;
    }
}
