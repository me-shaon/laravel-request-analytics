<?php

namespace MeShaon\RequestAnalytics\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class SetupCommand extends Command
{
    protected $signature = 'laravel-request-analytics:setup
                                   {--force : Overwrite existing config and assets}';

    protected $description = 'Set up Laravel Request Analytics';

    public function handle(): int
    {
        $this->info('Publishing migrations...');
        Artisan::call('vendor:publish', [
            '--tag' => 'request-analytics-migrations',
            '--force' => true,
        ]);
        $this->line(Artisan::output());

        // Ask the user which migration approach they prefer
        $choice = $this->choice(
            'How would you like to run migrations?',
            ['Run only Request Analytics migrations (default)', 'Run all migrations'],
            0 // default = first option (safe)
        );

        if ($choice === 'Run all migrations') {
            $this->info('Running all migrations...');
            Artisan::call('migrate', ['--force' => true]);
            $this->line(Artisan::output());
        } else {
            $this->info('Running only Request Analytics migrations...');

            $migrationPath = database_path('migrations');
            $files = glob($migrationPath.'/*_request_analytics*.php');

            foreach ($files as $file) {
                $relativePath = Str::after($file, base_path().DIRECTORY_SEPARATOR);
                Artisan::call('migrate', [
                    '--path' => $relativePath,
                    '--force' => true,
                ]);
                $this->line(Artisan::output());
            }

            $this->newLine();
            $this->warn('ℹ️ Remember to run `php artisan migrate` later for other pending migrations in your app.');
        }

        $this->info('Publishing config...');
        Artisan::call('vendor:publish', [
            '--tag' => 'request-analytics-config',
            '--force' => (bool) $this->option('force'),
        ]);
        $this->line(Artisan::output());

        $this->info('Publishing assets...');
        Artisan::call('vendor:publish', [
            '--tag' => 'request-analytics-assets',
            '--force' => (bool) $this->option('force'),
        ]);
        $this->line(Artisan::output());

        $this->newLine();
        $this->info('✅ Laravel Request Analytics setup complete.');

        return self::SUCCESS;
    }
}
