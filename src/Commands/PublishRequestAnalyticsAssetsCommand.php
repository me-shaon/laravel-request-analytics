<?php

namespace MeShaon\RequestAnalytics\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class PublishRequestAnalyticsAssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request-analytics:publish 
                            {--force : Force overwrite existing files}
                            {--clean : Clean up old published files before publishing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up and republish Request Analytics assets and views';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Publishing Request Analytics assets and views...');

        // Paths to clean up
        $vendorViewsPath = resource_path('views/vendor/request-analytics');
        $vendorAssetsPath = public_path('vendor/request-analytics');

        if ($this->option('clean') || config('request-analytics.publishing.cleanup_before_publish', false)) {
            $this->info('Cleaning up old published files...');

            // Clean up old published views
            if (File::exists($vendorViewsPath)) {
                File::deleteDirectory($vendorViewsPath);
                $this->info('Cleaned up old views: ' . $vendorViewsPath);
            }

            // Clean up old published assets
            if (File::exists($vendorAssetsPath)) {
                File::deleteDirectory($vendorAssetsPath);
                $this->info('Cleaned up old assets: ' . $vendorAssetsPath);
            }
        }

        $forcePublish = $this->option('force') || config('request-analytics.publishing.force_publish', false);

        // Republish views
        $this->info('Publishing views...');
        Artisan::call('vendor:publish', [
            '--tag' => 'request-analytics-views',
            '--force' => $forcePublish,
        ]);
        $this->line(Artisan::output());

        // Republish assets
        $this->info('Publishing assets...');
        Artisan::call('vendor:publish', [
            '--tag' => 'request-analytics-assets',
            '--force' => $forcePublish,
        ]);
        $this->line(Artisan::output());

        $this->info('Request Analytics assets and views published successfully!');

        return Command::SUCCESS;
    }
}
