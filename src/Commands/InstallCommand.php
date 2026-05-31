<?php

namespace Commero\Commands;

use Commero\Database\Seeders\RolesAndPermissionsSeeder;
use Commero\Models\User as CommeroUser;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Throwable;

class InstallCommand extends Command
{
    protected $signature = 'commero:install
        {--no-assets : Skip Filament asset publishing}
        {--no-migrate : Skip database migrations}
        {--no-admin : Skip interactive admin user creation}
        {--force : Force publishing and migration operations}';

    protected $description = 'Install and bootstrap Commero in the host Laravel application.';

    public function handle(): int
    {
        $this->components->info('Installing Commero...');

        $this->configureAuthModel();

        $this->call('vendor:publish', [
            '--tag' => 'commero-config',
            '--force' => (bool) $this->option('force'),
        ]);

        if (! $this->option('no-assets')) {
            $this->call('filament:assets');
        }

        if (! $this->option('no-migrate')) {
            $this->call('migrate', array_filter([
                '--force' => $this->option('force') ? true : null,
            ]));
        }

        $this->seedRolesAndPermissions();
        $this->createAdminUser();

        $this->newLine();
        $this->components->info('Commero installation complete.');
        $this->line('Admin panel: <comment>/admin</comment>');

        return self::SUCCESS;
    }

    protected function createAdminUser(): void
    {
        if ($this->option('no-admin') || ! $this->input->isInteractive()) {
            return;
        }

        if (! $this->confirm('Create an admin user now?', true)) {
            return;
        }

        $userModelClass = config('auth.providers.users.model');

        if (! is_string($userModelClass) || ! class_exists($userModelClass)) {
            $this->components->warn('Skipping admin creation: auth.providers.users.model is not configured.');

            return;
        }

        if (! is_subclass_of($userModelClass, Model::class)) {
            $this->components->warn("Skipping admin creation: [{$userModelClass}] is not an Eloquent model.");

            return;
        }

        /** @var Model $userModel */
        $userModel = new $userModelClass();

        if (! method_exists($userModel, 'getTable')) {
            $this->components->warn("Skipping admin creation: [{$userModelClass}] does not look like a standard user model.");

            return;
        }

        $email = $this->askValidEmail($userModelClass);

        if ($email === null) {
            return;
        }

        $name = trim((string) $this->ask('Admin name'));
        $password = (string) $this->secret('Admin password');
        $passwordConfirmation = (string) $this->secret('Confirm admin password');

        if ($password === '' || $password !== $passwordConfirmation) {
            $this->components->error('Admin user was not created because the password confirmation did not match.');

            return;
        }

        $attributes = [
            'email' => $email,
            'password' => Hash::make($password),
        ];

        if ($name !== '') {
            $attributes['name'] = $name;
        }

        /** @var Model $user */
        $user = new $userModelClass();
        $user->forceFill($attributes);
        $user->save();

        if (method_exists($user, 'assignRole')) {
            $user->assignRole('admin');
            $this->components->info("Admin user [{$email}] created and assigned the [admin] role.");

            return;
        }

        $this->components->warn("Admin user [{$email}] created, but no role was assigned because [{$userModelClass}] does not support assignRole().");
    }

    protected function askValidEmail(string $userModelClass): ?string
    {
        /** @var class-string<Model> $userModelClass */
        while (true) {
            $email = strtolower(trim((string) $this->ask('Admin email')));

            if ($email === '') {
                $this->components->error('Email is required.');

                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->components->error('Enter a valid email address.');

                continue;
            }

            if ($userModelClass::query()->where('email', $email)->exists()) {
                $this->components->error("A user with email [{$email}] already exists.");

                if (! $this->confirm('Try another email?', true)) {
                    return null;
                }

                continue;
            }

            return $email;
        }
    }

    protected function seedRolesAndPermissions(): void
    {
        try {
            $this->call('shield:generate', [
                '--all' => true,
                '--option' => 'permissions',
            ]);
        } catch (Throwable) {
            $this->components->warn('Filament Shield permission generation is unavailable. Seeding package roles and permissions directly.');
        }

        $this->call('db:seed', [
            '--class' => RolesAndPermissionsSeeder::class,
            '--force' => true,
        ]);
    }

    protected function configureAuthModel(): void
    {
        config([
            'auth.providers.users.model' => CommeroUser::class,
        ]);

        $updatedFiles = [];

        foreach (['.env', '.env.example'] as $file) {
            if ($this->syncEnvironmentValue(base_path($file), 'AUTH_MODEL', 'Commero\\\\Models\\\\User')) {
                $updatedFiles[] = $file;
            }
        }

        if ($updatedFiles === []) {
            $this->components->info('AUTH_MODEL is already configured for Commero\\Models\\User.');

            return;
        }

        $this->components->info(sprintf(
            'Configured AUTH_MODEL=Commero\\\\Models\\\\User in %s.',
            implode(', ', $updatedFiles),
        ));
    }

    protected function syncEnvironmentValue(string $path, string $key, string $value): bool
    {
        if (! is_file($path) || ! is_readable($path) || ! is_writable($path)) {
            return false;
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            return false;
        }

        $line = "{$key}={$value}";
        $pattern = "/^{$key}=.*$/m";

        if (preg_match($pattern, $contents) === 1) {
            $updated = preg_replace($pattern, $line, $contents, 1);

            if (! is_string($updated) || $updated === $contents) {
                return false;
            }

            file_put_contents($path, $updated);

            return true;
        }

        $updated = rtrim($contents).PHP_EOL.$line.PHP_EOL;
        file_put_contents($path, $updated);

        return true;
    }
}
