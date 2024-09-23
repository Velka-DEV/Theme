<?php

namespace YAAP\Theme;

use Illuminate\Console\Application;
use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;

/**
 * Class ThemeServiceProvider.
 */
class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot(): void
    {
        $this->loadPublishes();

        $this->loadConfig();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // init theme with default finder
        $this->app->singleton('theme-loader', function (Container $app) {
            return new ThemeLoader(
                $app,
                $this->app['view']->getFinder(),
                $this->app['translator']->getLoader()
            );
        });

        $this->app->singleton(
            'theme.create',
            fn (Container $app) => new Commands\ThemeGeneratorCommand($app['config'], $app['files'])
        );

        $this->app->singleton(
            'theme.create-v2',
            fn (Container $app) => new Commands\ThemeGeneratorV2Command($app['config'], $app['files'])
        );

        $this->app->singleton(
            'theme.destroy',
            fn (Container $app) => new Commands\ThemeDestroyCommand($app['config'], $app['files'])
        );

        $this->commands([
            'theme.create',
            'theme.destroy',
            'theme.create-v2',
        ]);
    }

    protected function loadPublishes(): void
    {
        $this->publishes([
            $this->pathToConfig('config/theme.php') => config_path('theme.php'),
        ], 'configs');
    }

    protected function loadConfig(): void
    {
        $this->mergeConfigFrom($this->pathToConfig('config/theme.php'), 'theme');
    }

    /**
     * Get the absolute path to some package resource.
     *
     * @param string $path The relative path to the resource
     */
    protected function pathToConfig(string $path): string
    {
        return __DIR__ . '/../' . $path;
    }
}
