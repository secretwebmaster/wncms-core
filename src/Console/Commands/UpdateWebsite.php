<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class UpdateWebsite extends Command
{
    protected $signature = 'wncms:update-website {key} {value}';

    protected $description = 'Update one website field from CLI';

    public function handle()
    {
        $key = (string) $this->argument('key');
        $rawValue = (string) $this->argument('value');

        if ($key === '') {
            $this->error('The key argument is required.');
            return Command::FAILURE;
        }

        $websiteClass = wncms()->getModelClass('website');
        $website = wncms()->website()->getCurrent();

        if (!$website) {
            $website = $websiteClass::query()->orderBy('id')->first();
        }

        if (!$website) {
            $this->error('No website found.');
            return Command::FAILURE;
        }

        if (!Schema::hasColumn($website->getTable(), $key)) {
            $this->error("Column '{$key}' does not exist on {$website->getTable()}.");
            return Command::FAILURE;
        }

        $value = $this->normalizeValue($rawValue);
        $oldValue = $website->getAttribute($key);

        if ($key === 'theme') {
            $theme = (string) $value;
            if ($theme === '') {
                $this->error('Theme cannot be empty.');
                return Command::FAILURE;
            }

            if ($theme !== (string) $website->theme) {
                $defaultOptions = config("theme.{$theme}.default", []);
                foreach ((array) $defaultOptions as $optionKey => $optionValue) {
                    $website->theme_options()->firstOrCreate(
                        [
                            'theme' => $theme,
                            'key' => $optionKey,
                        ],
                        [
                            'value' => $optionValue,
                        ]
                    );
                }
            }
        }

        $website->update([$key => $value]);
        wncms()->cache()->flush(['websites']);

        $before = is_scalar($oldValue) || $oldValue === null ? var_export($oldValue, true) : json_encode($oldValue);
        $after = is_scalar($value) || $value === null ? var_export($value, true) : json_encode($value);

        $this->info("Website #{$website->id} updated: {$key} {$before} -> {$after}");

        return Command::SUCCESS;
    }

    protected function normalizeValue(string $value)
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'null' => null,
            'true' => true,
            'false' => false,
            default => $value,
        };
    }
}
