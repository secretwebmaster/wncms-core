<?php

namespace Wncms\Plugins\Contracts;

interface PluginInterface
{
    public function init(): void;

    public function activate(): void;

    public function deactivate(): void;

    public function delete(): void;

    public function getId(): string;

    public function getPluginId(): string;

    public function getName(): string;

    public function getVersion(): string;

    public function getRootPath(): string;

    public function getViewFile(string $relativePath): string;

    public function renderView(string $relativePath, array $data = []): string;
}
