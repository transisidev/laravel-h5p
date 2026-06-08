<?php

namespace Transisi\LaravelH5P;

use Transisi\LaravelH5P\Commands\MigrationCommand;
use Transisi\LaravelH5P\Commands\ResetCommand;
use Transisi\LaravelH5P\Console\Commands\InstallCommand;
use Transisi\LaravelH5P\Console\Commands\PublishCommand;
use Transisi\LaravelH5P\Console\Commands\CleanupCommand;
use Transisi\LaravelH5P\Console\Commands\StatusCommand;
use Transisi\LaravelH5P\Helpers\H5pHelper;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class LaravelH5pServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('LaravelH5p', function ($app) {
            return new LaravelH5p($app);
        });

        $this->app->bind('H5pHelper', function () {
            return new H5pHelper();
        });

        $this->app->singleton('command.laravel-h5p.migration', function ($app) {
            return new MigrationCommand();
        });

        $this->app->singleton('command.laravel-h5p.reset', function ($app) {
            return new ResetCommand();
        });

        $this->commands([
            'command.laravel-h5p.migration',
            'command.laravel-h5p.reset',
            InstallCommand::class,
            PublishCommand::class,
            CleanupCommand::class,
            StatusCommand::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/laravel-h5p.php');

        // config
        $this->publishes([
            __DIR__ . '/../../config/laravel-h5p.php' => config_path('laravel-h5p.php'),
        ], 'laravel-h5p-config');

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/laravel-h5p.php',
            'laravel-h5p'
        );

        // language files: load from package so translations work even if not published
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'laravel-h5p');

        $this->publishes([
            __DIR__ . '/../../lang/en/laravel-h5p.php' => lang_path('en/laravel-h5p.php'),
            __DIR__ . '/../../lang/fr/laravel-h5p.php' => lang_path('fr/laravel-h5p.php'),
            __DIR__ . '/../../lang/ar/laravel-h5p.php' => lang_path('ar/laravel-h5p.php'),
        ], 'laravel-h5p-lang');

        // views
        $this->loadViewsFrom(__DIR__ . '/../../views/h5p', 'laravel-h5p');
        $this->publishes([
            __DIR__ . '/../../views/h5p' => resource_path('views/vendor/laravel-h5p'),
        ], 'laravel-h5p-views');

        // migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
        $this->publishes([
            __DIR__ . '/../../migrations/' => database_path('migrations'),
        ], 'laravel-h5p-migrations');

        // h5p assets
        $this->publishes([
            __DIR__ . '/../../assets' => public_path('assets/vendor/laravel-h5p'),
            base_path('vendor/h5p/h5p-core/fonts') => public_path('assets/vendor/h5p/h5p-core/fonts'),
            base_path('vendor/h5p/h5p-core/images') => public_path('assets/vendor/h5p/h5p-core/images'),
            base_path('vendor/h5p/h5p-core/js') => public_path('assets/vendor/h5p/h5p-core/js'),
            base_path('vendor/h5p/h5p-core/styles') => public_path('assets/vendor/h5p/h5p-core/styles'),
            base_path('vendor/h5p/h5p-editor/ckeditor') => public_path('assets/vendor/h5p/h5p-editor/ckeditor'),
            base_path('vendor/h5p/h5p-editor/images') => public_path('assets/vendor/h5p/h5p-editor/images'),
            base_path('vendor/h5p/h5p-editor/language') => public_path('assets/vendor/h5p/h5p-editor/language'),
            base_path('vendor/h5p/h5p-editor/libs') => public_path('assets/vendor/h5p/h5p-editor/libs'),
            base_path('vendor/h5p/h5p-editor/scripts') => public_path('assets/vendor/h5p/h5p-editor/scripts'),
            base_path('vendor/h5p/h5p-editor/styles') => public_path('assets/vendor/h5p/h5p-editor/styles'),
        ], 'laravel-h5p-assets');

        View::composer(['laravel-h5p::content.create', 'laravel-h5p::content.edit'], function (\Illuminate\View\View $view): void {
            if (!config('laravel-h5p.h5p_editor_chunk_upload_enabled')) {
                return;
            }
            $data = $view->getData();
            if (!isset($data['settings']) || !is_array($data['settings'])) {
                return;
            }
            $settings = $data['settings'];
            $settings['editor']['chunkUpload'] = [
                'enabled'        => true,
                'thresholdBytes' => (int) config('laravel-h5p.h5p_editor_chunk_threshold_bytes'),
                'chunkBytes'     => (int) config('laravel-h5p.h5p_editor_chunk_max_chunk_bytes'),
            ];
            if (!isset($settings['editor']['assets'])) {
                $settings['editor']['assets'] = ['js' => [], 'css' => []];
            }
            if (!isset($settings['editor']['assets']['js'])) {
                $settings['editor']['assets']['js'] = [];
            }
            $settings['editor']['assets']['js'][] = asset('js/h5p-editor-chunk-upload.js') . '?v=1';
            $view->with('settings', $settings);
        });
    }
}
