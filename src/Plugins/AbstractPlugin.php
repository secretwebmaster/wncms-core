<?php

namespace Wncms\Plugins;

use Wncms\Models\Plugin;
use Wncms\Plugins\Contracts\PluginInterface;

abstract class AbstractPlugin implements PluginInterface
{
    protected ?Plugin $plugin = null;

    protected array $manifest = [];

    protected string $pluginRootPath = '';

    public array $upgrades = [];

    public function setContext(Plugin $plugin, array $manifest = [], string $pluginRootPath = ''): static
    {
        $this->plugin = $plugin;
        $this->manifest = $manifest;
        $this->pluginRootPath = $pluginRootPath;

        return $this;
    }

    public function init(): void
    {
    }

    public function activate(): void
    {
    }

    public function deactivate(): void
    {
    }

    public function delete(): void
    {
    }

    public function getId(): string
    {
        if (!empty($this->manifest['id'])) {
            return (string) $this->manifest['id'];
        }

        return (string) ($this->plugin?->plugin_id ?? '');
    }

    public function getPluginId(): string
    {
        return $this->getId();
    }

    public function getName(): string
    {
        if (array_key_exists('name', $this->manifest)) {
            return $this->resolveTranslatableManifestField($this->manifest['name'], (string) ($this->plugin?->name ?? $this->getId()));
        }

        return (string) ($this->plugin?->name ?? $this->getId());
    }

    public function getVersion(): string
    {
        if (!empty($this->manifest['version'])) {
            return (string) $this->manifest['version'];
        }

        return (string) ($this->plugin?->version ?? '1.0.0');
    }

    protected function pluginPath(string $relativePath = ''): string
    {
        $relativePath = trim(str_replace('\\\\', '/', $relativePath), '/');

        if ($relativePath === '') {
            return $this->pluginRootPath;
        }

        return rtrim($this->pluginRootPath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    public function getRootPath(): string
    {
        return $this->pluginPath();
    }

    public function getViewFile(string $relativePath): string
    {
        $relativePath = trim(str_replace('\\\\', '/', $relativePath), '/');

        return $this->pluginPath('views/' . $relativePath);
    }

    public function renderView(string $relativePath, array $data = []): string
    {
        return view()->file($this->getViewFile($relativePath), $data)->render();
    }

    protected function resolveTranslatableManifestField($value, string $fallback = ''): string
    {
        return app(PluginManifestManager::class)->resolveTranslatableField($value, $fallback);
    }

    protected function settingKey(string $key): string
    {
        return $this->getId() . ':' . trim($key);
    }

    protected function getSetting(string $key, $fallback = null)
    {
        return gss($this->settingKey($key), $fallback);
    }

    protected function setSetting(string $key, $value): bool
    {
        return uss($this->settingKey($key), $value);
    }

    protected function setDefaultSetting(string $key, $value): bool
    {
        $lookupKey = $this->settingKey($key);
        $current = gss($lookupKey, null);

        if ($current === null || $current === '') {
            return uss($lookupKey, $value);
        }

        return true;
    }
}
