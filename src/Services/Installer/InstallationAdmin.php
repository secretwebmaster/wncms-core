<?php

namespace Wncms\Services\Installer;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Installer-only credentials; never persisted to config, environment files or logs. */
final class InstallationAdmin
{
    private function __construct(public readonly string $email, public readonly string $passwordHash) {}

    public static function fromInput(array $input): self
    {
        $validated = Validator::make($input, [
            'admin_email' => ['required', 'string', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:12', 'max:72', 'confirmed'],
        ])->validate();

        // Bcrypt limits passwords by bytes, not Unicode character count.
        if (strlen($validated['admin_password']) > 72 || str_contains($validated['admin_password'], "\0")) {
            throw ValidationException::withMessages(['admin_password' => 'Use at most 72 UTF-8 bytes and no null characters.']);
        }

        return new self($validated['admin_email'], Hash::make($validated['admin_password']));
    }

    public static function consoleInput(Command $command, bool $interactive): array
    {
        $email = $command->option('admin_email');
        $password = env('WNCMS_INSTALL_ADMIN_PASSWORD');
        if ($interactive && ! $email) {
            $email = $command->ask('Administrator email');
        }
        if ($interactive && ! is_string($password)) {
            $password = $command->secret('Administrator password (at least 12 characters)');
            $confirmation = $command->secret('Confirm administrator password');
        } else {
            $confirmation = $password;
        }

        return ['admin_email' => $email, 'admin_password' => $password, 'admin_password_confirmation' => $confirmation];
    }

    /** Expose only the hash to the seeder for the duration of this installation. */
    public function seed(callable $callback): void
    {
        if (app()->bound(self::class)) {
            throw new \RuntimeException('An administrator seed context is already active.');
        }
        app()->instance(self::class, $this);
        try {
            $callback();
        } finally {
            app()->forgetInstance(self::class);
        }
    }
}
