<?php

namespace Prophets\DrupalJsonApi;

use Illuminate\Support\ServiceProvider;
use Prophets\DrupalJsonApi\Repositories\RepositoryFactory;

class DrupalJsonApiServiceProvider extends ServiceProvider
{
    protected $commands = [
        Console\RepositoryMakeCommand::class,
        Console\CacheDecoratorMakeCommand::class,
        Console\InterfaceMakeCommand::class,
    ];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/drupal-jsonapi.php', 'drupal-jsonapi');

        $this->publishes([
            __DIR__.'/../config/drupal-jsonapi.php' => config_path('drupal-jsonapi.php'),
        ]);
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $repositoryFactory = new RepositoryFactory(config('drupal-jsonapi.use_cache'));
        $this->app->singleton(RepositoryFactory::class, $repositoryFactory);
        $repositories = config('drupal-jsonapi.repository_models', []);

        /**
         * Register repositories from config.
         */
        foreach ($repositories as $modelClass) {
            $repositoryClassName = call_user_func($modelClass . '::getRepositoryClassName');
            $repositoryInterface = call_user_func($modelClass . '::getRepositoryInterface');

            $this->app->bind(
                $repositoryInterface,
                function () use ($repositoryFactory, $repositoryClassName, $modelClass) {
                    return $repositoryFactory->create($modelClass, $repositoryClassName);
                }
            );
        }

        /**
         * Register artisan commands
         */
        $this->commands($this->commands);
    }
}
