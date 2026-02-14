<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Plugin extends BaseModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $modelKey = 'plugin';

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-plug'
    ];

    public const STATUSES = [
        'active',
        'inactive',
        'safe',
    ];

    public const LOAD_ERROR_PREFIX = '[LOAD_ERROR]';

    public const LOAD_ERROR_SOURCE_FILE_MARKER = ' | source_file=';

    /**
     * ----------------------------------------------------------------------------------------------------
     * Contracts
     * ----------------------------------------------------------------------------------------------------
     */
    public static function getModelKey(): string
    {
        return self::$modelKey;
    }

    public static function getTagMeta(): array
    {
        return [];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')->singleFile();
    }

    public static function formatLoadErrorRemark(string $message, ?string $sourceFile = null): string
    {
        $remark = self::LOAD_ERROR_PREFIX . ' ' . now()->toDateTimeString() . ' ' . mb_substr(trim($message), 0, 350);
        $normalizedSourceFile = trim((string) $sourceFile);

        if ($normalizedSourceFile !== '') {
            $remark .= self::LOAD_ERROR_SOURCE_FILE_MARKER . mb_substr($normalizedSourceFile, 0, 350);
        }

        return $remark;
    }

    public static function extractLoadErrorDiagnostics(?string $remark): array
    {
        $normalizedRemark = (string) $remark;
        if (!str_starts_with($normalizedRemark, self::LOAD_ERROR_PREFIX)) {
            return [
                'is_load_error' => false,
                'last_load_error' => '-',
                'source_file' => '-',
            ];
        }

        $payload = trim(substr($normalizedRemark, strlen(self::LOAD_ERROR_PREFIX)));
        $sourceFile = '-';

        if (str_contains($payload, self::LOAD_ERROR_SOURCE_FILE_MARKER)) {
            [$payload, $sourceFilePayload] = explode(self::LOAD_ERROR_SOURCE_FILE_MARKER, $payload, 2);
            $sourceFileCandidate = trim($sourceFilePayload);
            if ($sourceFileCandidate !== '') {
                $sourceFile = $sourceFileCandidate;
            }
        }

        $payload = preg_replace('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\s*/', '', trim($payload)) ?? '';

        return [
            'is_load_error' => true,
            'last_load_error' => $payload !== '' ? $payload : '-',
            'source_file' => $sourceFile,
        ];
    }
}
