<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;

class SettingUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wncms:setting-update {key} {value?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $key = $this->argument('key');
        $value = $this->argument('value');
        $oldValue = gss($key);
        uss($key, $value);
        $this->info("Setting $key has been updated from '\e[33m$oldValue\e[0m' -> '\e[33m" . gss($key) . "\e[0m'");
    }
}
